<?php

use App\Models\AperturaCaja;
use App\Models\ArqueoCaja;
use App\Models\DetalleArqueo;
use App\Models\Egresos;
use App\Models\PagoVenta;
use App\Models\TasaCambio;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public bool $abrirCajaModal = false;
    public bool $registrarEgresoModal = false;
    public bool $modificarTasaModal = false;

    public string $caja = '1';

    public bool $cajaAbierta = false;
    public ?int $aperturaCajaId = null;

    public string $montoApertura = '0.00';
    public string $tasaOficial = '0.00';
    public string $nuevaTasaOficial = '';
    public string $tasaCambioApertura = '';

    public string $monedaEgreso = 'cordoba';
    public string $montoEgresoCordobas = '';
    public string $montoEgresoDolares = '';
    public string $motivoEgreso = '';
    public string $descripcionEgreso = '';

    public float $totalAbonoCordobas = 0;
    public float $totalAbonoDolares = 0;

    public float $totalVentaCordobas = 0;
    public float $totalVentaDolares = 0;

    public float $totalEgresoCordobas = 0;
    public float $totalEgresoDolares = 0;

    public array $monedasEgreso = [
        ['id' => 'cordoba', 'name' => 'Córdoba'],
        ['id' => 'dolar', 'name' => 'Dólar'],
        ['id' => 'ambas', 'name' => 'Ambas monedas'],
    ];

    public array $motivosEgreso = [
        ['id' => 'Devolución a cliente', 'name' => 'Devolución a cliente'],
        ['id' => 'Compra de repuestos', 'name' => 'Compra de repuestos'],
        ['id' => 'Compra de accesorios', 'name' => 'Compra de accesorios'],
        ['id' => 'Compra de tóner o tinta', 'name' => 'Compra de tóner o tinta'],
        ['id' => 'Compra de papel para fotocopias', 'name' => 'Compra de papel para fotocopias'],
        ['id' => 'Compra de papel para impresión', 'name' => 'Compra de papel para impresión'],
        ['id' => 'Mantenimiento de impresora o fotocopiadora', 'name' => 'Mantenimiento de impresora o fotocopiadora'],
        ['id' => 'Pago de envío o transporte', 'name' => 'Pago de envío o transporte'],
        ['id' => 'Viáticos o gestiones', 'name' => 'Viáticos o gestiones'],
        ['id' => 'Servicio técnico externo', 'name' => 'Servicio técnico externo'],
        ['id' => 'Retiro administrativo', 'name' => 'Retiro administrativo'],
        ['id' => 'Ajuste de caja', 'name' => 'Ajuste de caja'],
        ['id' => 'Otros', 'name' => 'Otros'],
    ];

    public array $denominacionesCordobas = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
    public array $denominacionesDolares = [100, 50, 20, 10, 5, 1];

    public array $conteoCordobas = [
        1000 => 0,
        500 => 0,
        200 => 0,
        100 => 0,
        50 => 0,
        20 => 0,
        10 => 0,
        5 => 0,
        1 => 0,
    ];

    public array $conteoDolares = [
        100 => 0,
        50 => 0,
        20 => 0,
        10 => 0,
        5 => 0,
        1 => 0,
    ];

    public function mount(): void
    {
        $this->cargarTasaCambio();
        $this->cargarAperturaAbierta();
        $this->cargarAbonosCreditoHoy();
        $this->cargarPagosVentaHoy();
        $this->cargarEgresosCaja();
    }

    private function usuarioActualId(): ?int
    {
        $usuario = auth()->user();

        if (! $usuario) {
            return null;
        }

        return (int) ($usuario->Id_Usuario ?? $usuario->getKey());
    }

    private function notificar(string $mensaje, string $tipo = 'success'): void
    {
        $this->toast(
            type: $tipo,
            title: $mensaje,
            position: 'toast-top toast-end',
            timeout: 3500
        );
    }

    private function limpiarTotalesVenta(): void
    {
        $this->totalVentaCordobas = 0;
        $this->totalVentaDolares = 0;
    }

    private function limpiarTotalesEgreso(): void
    {
        $this->totalEgresoCordobas = 0;
        $this->totalEgresoDolares = 0;
    }

    private function limpiarFormularioEgreso(): void
    {
        $this->monedaEgreso = 'cordoba';
        $this->montoEgresoCordobas = '';
        $this->montoEgresoDolares = '';
        $this->motivoEgreso = '';
        $this->descripcionEgreso = '';
    }

    private function reiniciarConteos(): void
    {
        foreach ($this->denominacionesCordobas as $denominacion) {
            $this->conteoCordobas[$denominacion] = 0;
        }

        foreach ($this->denominacionesDolares as $denominacion) {
            $this->conteoDolares[$denominacion] = 0;
        }
    }

    private function conteosMonedasValidos(): bool
    {
        foreach ($this->conteoCordobas as $cantidad) {
            if (! is_numeric($cantidad) || (float) $cantidad < 0) {
                return false;
            }
        }

        foreach ($this->conteoDolares as $cantidad) {
            if (! is_numeric($cantidad) || (float) $cantidad < 0) {
                return false;
            }
        }

        return true;
    }

    private function hayAlMenosUnaMonedaContada(): bool
    {
        foreach ($this->conteoCordobas as $cantidad) {
            if ((float) ($cantidad ?: 0) > 0) {
                return true;
            }
        }

        foreach ($this->conteoDolares as $cantidad) {
            if ((float) ($cantidad ?: 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function rangoCajaActual(): ?array
    {
        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId || ! $this->aperturaCajaId) {
            return null;
        }

        $apertura = AperturaCaja::query()
            ->where('Id_Apertura_Caja', $this->aperturaCajaId)
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado_Apertura', AperturaCaja::ABIERTO)
            ->whereDate('Fecha_Apertura', now()->toDateString())
            ->first();

        if (! $apertura) {
            return null;
        }

        return [
            $apertura->Fecha_Apertura,
            now(),
        ];
    }

    public function updatedMonedaEgreso(): void
    {
        $this->resetValidation([
            'montoEgresoCordobas',
            'montoEgresoDolares',
        ]);

        if ($this->monedaEgreso === 'cordoba') {
            $this->montoEgresoDolares = '';
        }

        if ($this->monedaEgreso === 'dolar') {
            $this->montoEgresoCordobas = '';
        }
    }

    public function mostrarMontoEgresoCordobas(): bool
    {
        return in_array($this->monedaEgreso, ['cordoba', 'ambas'], true);
    }

    public function mostrarMontoEgresoDolares(): bool
    {
        return in_array($this->monedaEgreso, ['dolar', 'ambas'], true);
    }

    public function cargarAperturaAbierta(): void
    {
        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId) {
            $this->cajaAbierta = false;
            $this->aperturaCajaId = null;
            $this->montoApertura = '0.00';
            $this->caja = '1';

            return;
        }

        $apertura = AperturaCaja::cajaAbiertaHoyPorUsuario($usuarioId);

        if (! $apertura) {
            $this->cajaAbierta = false;
            $this->aperturaCajaId = null;
            $this->montoApertura = '0.00';
            $this->caja = (string) AperturaCaja::siguienteNumeroCajaHoy();

            return;
        }

        $this->cajaAbierta = true;
        $this->aperturaCajaId = (int) $apertura->Id_Apertura_Caja;
        $this->montoApertura = number_format((float) $apertura->Monto_Apertura, 2, '.', '');
        $this->caja = (string) $apertura->Numero_Caja;
    }

    public function cargarTasaCambio(): void
    {
        $tasaActual = TasaCambio::actual();

        $valorActual = $tasaActual
            ? (float) $tasaActual->Valor_Cambio
            : 0;

        $this->tasaOficial = number_format($valorActual, 2, '.', '');
        $this->nuevaTasaOficial = $this->tasaOficial;
    }

    public function cargarAbonosCreditoHoy(): void
    {
        $usuarioId = $this->usuarioActualId();
        $rangoCaja = $this->rangoCajaActual();

        if (! $usuarioId || ! $rangoCaja) {
            $this->totalAbonoCordobas = 0;
            $this->totalAbonoDolares = 0;
            return;
        }

        [$inicioCaja, $finCaja] = $rangoCaja;

        $baseAbonos = DB::table('abono_credito')
            ->where('Id_Usuario', $usuarioId)
            ->whereDate('Fecha_Abono', now()->toDateString())
            ->whereBetween('Fecha_Abono', [$inicioCaja, $finCaja]);

        $this->totalAbonoCordobas = round((float) (clone $baseAbonos)
            ->whereRaw('UPPER(TRIM(Moneda)) = ?', ['NIO'])
            ->sum('Monto'), 2);

        $this->totalAbonoDolares = round((float) (clone $baseAbonos)
            ->whereRaw('UPPER(TRIM(Moneda)) = ?', ['USD'])
            ->sum('Monto'), 2);
    }

    public function cargarPagosVentaHoy(): void
    {
        $usuarioId = $this->usuarioActualId();
        $rangoCaja = $this->rangoCajaActual();

        if (! $usuarioId || ! $rangoCaja) {
            $this->limpiarTotalesVenta();
            return;
        }

        [$inicioCaja, $finCaja] = $rangoCaja;

        $tablaPagos = (new PagoVenta())->getTable();
        $tablaVentas = (new Venta())->getTable();

        $basePagosEfectivo = DB::table($tablaPagos . ' as pv')
            ->join($tablaVentas . ' as v', 'v.Id_Venta', '=', 'pv.Id_Venta')
            ->whereBetween('pv.Fecha_Pago', [$inicioCaja, $finCaja])
            ->where('pv.Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->where('v.Id_Usuario', $usuarioId)
            ->where('v.Estado', Venta::ESTADO_ACTIVA);

        $pagosCordobas = (float) (clone $basePagosEfectivo)
            ->where('pv.Moneda', PagoVenta::MONEDA_CORDOBA)
            ->sum('pv.Monto');

        $pagosDolares = (float) (clone $basePagosEfectivo)
            ->where('pv.Moneda', PagoVenta::MONEDA_DOLAR)
            ->sum('pv.Monto');

        $cambioEntregadoCordobas = (float) DB::query()
            ->fromSub(
                (clone $basePagosEfectivo)
                    ->select([
                        'v.Id_Venta',
                        DB::raw('COALESCE(MAX(v.Cambio_Entregado_Cordobas), 0) as cambio_entregado_cordobas'),
                    ])
                    ->groupBy('v.Id_Venta'),
                'ventas_efectivo'
            )
            ->sum('cambio_entregado_cordobas');

        $this->totalVentaCordobas = round($pagosCordobas - $cambioEntregadoCordobas, 2);
        $this->totalVentaDolares = round($pagosDolares, 2);
    }

    public function cargarEgresosCaja(): void
    {
        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId || ! $this->aperturaCajaId) {
            $this->limpiarTotalesEgreso();
            return;
        }

        $egresosCaja = Egresos::query()
            ->deApertura($this->aperturaCajaId)
            ->deUsuario($usuarioId);

        $this->totalEgresoCordobas = round((float) (clone $egresosCaja)->sum('Monto_Egresado_Cordoba'), 2);
        $this->totalEgresoDolares = round((float) (clone $egresosCaja)->sum('Monto_Egresado_Dolar'), 2);
    }

    public function abrirModalCaja(): void
    {
        $this->resetValidation();

        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId) {
            $this->notificar('No se pudo identificar al usuario en sesión.', 'error');
            return;
        }

        $aperturaUsuario = AperturaCaja::cajaAbiertaHoyPorUsuario($usuarioId);

        if ($aperturaUsuario) {
            $this->cargarAperturaAbierta();

            $this->notificar('Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.', 'warning');
            return;
        }

        $this->cargarTasaCambio();

        $this->caja = (string) AperturaCaja::siguienteNumeroCajaHoy();
        $this->montoApertura = '';
        $this->tasaCambioApertura = '';

        $this->abrirCajaModal = true;
    }

    public function cerrarModalCaja(): void
    {
        $this->resetValidation();

        $this->tasaCambioApertura = '';
        $this->abrirCajaModal = false;
    }

    public function abrirModalEgreso(): void
    {
        $this->resetValidation();

        $this->cargarAperturaAbierta();
        $this->cargarAbonosCreditoHoy();
        $this->cargarPagosVentaHoy();
        $this->cargarEgresosCaja();

        if (! $this->cajaAbierta || ! $this->aperturaCajaId) {
            $this->notificar('Debes abrir una caja antes de registrar egresos.', 'error');
            return;
        }

        $this->limpiarFormularioEgreso();

        $this->registrarEgresoModal = true;
    }

    public function cerrarModalEgreso(): void
    {
        $this->resetValidation();
        $this->registrarEgresoModal = false;
    }

    public function abrirModalTasa(): void
    {
        $this->resetValidation();
        $this->cargarTasaCambio();
        $this->modificarTasaModal = true;
    }

    public function cerrarModalTasa(): void
    {
        $this->resetValidation();
        $this->modificarTasaModal = false;
    }

    public function guardarApertura(): void
    {
        $this->validate([
            'montoApertura' => ['required', 'numeric', 'min:0.01'],
            'tasaCambioApertura' => ['nullable', 'numeric', 'min:0.01'],
        ], [
            'montoApertura.required' => 'El monto de apertura es obligatorio.',
            'montoApertura.numeric' => 'El monto de apertura debe ser numérico.',
            'montoApertura.min' => 'El monto de apertura debe ser mayor a C$ 0.00.',

            'tasaCambioApertura.numeric' => 'La tasa de cambio debe ser numérica.',
            'tasaCambioApertura.min' => 'La tasa de cambio debe ser mayor a 0.',
        ]);

        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId) {
            $this->addError('montoApertura', 'No se pudo identificar al usuario en sesión.');
            return;
        }

        $tasaCambioApertura = trim($this->tasaCambioApertura);
        $actualizoTasaCambio = $tasaCambioApertura !== '';

        try {
            DB::transaction(function () use ($usuarioId, $tasaCambioApertura, $actualizoTasaCambio) {
                $aperturaUsuario = AperturaCaja::query()
                    ->abierta()
                    ->deHoy()
                    ->where('Id_Usuario', $usuarioId)
                    ->lockForUpdate()
                    ->first();

                if ($aperturaUsuario) {
                    throw new RuntimeException('Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.');
                }

                $ultimaCajaHoy = AperturaCaja::query()
                    ->deHoy()
                    ->lockForUpdate()
                    ->orderByDesc('Numero_Caja')
                    ->orderByDesc('Id_Apertura_Caja')
                    ->first();

                $numeroCaja = $ultimaCajaHoy
                    ? ((int) $ultimaCajaHoy->Numero_Caja + 1)
                    : 1;

                if ($actualizoTasaCambio) {
                    TasaCambio::create([
                        'Valor_Cambio' => number_format((float) $tasaCambioApertura, 2, '.', ''),
                    ]);
                }

                $apertura = AperturaCaja::create([
                    'Id_Usuario' => $usuarioId,
                    'Numero_Caja' => $numeroCaja,
                    'Monto_Apertura' => number_format((float) $this->montoApertura, 2, '.', ''),
                    'Fecha_Apertura' => now(),
                    'Estado_Apertura' => AperturaCaja::ABIERTO,
                ]);

                $this->aperturaCajaId = (int) $apertura->Id_Apertura_Caja;
                $this->caja = (string) $apertura->Numero_Caja;
                $this->montoApertura = number_format((float) $apertura->Monto_Apertura, 2, '.', '');
                $this->cajaAbierta = true;
            });

            $this->abrirCajaModal = false;
            $this->tasaCambioApertura = '';

            $this->cargarTasaCambio();
            $this->cargarAbonosCreditoHoy();
            $this->cargarPagosVentaHoy();
            $this->cargarEgresosCaja();

            $mensaje = $actualizoTasaCambio
                ? 'Caja abierta correctamente y tasa de cambio actualizada.'
                : 'Caja abierta correctamente.';

            $this->notificar($mensaje, 'success');
        } catch (RuntimeException $e) {
            $this->abrirCajaModal = false;
            $this->tasaCambioApertura = '';

            $this->cargarAperturaAbierta();

            $this->notificar($e->getMessage(), 'warning');
        } catch (Throwable $e) {
            report($e);

            $this->abrirCajaModal = false;
            $this->tasaCambioApertura = '';

            $this->cargarAperturaAbierta();

            $this->notificar('Ocurrió un error al abrir la caja.', 'error');
        }
    }

    public function guardarTasaCambio(): void
    {
        $this->validate([
            'nuevaTasaOficial' => ['required', 'numeric', 'min:0.01'],
        ], [
            'nuevaTasaOficial.required' => 'La tasa de cambio es obligatoria.',
            'nuevaTasaOficial.numeric' => 'La tasa de cambio debe ser numérica.',
            'nuevaTasaOficial.min' => 'La tasa de cambio debe ser mayor a 0.',
        ]);

        TasaCambio::create([
            'Valor_Cambio' => number_format((float) $this->nuevaTasaOficial, 2, '.', ''),
        ]);

        $this->cargarTasaCambio();

        $this->modificarTasaModal = false;

        $this->notificar('Tasa de cambio actualizada correctamente.', 'success');
    }

    public function guardarEgreso(): void
    {
        $this->resetValidation();

        $this->validate([
            'monedaEgreso' => ['required', 'in:cordoba,dolar,ambas'],
            'montoEgresoCordobas' => ['nullable', 'numeric', 'min:0'],
            'montoEgresoDolares' => ['nullable', 'numeric', 'min:0'],
            'motivoEgreso' => ['required', 'string', 'max:150'],
            'descripcionEgreso' => ['required', 'string', 'max:1000'],
        ], [
            'monedaEgreso.required' => 'La moneda del egreso es obligatoria.',
            'monedaEgreso.in' => 'La moneda seleccionada no es válida.',

            'montoEgresoCordobas.numeric' => 'El monto en córdobas debe ser numérico.',
            'montoEgresoCordobas.min' => 'El monto en córdobas no puede ser negativo.',

            'montoEgresoDolares.numeric' => 'El monto en dólares debe ser numérico.',
            'montoEgresoDolares.min' => 'El monto en dólares no puede ser negativo.',

            'motivoEgreso.required' => 'El motivo del egreso es obligatorio.',
            'motivoEgreso.max' => 'El motivo no puede superar los 150 caracteres.',

            'descripcionEgreso.required' => 'La descripción del egreso es obligatoria.',
            'descripcionEgreso.max' => 'La descripción no puede superar los 1000 caracteres.',
        ]);

        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId || ! $this->aperturaCajaId) {
            $this->addError('motivoEgreso', 'Debes tener una caja abierta para registrar egresos.');
            return;
        }

        $montoCordobas = round((float) ($this->montoEgresoCordobas ?: 0), 2);
        $montoDolares = round((float) ($this->montoEgresoDolares ?: 0), 2);

        if (in_array($this->monedaEgreso, ['cordoba', 'ambas'], true) && $montoCordobas <= 0) {
            $this->addError('montoEgresoCordobas', 'Debes ingresar un monto en córdobas mayor a 0.');
            return;
        }

        if (in_array($this->monedaEgreso, ['dolar', 'ambas'], true) && $montoDolares <= 0) {
            $this->addError('montoEgresoDolares', 'Debes ingresar un monto en dólares mayor a 0.');
            return;
        }

        if ($this->monedaEgreso === 'cordoba') {
            $montoDolares = 0;
        }

        if ($this->monedaEgreso === 'dolar') {
            $montoCordobas = 0;
        }

        if ($montoCordobas > $this->disponibleEgresoCordobas()) {
            $this->addError('montoEgresoCordobas', 'El monto en córdobas supera el disponible de la caja.');
            return;
        }

        if ($montoDolares > $this->disponibleEgresoDolares()) {
            $this->addError('montoEgresoDolares', 'El monto en dólares supera el disponible de la caja.');
            return;
        }

        try {
            Egresos::create([
                'Id_Apertura_Caja' => $this->aperturaCajaId,
                'Id_Usuario' => $usuarioId,
                'Monto_Egresado_Cordoba' => $montoCordobas > 0
                    ? number_format($montoCordobas, 2, '.', '')
                    : null,
                'Monto_Egresado_Dolar' => $montoDolares > 0
                    ? number_format($montoDolares, 2, '.', '')
                    : null,
                'Motivo_Egreso' => $this->motivoEgreso,
                'Descripcion_Egreso' => trim($this->descripcionEgreso),
                'Fecha_Egreso' => now(),
            ]);

            $this->registrarEgresoModal = false;

            $this->limpiarFormularioEgreso();
            $this->cargarEgresosCaja();

            $this->notificar('Egreso registrado correctamente.', 'success');
        } catch (Throwable $e) {
            report($e);

            $this->notificar('Ocurrió un error al registrar el egreso.', 'error');
        }
    }

    public function cerrarCaja(): void
    {
        $this->resetValidation();

        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId || ! $this->aperturaCajaId) {
            $this->notificar('No hay una caja abierta para cerrar.', 'error');
            return;
        }

        if (! $this->conteosMonedasValidos()) {
            $this->notificar('Las cantidades de billetes y monedas deben ser números mayores o iguales a 0.', 'error');
            return;
        }

        if (! $this->hayAlMenosUnaMonedaContada()) {
            $this->notificar('Debes ingresar al menos una cantidad en el conteo de córdobas o dólares antes de cerrar la caja.', 'warning');
            return;
        }

        $this->cargarAperturaAbierta();
        $this->cargarAbonosCreditoHoy();
        $this->cargarPagosVentaHoy();
        $this->cargarEgresosCaja();

        if (! $this->cajaAbierta || ! $this->aperturaCajaId) {
            $this->notificar('No hay una caja abierta para cerrar.', 'error');
            return;
        }

        try {
            DB::transaction(function () use ($usuarioId) {
                $apertura = AperturaCaja::query()
                    ->where('Id_Apertura_Caja', $this->aperturaCajaId)
                    ->where('Id_Usuario', $usuarioId)
                    ->where('Estado_Apertura', AperturaCaja::ABIERTO)
                    ->lockForUpdate()
                    ->first();

                if (! $apertura) {
                    throw new RuntimeException('La caja ya fue cerrada o no pertenece al usuario actual.');
                }

                $arqueoExistente = ArqueoCaja::query()
                    ->where('Id_Apertura_Caja', $this->aperturaCajaId)
                    ->lockForUpdate()
                    ->first();

                if ($arqueoExistente) {
                    throw new RuntimeException('Esta apertura de caja ya tiene un arqueo registrado.');
                }

                $faltanteCordoba = round($this->faltanteCordobas(), 2);
                $faltanteDolar = round($this->faltanteDolares(), 2);
                $sobranteCordoba = round($this->sobranteCordobas(), 2);
                $sobranteDolar = round($this->sobranteDolares(), 2);

                $estadoArqueo = DetalleArqueo::ESTADO_CUADRADO;

                if ($faltanteCordoba > 0 || $faltanteDolar > 0) {
                    $estadoArqueo = DetalleArqueo::ESTADO_FALTANTE;
                }

                if ($sobranteCordoba > 0 || $sobranteDolar > 0) {
                    $estadoArqueo = DetalleArqueo::ESTADO_SOBRANTE;
                }

                if (
                    ($faltanteCordoba > 0 || $faltanteDolar > 0) &&
                    ($sobranteCordoba > 0 || $sobranteDolar > 0)
                ) {
                    $estadoArqueo = DetalleArqueo::ESTADO_DIFERENCIA;
                }

                $arqueo = ArqueoCaja::create([
                    'Id_Usuario' => $usuarioId,
                    'Id_Apertura_Caja' => $this->aperturaCajaId,
                    'Total_Caja_Cordoba' => number_format($this->totalCordobas(), 2, '.', ''),
                    'Total_Caja_Dolar' => number_format($this->totalDolares(), 2, '.', ''),
                    'Fecha_Arqueo' => now(),
                ]);

                DetalleArqueo::create([
                    'Id_Arqueo' => $arqueo->Id_Arqueo,
                    'Faltante_Cordoba' => number_format($faltanteCordoba, 2, '.', ''),
                    'Faltante_Dolar' => number_format($faltanteDolar, 2, '.', ''),
                    'Sobrante_Cordoba' => number_format($sobranteCordoba, 2, '.', ''),
                    'Sobrante_Dolar' => number_format($sobranteDolar, 2, '.', ''),
                    'Cantidad_Egresada_Cordoba' => number_format($this->totalEgresoCordobas, 2, '.', ''),
                    'Cantidad_Egresada_Dolar' => number_format($this->totalEgresoDolares, 2, '.', ''),
                    'Estado_Arqueo' => $estadoArqueo,
                ]);

                AperturaCaja::query()
                    ->where('Id_Apertura_Caja', $apertura->Id_Apertura_Caja)
                    ->update([
                        'Estado_Apertura' => AperturaCaja::CERRADO,
                    ]);
            });

            $this->cajaAbierta = false;
            $this->aperturaCajaId = null;
            $this->montoApertura = '0.00';
            $this->caja = (string) AperturaCaja::siguienteNumeroCajaHoy();

            $this->limpiarTotalesVenta();
            $this->limpiarTotalesEgreso();
            $this->reiniciarConteos();

            $this->totalAbonoCordobas = 0;
            $this->totalAbonoDolares = 0;

            $this->notificar('Caja cerrada correctamente.', 'success');
        } catch (RuntimeException $e) {
            $this->notificar($e->getMessage(), 'warning');
        } catch (Throwable $e) {
            report($e);

            $this->notificar('Ocurrió un error al cerrar la caja.', 'error');
        }
    }

    public function subtotalCordoba(int $denominacion): float
    {
        return ((int) ($this->conteoCordobas[$denominacion] ?? 0)) * $denominacion;
    }

    public function subtotalDolar(int $denominacion): float
    {
        return ((int) ($this->conteoDolares[$denominacion] ?? 0)) * $denominacion;
    }

    public function totalCordobas(): float
    {
        $total = 0;

        foreach ($this->denominacionesCordobas as $denominacion) {
            $total += $this->subtotalCordoba($denominacion);
        }

        return $total;
    }

    public function totalDolares(): float
    {
        $total = 0;

        foreach ($this->denominacionesDolares as $denominacion) {
            $total += $this->subtotalDolar($denominacion);
        }

        return $total;
    }

    public function totalIngresosCordobas(): float
    {
        return (float) $this->montoApertura
            + (float) $this->totalVentaCordobas
            + (float) $this->totalAbonoCordobas;
    }

    public function totalIngresosDolares(): float
    {
        return (float) $this->totalVentaDolares
            + (float) $this->totalAbonoDolares;
    }

    public function totalEsperadoCordobas(): float
    {
        return round($this->totalIngresosCordobas() - (float) $this->totalEgresoCordobas, 2);
    }

    public function totalEsperadoDolares(): float
    {
        return round($this->totalIngresosDolares() - (float) $this->totalEgresoDolares, 2);
    }

    public function disponibleEgresoCordobas(): float
    {
        return max(0, $this->totalEsperadoCordobas());
    }

    public function disponibleEgresoDolares(): float
    {
        return max(0, $this->totalEsperadoDolares());
    }

    public function faltanteCordobas(): float
    {
        $faltante = $this->totalEsperadoCordobas() - $this->totalCordobas();

        return $faltante > 0 ? $faltante : 0;
    }

    public function sobranteCordobas(): float
    {
        $sobrante = $this->totalCordobas() - $this->totalEsperadoCordobas();

        return $sobrante > 0 ? $sobrante : 0;
    }

    public function faltanteDolares(): float
    {
        $faltante = $this->totalEsperadoDolares() - $this->totalDolares();

        return $faltante > 0 ? $faltante : 0;
    }

    public function sobranteDolares(): float
    {
        $sobrante = $this->totalDolares() - $this->totalEsperadoDolares();

        return $sobrante > 0 ? $sobrante : 0;
    }

    public function detalleDiferenciaCordobas(): array
    {
        $faltante = $this->faltanteCordobas();
        $sobrante = $this->sobranteCordobas();

        if ($faltante > 0) {
            return [
                'label' => 'Diferencia en C$',
                'valor' => 'C$ ' . $this->formatear($faltante),
                'tipo' => 'faltante',
                'monto' => $faltante,
            ];
        }

        if ($sobrante > 0) {
            return [
                'label' => 'Diferencia en C$',
                'valor' => 'C$ ' . $this->formatear($sobrante),
                'tipo' => 'sobrante',
                'monto' => $sobrante,
            ];
        }

        return [
            'label' => 'Diferencia en C$',
            'valor' => 'C$ 0.00',
            'tipo' => 'cuadrado',
            'monto' => 0,
        ];
    }

    public function detalleDiferenciaDolares(): array
    {
        $faltante = $this->faltanteDolares();
        $sobrante = $this->sobranteDolares();

        if ($faltante > 0) {
            return [
                'label' => 'Diferencia en $',
                'valor' => '$ ' . $this->formatear($faltante),
                'tipo' => 'faltante',
                'monto' => $faltante,
            ];
        }

        if ($sobrante > 0) {
            return [
                'label' => 'Diferencia en $',
                'valor' => '$ ' . $this->formatear($sobrante),
                'tipo' => 'sobrante',
                'monto' => $sobrante,
            ];
        }

        return [
            'label' => 'Diferencia en $',
            'valor' => '$ 0.00',
            'tipo' => 'cuadrado',
            'monto' => 0,
        ];
    }

    public function formatear(float|int|string $valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }

    public function detallesCaja(): array
    {
        return [
            ['label' => 'Caja número', 'valor' => '#' . $this->caja],
            ['label' => 'Fondo inicial', 'valor' => 'C$ ' . $this->formatear($this->montoApertura)],

            ['label' => 'Total ventas C$', 'valor' => 'C$ ' . $this->formatear($this->totalVentaCordobas)],
            ['label' => 'Total ventas $', 'valor' => '$ ' . $this->formatear($this->totalVentaDolares)],

            ['label' => 'Total abono C$', 'valor' => 'C$ ' . $this->formatear($this->totalAbonoCordobas)],
            ['label' => 'Total abono $', 'valor' => '$ ' . $this->formatear($this->totalAbonoDolares)],

            ['label' => 'Total egresos C$', 'valor' => 'C$ ' . $this->formatear($this->totalEgresoCordobas)],
            ['label' => 'Total egresos $', 'valor' => '$ ' . $this->formatear($this->totalEgresoDolares)],

            ['label' => 'Total esperado C$', 'valor' => 'C$ ' . $this->formatear($this->totalEsperadoCordobas())],
            $this->detalleDiferenciaCordobas(),

            ['label' => 'Total esperado $', 'valor' => '$ ' . $this->formatear($this->totalEsperadoDolares())],
            $this->detalleDiferenciaDolares(),
        ];
    }
};

?>

<div class="flex min-h-screen w-full flex-col gap-6 bg-[#F0F3F7] p-4 md:p-6">
    @php
        $cardClass = 'rounded-2xl border border-[#D7E4F3] bg-white shadow-sm';
        $softCardClass = 'rounded-xl border border-[#D7E4F3] bg-[#F8FAFC]';
        $inputReadonlyClass = 'w-full rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42]';
        $inputEditableClass = 'w-full rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42]';

        $modalCloseStableClass = 'backdrop-blur-sm [&_.btn-circle]:!bg-[#F0F3F7] [&_.btn-circle]:!text-[#1A2B42] [&_.btn-circle]:!border-[#D7E4F3] [&_.btn-circle:hover]:!bg-[#F0F3F7] [&_.btn-circle:hover]:!text-[#1A2B42] [&_.btn-circle:hover]:!border-[#D7E4F3] [&_.btn-circle:focus]:!bg-[#F0F3F7] [&_.btn-circle:focus]:!text-[#1A2B42] [&_.btn-circle:focus]:!border-[#D7E4F3] [&_.btn-circle:active]:!bg-[#F0F3F7] [&_.btn-circle:active]:!text-[#1A2B42] [&_.btn-circle:active]:!border-[#D7E4F3]';
    @endphp

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        <div class="{{ $cardClass }} flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4] shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5M6 15h.008v.008H6V15Zm3 0h.008v.008H9V15Z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-[#1A2B42] md:text-3xl">Arqueo de caja</h1>
                    <p class="text-sm text-[#5F6B7A]">Registro de efectivo y movimientos de la caja</p>
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
                <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Caja
                    </label>

                    <x-input
                        wire:model="caja"
                        readonly
                        prefix="#"
                        class="{{ $inputReadonlyClass }}"
                    />
                </div>

                <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Tasa de cambio
                    </label>

                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1">
                            <x-input
                                wire:model="tasaOficial"
                                readonly
                                prefix="TC"
                                class="{{ $inputReadonlyClass }}"
                            />
                        </div>

                        <x-button
                            icon="o-pencil-square"
                            wire:click="abrirModalTasa"
                            title="Modificar tasa de cambio"
                            class="h-12 min-h-0 rounded-xl border border-[#D7E4F3] bg-white text-[#0B6FE4] hover:bg-[#EAF2FB]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-8.25A2.25 2.25 0 0 0 17.25 3.75h-10.5A2.25 2.25 0 0 0 4.5 6v12A2.25 2.25 0 0 0 6.75 20.25h7.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h3.75m0 0V15m0 3.75L15 13.5" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Detalles de caja</h2>
                </div>

                <div class="space-y-3">
                    @foreach ($this->detallesCaja() as $detalle)
                        @php
                            $tipoDetalle = $detalle['tipo'] ?? 'normal';
                            $montoDetalle = (float) ($detalle['monto'] ?? 0);

                            $valorClass = 'text-sm font-bold text-[#1A2B42]';
                            $filaClass = 'flex items-center justify-between gap-3 rounded-xl bg-[#F8FAFC] px-4 py-3';

                            if ($tipoDetalle === 'faltante' && $montoDetalle > 0) {
                                $valorClass = 'text-sm font-extrabold text-red-600';
                                $filaClass = 'flex items-center justify-between gap-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3';
                            }

                            if ($tipoDetalle === 'sobrante' && $montoDetalle > 0) {
                                $valorClass = 'text-sm font-extrabold text-green-600';
                                $filaClass = 'flex items-center justify-between gap-3 rounded-xl border border-green-100 bg-green-50 px-4 py-3';
                            }
                        @endphp

                        <div class="{{ $filaClass }}">
                            <span class="text-sm font-medium text-[#5F6B7A]">
                                {{ $detalle['label'] }}
                            </span>

                            <span class="{{ $valorClass }} text-right">
                                {{ $detalle['valor'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Efectivo (C$)</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($denominacionesCordobas as $denominacion)
                        <div class="{{ $softCardClass }} p-3">
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                C${{ $denominacion }}
                            </span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                prefix="C$"
                                wire:model.live="conteoCordobas.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: C$ {{ $this->formatear($this->subtotalCordoba($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Efectivo contado C$</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">
                        C$ {{ $this->formatear($this->totalCordobas()) }}
                    </span>
                </div>
            </div>

            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Efectivo ($)</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($denominacionesDolares as $denominacion)
                        <div class="{{ $softCardClass }} p-3">
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                ${{ $denominacion }}
                            </span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                prefix="$"
                                wire:model.live="conteoDolares.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: $ {{ $this->formatear($this->subtotalDolar($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Efectivo contado $</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">
                        $ {{ $this->formatear($this->totalDolares()) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button
                label="Registrar egreso"
                wire:click="abrirModalEgreso"
                class="border-0 bg-[#1A2B42] text-white hover:opacity-95"
            />

            <x-button
                label="Abrir caja"
                wire:click="abrirModalCaja"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F8FAFC]"
            />

            <x-button
                label="Cerrar caja"
                wire:click="cerrarCaja"
                class="border-0 bg-[#0B6FE4] text-white hover:opacity-95"
            />
        </div>
    </div>

    <x-modal
        wire:model="abrirCajaModal"
        class="{{ $modalCloseStableClass }}"
        box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-5 flex items-center justify-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5m-3.75-3.75h7.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-bold text-[#0B6FE4]">Apertura de Caja</h2>
                    <p class="text-sm text-[#5F6B7A]">Ingresa el monto inicial y la tasa de cambio del día</p>
                </div>
            </div>

            <x-form wire:submit="guardarApertura" no-separator>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Número de caja
                        </label>

                        <x-input
                            wire:model="caja"
                            readonly
                            prefix="#"
                            class="{{ $inputReadonlyClass }}"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Monto de apertura
                        </label>

                        <x-input
                            type="number"
                            step="0.01"
                            min="0.01"
                            wire:model="montoApertura"
                            placeholder="0.00"
                            prefix="C$"
                            class="{{ $inputEditableClass }}"
                        />

                        @error('montoApertura')
                            <span class="mt-2 block text-sm font-semibold text-red-600">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Tasa actual
                                </label>

                                <x-input
                                    wire:model="tasaOficial"
                                    readonly
                                    prefix="TC"
                                    class="{{ $inputReadonlyClass }}"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Nueva tasa de cambio opcional
                                </label>

                                <x-input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="tasaCambioApertura"
                                    placeholder="Dejar vacío para no modificar"
                                    prefix="TC"
                                    class="{{ $inputEditableClass }}"
                                />

                                @error('tasaCambioApertura')
                                    <span class="mt-2 block text-sm font-semibold text-red-600">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancelar"
                        type="button"
                        wire:click="cerrarModalCaja"
                        class="bg-[#6B7280] text-white"
                    />

                    <x-button
                        label="Abrir caja"
                        type="submit"
                        spinner="guardarApertura"
                        class="bg-[#16A34A] text-white"
                    />
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>

    <x-modal
        wire:model="modificarTasaModal"
        class="{{ $modalCloseStableClass }}"
        box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-5 flex items-center justify-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                    </svg>
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-bold text-[#0B6FE4]">Modificar tasa de cambio</h2>
                    <p class="text-sm text-[#5F6B7A]">Actualiza la tasa oficial utilizada por la caja</p>
                </div>
            </div>

            <x-form wire:submit="guardarTasaCambio" no-separator>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nueva tasa de cambio
                    </label>

                    <x-input
                        type="number"
                        step="0.01"
                        min="0.01"
                        wire:model="nuevaTasaOficial"
                        placeholder="0.00"
                        prefix="TC"
                        class="{{ $inputEditableClass }}"
                    />

                    @error('nuevaTasaOficial')
                        <span class="mt-2 block text-sm font-semibold text-red-600">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancelar"
                        type="button"
                        wire:click="cerrarModalTasa"
                        class="bg-[#6B7280] text-white"
                    />

                    <x-button
                        label="Guardar tasa"
                        type="submit"
                        spinner="guardarTasaCambio"
                        class="bg-[#0B6FE4] text-white"
                    />
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>

    <x-modal
        wire:model="registrarEgresoModal"
        class="{{ $modalCloseStableClass }}"
        box-class="max-w-4xl rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Egresos de Caja</h2>
                    <p class="text-sm text-[#5F6B7A]">Registra egresos de una moneda específica o ambas</p>
                </div>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-5">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-[#1A2B42]">Nuevo egreso</h3>
                    <p class="text-sm text-[#5F6B7A]">
                        Selecciona la moneda del egreso y registra la descripción obligatoria.
                    </p>
                </div>

                <x-form wire:submit="guardarEgreso" no-separator>
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Disponible en caja C$
                                </label>

                                <x-input
                                    :value="$this->formatear($this->disponibleEgresoCordobas())"
                                    readonly
                                    prefix="C$"
                                    class="{{ $inputReadonlyClass }}"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Disponible en caja $
                                </label>

                                <x-input
                                    :value="$this->formatear($this->disponibleEgresoDolares())"
                                    readonly
                                    prefix="$"
                                    class="{{ $inputReadonlyClass }}"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Moneda del egreso
                            </label>

                            <x-select
                                wire:model.live="monedaEgreso"
                                :options="$monedasEgreso"
                                class="{{ $inputEditableClass }}"
                            />

                            @error('monedaEgreso')
                                <span class="mt-2 block text-sm font-semibold text-red-600">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            @if ($this->mostrarMontoEgresoCordobas())
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                        Monto a egresar en córdobas
                                    </label>

                                    <x-input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model="montoEgresoCordobas"
                                        placeholder="0.00"
                                        prefix="C$"
                                        class="{{ $inputEditableClass }}"
                                    />

                                    @error('montoEgresoCordobas')
                                        <span class="mt-2 block text-sm font-semibold text-red-600">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            @endif

                            @if ($this->mostrarMontoEgresoDolares())
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                        Monto a egresar en dólares
                                    </label>

                                    <x-input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model="montoEgresoDolares"
                                        placeholder="0.00"
                                        prefix="$"
                                        class="{{ $inputEditableClass }}"
                                    />

                                    @error('montoEgresoDolares')
                                        <span class="mt-2 block text-sm font-semibold text-red-600">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Motivo del egreso
                            </label>

                            <x-select
                                wire:model="motivoEgreso"
                                :options="$motivosEgreso"
                                placeholder="Seleccione un motivo"
                                class="{{ $inputEditableClass }}"
                            />

                            @error('motivoEgreso')
                                <span class="mt-2 block text-sm font-semibold text-red-600">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Descripción del egreso
                            </label>

                            <x-textarea
                                wire:model="descripcionEgreso"
                                rows="4"
                                placeholder="Detalle obligatorio del egreso..."
                                class="rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#7B8794]"
                            />

                            @error('descripcionEgreso')
                                <span class="mt-2 block text-sm font-semibold text-red-600">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <x-slot:actions>
                        <x-button
                            label="Volver"
                            type="button"
                            wire:click="cerrarModalEgreso"
                            class="bg-[#6B7280] text-white"
                        />

                        <x-button
                            label="Guardar egreso"
                            type="submit"
                            spinner="guardarEgreso"
                            class="bg-[#0B6FE4] text-white"
                        />
                    </x-slot:actions>
                </x-form>
            </div>
        </div>
    </x-modal>
</div>