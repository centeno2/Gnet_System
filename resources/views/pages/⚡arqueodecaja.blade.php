<?php

use App\Models\AperturaCaja;
use App\Models\ArqueoCaja;
use App\Models\DetalleArqueo;
use App\Models\Egresos;
use App\Models\PagoVenta;
use App\Models\TasaCambio;
use App\Models\Venta;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public bool $abrirCajaModal = false;
    public bool $registrarEgresoModal = false;
    public bool $modificarTasaModal = false;
    public bool $modalReporteCierreCaja = false;

    public string $caja = '1';
    public string $reporteCierreCajaUrl = '';
    public ?int $ultimoArqueoCajaId = null;

    public bool $cajaAbierta = false;
    public ?int $aperturaCajaId = null;

    public string $montoApertura = '0.00';
    public string $montoAperturaFormulario = '';
    public string $tasaOficial = '0.00';
    public string $nuevaTasaOficial = '';
    public string $tasaCambioApertura = '';

    public string $monedaEgreso = 'cordoba';
    public string $montoEgresoCordobas = '';
    public string $montoEgresoDolares = '';
    public string $motivoEgreso = '';
    public string $descripcionEgreso = '';

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

    public array $denominacionesCordobas = [
        '1000' => 1000,
        '500' => 500,
        '200' => 200,
        '100' => 100,
        '50' => 50,
        '20' => 20,
        '10' => 10,
        '5' => 5,
        '1' => 1,
        '0_50' => 0.50,
        '0_25' => 0.25,
    ];

    public array $conteoCordobas = [
        '1000' => 0,
        '500' => 0,
        '200' => 0,
        '100' => 0,
        '50' => 0,
        '20' => 0,
        '10' => 0,
        '5' => 0,
        '1' => 0,
        '0_50' => 0,
        '0_25' => 0,
    ];

    public array $denominacionesDolares = [
    100,    
    50,
    20,
    10, 
    5, 
    1
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
        $this->cerrarCajasAbiertasAnterioresAHoy();

        $this->cargarTasaCambio();
        $this->cargarAperturaAbierta();
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

    private function transaccion(callable $callback): mixed
    {
        return (new AperturaCaja())->getConnection()->transaction($callback);
    }

    private function cerrarCajasAbiertasAnterioresAHoy(): int
    {
        return $this->cerrarCajasAutomaticamente(false);
    }

    private function cerrarCajasAutomaticamente(bool $incluirHoy = false, ?int $soloUsuarioId = null): int
    {
        try {
            return $this->transaccion(function () use ($incluirHoy, $soloUsuarioId) {
                $aperturas = AperturaCaja::query()
                    ->where('Estado_Apertura', AperturaCaja::ABIERTO)
                    ->when(
                        $incluirHoy,
                        fn ($query) => $query->whereDate('Fecha_Apertura', '<=', now()->toDateString()),
                        fn ($query) => $query->whereDate('Fecha_Apertura', '<', now()->toDateString())
                    )
                    ->when($soloUsuarioId, fn ($query) => $query->where('Id_Usuario', $soloUsuarioId))
                    ->orderBy('Fecha_Apertura')
                    ->orderBy('Id_Apertura_Caja')
                    ->lockForUpdate()
                    ->get();

                $cerradas = 0;

                foreach ($aperturas as $apertura) {
                    if (! $apertura instanceof AperturaCaja) {
                        continue;
                    }

                    if ($this->crearArqueoTecnicoYCerrarCaja($apertura)) {
                        $cerradas++;
                    }
                }

                return $cerradas;
            });
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    private function crearArqueoTecnicoYCerrarCaja(AperturaCaja $apertura): bool
    {
        $arqueoExistente = ArqueoCaja::query()
            ->where('Id_Apertura_Caja', $apertura->Id_Apertura_Caja)
            ->lockForUpdate()
            ->first();

        if ($arqueoExistente) {
            AperturaCaja::query()
                ->where('Id_Apertura_Caja', $apertura->Id_Apertura_Caja)
                ->update([
                    'Estado_Apertura' => AperturaCaja::CERRADO,
                ]);

            return true;
        }

        $usuarioId = (int) $apertura->Id_Usuario;
        $inicioCaja = $apertura->Fecha_Apertura;
        $finCaja = now();

        $totales = $this->calcularTotalesCajaParaCierreAutomatico(
            usuarioId: $usuarioId,
            aperturaId: (int) $apertura->Id_Apertura_Caja,
            inicioCaja: $inicioCaja,
            finCaja: $finCaja,
            montoApertura: (float) $apertura->Monto_Apertura
        );

        $arqueo = ArqueoCaja::create([
            'Id_Usuario' => $usuarioId,
            'Id_Apertura_Caja' => $apertura->Id_Apertura_Caja,
            'Total_Caja_Cordoba' => number_format($totales['esperado_cordoba'], 2, '.', ''),
            'Total_Caja_Dolar' => number_format($totales['esperado_dolar'], 2, '.', ''),
            'Fecha_Arqueo' => $finCaja,
        ]);

        DetalleArqueo::create([
            'Id_Arqueo' => $arqueo->Id_Arqueo,
            'Faltante_Cordoba' => number_format(0, 2, '.', ''),
            'Faltante_Dolar' => number_format(0, 2, '.', ''),
            'Sobrante_Cordoba' => number_format(0, 2, '.', ''),
            'Sobrante_Dolar' => number_format(0, 2, '.', ''),
            'Cantidad_Egresada_Cordoba' => number_format($totales['egreso_cordoba'], 2, '.', ''),
            'Cantidad_Egresada_Dolar' => number_format($totales['egreso_dolar'], 2, '.', ''),
            'Estado_Arqueo' => DetalleArqueo::ESTADO_CUADRADO,
        ]);

        AperturaCaja::query()
            ->where('Id_Apertura_Caja', $apertura->Id_Apertura_Caja)
            ->update([
                'Estado_Apertura' => AperturaCaja::CERRADO,
            ]);

        return true;
    }

    private function calcularTotalesCajaParaCierreAutomatico(
        int $usuarioId,
        int $aperturaId,
        mixed $inicioCaja,
        mixed $finCaja,
        float $montoApertura
    ): array {
        $ventasActivasDelUsuario = Venta::query()
            ->select('Id_Venta')
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado', Venta::ESTADO_ACTIVA);

        $pagosEfectivoDelRango = PagoVenta::query()
            ->whereBetween('Fecha_Pago', [$inicioCaja, $finCaja])
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->whereIn('Id_Venta', clone $ventasActivasDelUsuario);

        $ventasCordobas = round((float) (clone $pagosEfectivoDelRango)
            ->where('Moneda', PagoVenta::MONEDA_CORDOBA)
            ->sum('Monto'), 2);

        $ventasDolares = round((float) (clone $pagosEfectivoDelRango)
            ->where('Moneda', PagoVenta::MONEDA_DOLAR)
            ->sum('Monto'), 2);

        $ventasConPagoEfectivo = PagoVenta::query()
            ->select('Id_Venta')
            ->whereBetween('Fecha_Pago', [$inicioCaja, $finCaja])
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->whereIn('Id_Venta', clone $ventasActivasDelUsuario)
            ->distinct();

        $cambioEntregadoCordobas = round((float) Venta::query()
            ->whereIn('Id_Venta', $ventasConPagoEfectivo)
            ->sum('Cambio_Entregado_Cordobas'), 2);

        $ventasCordobas = round($ventasCordobas - $cambioEntregadoCordobas, 2);

        $baseEgresos = Egresos::query()
            ->deApertura($aperturaId)
            ->deUsuario($usuarioId);

        $egresosCordobas = round((float) (clone $baseEgresos)
            ->sum('Monto_Egresado_Cordoba'), 2);

        $egresosDolares = round((float) (clone $baseEgresos)
            ->sum('Monto_Egresado_Dolar'), 2);

        $esperadoCordobas = round(
            $montoApertura
            + $ventasCordobas
            - $egresosCordobas,
            2
        );

        $esperadoDolares = round(
            $ventasDolares
            - $egresosDolares,
            2
        );

        return [
            'venta_cordoba' => max(0, $ventasCordobas),
            'venta_dolar' => max(0, $ventasDolares),
            'egreso_cordoba' => max(0, $egresosCordobas),
            'egreso_dolar' => max(0, $egresosDolares),
            'esperado_cordoba' => max(0, $esperadoCordobas),
            'esperado_dolar' => max(0, $esperadoDolares),
        ];
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

    private function limpiarFormularioApertura(bool $recargarCajaReal = false): void
    {
        $this->resetValidation([
            'montoAperturaFormulario',
            'tasaCambioApertura',
        ]);

        $this->montoAperturaFormulario = '';
        $this->tasaCambioApertura = '';

        if ($recargarCajaReal) {
            $this->cargarAperturaAbierta();
        }
    }

    private function reiniciarConteos(): void
    {
        foreach ($this->denominacionesCordobas as $clave => $valor) {
            $this->conteoCordobas[$clave] = 0;
        }

        foreach ($this->denominacionesDolares as $denominacion) {
            $this->conteoDolares[$denominacion] = 0;
        }
    }

    public function formatearDenominacionCordoba(string|int|float $valor): string
    {
        $numero = (float) $valor;

        if ($numero >= 1 && floor($numero) == $numero) {
            return number_format($numero, 0, '.', ',');
        }

        return number_format($numero, 2, '.', ',');
    }

    public function updatedConteoCordobas($valor, $denominacion): void
    {
        $denominacion = (string) $denominacion;

        if (! array_key_exists($denominacion, $this->denominacionesCordobas)) {
            return;
        }

        $limpio = preg_replace('/[^0-9]/', '', (string) $valor);

        $this->conteoCordobas[$denominacion] = $limpio === ''
            ? 0
            : (int) $limpio;
    }

    public function updatedConteoDolares($valor, $denominacion): void
    {
        $denominacion = (int) $denominacion;

        if (! in_array($denominacion, $this->denominacionesDolares, true)) {
            return;
        }

        $limpio = preg_replace('/[^0-9]/', '', (string) $valor);

        $this->conteoDolares[$denominacion] = $limpio === ''
            ? 0
            : (int) $limpio;
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

    public function updatedAbrirCajaModal(bool $abierto): void
    {
        if (! $abierto) {
            $this->limpiarFormularioApertura(true);
        }
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
        $this->cerrarCajasAbiertasAnterioresAHoy();

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

    public function cargarPagosVentaHoy(): void
    {
        $usuarioId = $this->usuarioActualId();
        $rangoCaja = $this->rangoCajaActual();

        if (! $usuarioId || ! $rangoCaja) {
            $this->limpiarTotalesVenta();
            return;
        }

        [$inicioCaja, $finCaja] = $rangoCaja;

        $ventasActivasDelUsuario = Venta::query()
            ->select('Id_Venta')
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado', Venta::ESTADO_ACTIVA);

        $basePagosEfectivo = PagoVenta::query()
            ->whereBetween('Fecha_Pago', [$inicioCaja, $finCaja])
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->whereIn('Id_Venta', clone $ventasActivasDelUsuario);

        $pagosCordobas = (float) (clone $basePagosEfectivo)
            ->where('Moneda', PagoVenta::MONEDA_CORDOBA)
            ->sum('Monto');

        $pagosDolares = (float) (clone $basePagosEfectivo)
            ->where('Moneda', PagoVenta::MONEDA_DOLAR)
            ->sum('Monto');

        $ventasConPagoEfectivo = PagoVenta::query()
            ->select('Id_Venta')
            ->whereBetween('Fecha_Pago', [$inicioCaja, $finCaja])
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->whereIn('Id_Venta', clone $ventasActivasDelUsuario)
            ->distinct();

        $cambioEntregadoCordobas = (float) Venta::query()
            ->whereIn('Id_Venta', $ventasConPagoEfectivo)
            ->sum('Cambio_Entregado_Cordobas');

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

        $this->cerrarCajasAbiertasAnterioresAHoy();

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
        $this->limpiarFormularioApertura(false);

        $this->abrirCajaModal = true;
    }

    public function cerrarModalCaja(): void
    {
        $this->limpiarFormularioApertura(true);
        $this->abrirCajaModal = false;
    }

    public function abrirModalEgreso(): void
    {
        $this->resetValidation();

        $this->cargarAperturaAbierta();
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
            'montoAperturaFormulario' => ['required', 'numeric', 'min:0.01'],
            'tasaCambioApertura' => ['nullable', 'numeric', 'min:0.01'],
        ], [
            'montoAperturaFormulario.required' => 'El monto de apertura es obligatorio.',
            'montoAperturaFormulario.numeric' => 'El monto de apertura debe ser numérico.',
            'montoAperturaFormulario.min' => 'El monto de apertura debe ser mayor a C$ 0.00.',

            'tasaCambioApertura.numeric' => 'La tasa de cambio debe ser numérica.',
            'tasaCambioApertura.min' => 'La tasa de cambio debe ser mayor a 0.',
        ]);

        $usuarioId = $this->usuarioActualId();

        if (! $usuarioId) {
            $this->addError('montoAperturaFormulario', 'No se pudo identificar al usuario en sesión.');
            return;
        }

        $tasaCambioApertura = trim($this->tasaCambioApertura);
        $actualizoTasaCambio = $tasaCambioApertura !== '';

        $this->cerrarCajasAbiertasAnterioresAHoy();

        try {
            $this->transaccion(function () use ($usuarioId, $tasaCambioApertura, $actualizoTasaCambio) {
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
                    'Monto_Apertura' => number_format((float) $this->montoAperturaFormulario, 2, '.', ''),
                    'Fecha_Apertura' => now(),
                    'Estado_Apertura' => AperturaCaja::ABIERTO,
                ]);

                $this->aperturaCajaId = (int) $apertura->Id_Apertura_Caja;
                $this->caja = (string) $apertura->Numero_Caja;
                $this->montoApertura = number_format((float) $apertura->Monto_Apertura, 2, '.', '');
                $this->cajaAbierta = true;
            });

            $this->abrirCajaModal = false;
            $this->limpiarFormularioApertura(false);

            $this->cargarTasaCambio();
            $this->cargarPagosVentaHoy();
            $this->cargarEgresosCaja();

            $mensaje = $actualizoTasaCambio
                ? 'Caja abierta correctamente y tasa de cambio actualizada.'
                : 'Caja abierta correctamente.';

            $this->notificar($mensaje, 'success');
        } catch (RuntimeException $e) {
            $this->abrirCajaModal = false;
            $this->limpiarFormularioApertura(false);

            $this->cargarAperturaAbierta();

            $this->notificar($e->getMessage(), 'warning');
        } catch (Throwable $e) {
            report($e);

            $this->abrirCajaModal = false;
            $this->limpiarFormularioApertura(false);

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

        if ($this->monedaEgreso === 'cordoba') {
            $montoDolares = 0;
        }

        if ($this->monedaEgreso === 'dolar') {
            $montoCordobas = 0;
        }

        if ($this->monedaEgreso === 'cordoba' && $montoCordobas <= 0) {
            $this->addError('montoEgresoCordobas', 'El monto a egresar debe ser mayor a C$ 0.00.');
            return;
        }

        if ($this->monedaEgreso === 'dolar' && $montoDolares <= 0) {
            $this->addError('montoEgresoDolares', 'El monto a egresar debe ser mayor a $ 0.00.');
            return;
        }

        if ($this->monedaEgreso === 'ambas') {
            $hayErrorMonto = false;

            if ($montoCordobas <= 0) {
                $this->addError('montoEgresoCordobas', 'El monto en córdobas debe ser mayor a C$ 0.00.');
                $hayErrorMonto = true;
            }

            if ($montoDolares <= 0) {
                $this->addError('montoEgresoDolares', 'El monto en dólares debe ser mayor a $ 0.00.');
                $hayErrorMonto = true;
            }

            if ($hayErrorMonto) {
                return;
            }
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
        $this->modalReporteCierreCaja = false;
        $this->reporteCierreCajaUrl = '';
        $this->ultimoArqueoCajaId = null;

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
        $this->cargarPagosVentaHoy();
        $this->cargarEgresosCaja();

        if (! $this->cajaAbierta || ! $this->aperturaCajaId) {
            $this->notificar('No hay una caja abierta para cerrar.', 'error');
            return;
        }

        try {
            $resultado = $this->transaccion(function () use ($usuarioId) {
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

                return [
                    'arqueo_id' => (int) $arqueo->Id_Arqueo,
                ];
            });

            $this->ultimoArqueoCajaId = (int) ($resultado['arqueo_id'] ?? 0);
            $this->reporteCierreCajaUrl = $this->ultimoArqueoCajaId > 0
                ? route('reportes.cierre-caja.pdf', ['arqueo' => $this->ultimoArqueoCajaId])
                : '';
            $this->modalReporteCierreCaja = $this->reporteCierreCajaUrl !== '';

            $this->cajaAbierta = false;
            $this->aperturaCajaId = null;
            $this->montoApertura = '0.00';
            $this->caja = (string) AperturaCaja::siguienteNumeroCajaHoy();

            $this->limpiarTotalesVenta();
            $this->limpiarTotalesEgreso();
            $this->reiniciarConteos();
          

            $this->notificar('Caja cerrada correctamente. Reporte PDF listo.', 'success');
        } catch (RuntimeException $e) {
            $this->notificar($e->getMessage(), 'warning');
        } catch (Throwable $e) {
            report($e);

            $this->notificar('Ocurrió un error al cerrar la caja.', 'error');
        }
    }

    public function cerrarModalReporteCierreCaja(): void
    {
        $this->modalReporteCierreCaja = false;
    }

    public function subtotalCordoba(string|int $denominacion): float
    {
        $clave = (string) $denominacion;

        $cantidad = (int) ($this->conteoCordobas[$clave] ?? 0);
        $valor = (float) ($this->denominacionesCordobas[$clave] ?? 0);

        return round($cantidad * $valor, 2);
    }

    public function subtotalDolar(int $denominacion): float
    {
        return ((int) ($this->conteoDolares[$denominacion] ?? 0)) * $denominacion;
    }

    public function totalCordobas(): float
    {
        $total = 0;

        foreach (array_keys($this->denominacionesCordobas) as $denominacion) {
            $total += $this->subtotalCordoba($denominacion);
        }

        return round($total, 2);
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
            + (float) $this->totalVentaCordobas;
    }

    public function totalIngresosDolares(): float
    {
        return (float) $this->totalVentaDolares;
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

<div class="flex min-h-screen w-full flex-col gap-4 bg-[#F0F3F7] p-3 md:p-4">
    @php
        $cardClass = 'rounded-2xl border border-[#D7E4F3] bg-white shadow-sm';
        $softCardClass = 'rounded-xl border border-[#D7E4F3] bg-[#F8FAFC]';
        $inputReadonlyClass = 'input-sm h-8 min-h-0 w-full rounded-lg border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42] gnet-number-light';
        $inputEditableClass = 'input-sm h-8 min-h-0 w-full rounded-lg border-[#D7E4F3] bg-white text-xs text-[#1A2B42] gnet-number-light';
        $inputCounterClass = 'h-7 min-h-0 w-full rounded-lg border border-[#D7E4F3] bg-white px-2 text-center text-xs text-[#1A2B42] outline-none focus:border-[#0B6FE4] focus:ring-0 gnet-number-light';
        $modalAperturaClass = 'backdrop-blur-sm [&_.btn-circle]:!hidden';
        $modalEgresoClass = 'backdrop-blur-sm [&_.btn-circle]:!hidden';
        $modalTasaClass = 'backdrop-blur-sm [&_.btn-circle]:!hidden';

        $diferenciaCordobas = $this->detalleDiferenciaCordobas();
        $diferenciaDolares = $this->detalleDiferenciaDolares();

        $diferenciaCordobasClass = match ($diferenciaCordobas['tipo']) {
            'faltante' => 'border-red-200 bg-red-50 text-red-700',
            'sobrante' => 'border-green-200 bg-green-50 text-green-700',
            default => 'border-[#D7E4F3] bg-[#F8FAFC] text-[#1A2B42]',
        };

        $diferenciaDolaresClass = match ($diferenciaDolares['tipo']) {
            'faltante' => 'border-red-200 bg-red-50 text-red-700',
            'sobrante' => 'border-green-200 bg-green-50 text-green-700',
            default => 'border-[#D7E4F3] bg-[#F8FAFC] text-[#1A2B42]',
        };
    @endphp

    @once
        <style>
            input.gnet-number-light[type="number"] {
                color-scheme: light;
            }
        </style>
    @endonce

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4">
        <div class="{{ $cardClass }} overflow-hidden">
            <div class="border-b border-[#D7E4F3] bg-white p-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4] shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5M6 15h.008v.008H6V15Zm3 0h.008v.008H9V15Z" />
                            </svg>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl font-bold text-[#1A2B42] md:text-2xl">
                                    Arqueo de caja
                                </h1>

                                @if ($cajaAbierta)
                                    <span class="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-green-700">
                                        Caja abierta
                                    </span>
                                @else
                                    <span class="rounded-full border border-[#D7E4F3] bg-[#F8FAFC] px-3 py-1 text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                        Sin apertura
                                    </span>
                                @endif
                            </div>

                            <p class="mt-0.5 text-xs text-[#5F6B7A]">
                                Conteo físico, movimientos registrados y diferencia final de caja.
                            </p>
                        </div>
                    </div>

                    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 xl:max-w-xl">
                        <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                            <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Caja actual
                            </span>

                            <x-input
                                wire:model="caja"
                                readonly
                                prefix="#"
                                class="{{ $inputReadonlyClass }}"
                            />
                        </div>

                        <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                            <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Tasa de cambio
                            </span>

                            <div class="flex items-center gap-2">
                                <x-input
                                    wire:model="tasaOficial"
                                    readonly
                                    prefix="TC"
                                    class="{{ $inputReadonlyClass }}"
                                />

                                <x-button
                                    icon="o-pencil-square"
                                    wire:click="abrirModalTasa"
                                    title="Modificar tasa de cambio"
                                    class="h-10 min-h-0 rounded-xl border border-[#0B6FE4] bg-[#0B6FE4] text-white shadow-sm hover:bg-[#2E8BC0] hover:text-white"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 bg-[#F8FAFC] p-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-[#D7E4F3] bg-white p-3">
                    <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Esperado C$
                    </span>

                    <span class="mt-1.5 block text-xl font-extrabold text-[#1A2B42]">
                        C$ {{ $this->formatear($this->totalEsperadoCordobas()) }}
                    </span>
                </div>

                <div class="rounded-2xl border border-[#D7E4F3] bg-white p-3">
                    <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Esperado $
                    </span>

                    
                    <span class="mt-1.5 block text-xl font-extrabold text-[#1A2B42]">
                        $ {{ $this->formatear($this->totalEsperadoDolares()) }}
                    </span>
                </div>

                <div class="rounded-2xl border {{ $diferenciaCordobasClass }} p-3">
                    <span class="block text-xs font-bold uppercase tracking-wide">
                        {{ $diferenciaCordobas['tipo'] === 'faltante' ? 'Faltante C$' : ($diferenciaCordobas['tipo'] === 'sobrante' ? 'Sobrante C$' : 'Diferencia C$') }}
                    </span>

                    <span class="mt-1.5 block text-xl font-extrabold">
                        {{ $diferenciaCordobas['valor'] }}
                    </span>
                </div>

                <div class="rounded-2xl border {{ $diferenciaDolaresClass }} p-3">
                    <span class="block text-xs font-bold uppercase tracking-wide">
                        {{ $diferenciaDolares['tipo'] === 'faltante' ? 'Faltante $' : ($diferenciaDolares['tipo'] === 'sobrante' ? 'Sobrante $' : 'Diferencia $') }}
                    </span>

                    <span class="mt-1.5 block text-xl font-extrabold">
                        {{ $diferenciaDolares['valor'] }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-12">
            <div class="xl:col-span-3">
                <div class="{{ $cardClass }} overflow-hidden">
                    <div class="border-b border-[#D7E4F3] bg-white px-4 py-3">
                        <h2 class="text-base font-bold text-[#1A2B42]">
                            Montos de la caja
                        </h2>

                        <p class="mt-0.5 text-xs text-[#5F6B7A]">
                            Montos usados para calcular el efectivo esperado.
                        </p>
                    </div>

                    <div class="space-y-1.5 p-2">
                        <div class="{{ $softCardClass }} p-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Apertura
                            </span>

                            <div class="mt-1.5 flex items-center justify-between gap-3">
                              <span class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Fondo inicial
                                </span>

                                <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                    C$ {{ $this->formatear($this->montoApertura) }}
                                </span>
                            </div>
                        </div>

                        <div class="{{ $softCardClass }} p-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Ingresos
                            </span>

                            <div class="mt-1.5 space-y-1">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-[#5F6B7A]">
                                        Ventas C$
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                        C$ {{ $this->formatear($this->totalVentaCordobas) }}
                                    </span>
                                </div>

                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-[#5F6B7A]">
                                        Ventas $
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                        $ {{ $this->formatear($this->totalVentaDolares) }}
                                    </span>
                                </div>                                
                            </div>
                        </div>

                        <div class="{{ $softCardClass }} p-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Egresos
                            </span>

                            <div class="mt-1.5 space-y-1">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-[#5F6B7A]">
                                        Egresos C$
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                        C$ {{ $this->formatear($this->totalEgresoCordobas) }}
                                    </span>
                                </div>

                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-[#5F6B7A]">
                                        Egresos $
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                        $ {{ $this->formatear($this->totalEgresoDolares) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#EAF2FB] p-2.5">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                Totales esperados
                            </span>

                            <div class="mt-1.5 space-y-1">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-[#1A2B42]">
                                        Córdobas
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-sm font-extrabold text-[#0B6FE4] tabular-nums">
                                        C$ {{ $this->formatear($this->totalEsperadoCordobas()) }}
                                    </span>
                                </div>

                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-[#1A2B42]">
                                        Dólares
                                    </span>

                                    <span class="shrink-0 whitespace-nowrap text-right text-sm font-extrabold text-[#0B6FE4] tabular-nums">
                                        $ {{ $this->formatear($this->totalEsperadoDolares()) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#D7E4F3] bg-white p-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            <x-button
                                label="Registrar egreso"
                                wire:click="abrirModalEgreso"
                                class="min-h-0 rounded-xl border-0 bg-[#0B6FE4] px-3 py-2 text-white shadow-sm hover:opacity-95"
                            />

                            <x-button
                                label="Abrir caja"
                                wire:click="abrirModalCaja"
                                class="min-h-0 rounded-xl border border-[#D7E4F3] bg-white px-3 py-2 text-[#1A2B42] shadow-sm hover:bg-[#F8FAFC]"
                            />

                            <x-button
                                label="Cerrar caja"
                                wire:click="cerrarCaja"
                                class="min-h-0 rounded-xl border-0 bg-[#0B6FE4] px-3 py-2 text-white shadow-sm hover:opacity-95"
                            />

                            @if ($reporteCierreCajaUrl !== '')
                                <x-button
                                    label="Ver cierre PDF"
                                    type="button"
                                    wire:click="$set('modalReporteCierreCaja', true)"
                                    class="min-h-0 rounded-xl border border-[#D7E4F3] bg-white px-3 py-2 text-[#1A2B42] shadow-sm hover:bg-[#F8FAFC]"
                                />
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-9">
                <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
                    <div class="{{ $cardClass }} overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-[#D7E4F3] bg-white px-4 py-3">
                            <div>
                                <h2 class="text-base font-bold text-[#1A2B42]">
                                    Conteo en córdobas
                                </h2>

                                <p class="mt-0.5 text-xs text-[#5F6B7A]">
                                    Ingrese cantidad por denominación.
                                </p>
                            </div>

                            <div class="rounded-xl bg-[#EAF2FB] px-3 py-1.5 text-right">
                                <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Total C$
                                </span>

                                <span class="block text-base font-extrabold text-[#0B6FE4]">
                                    {{ $this->formatear($this->totalCordobas()) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                                <div class="grid grid-cols-12 bg-[#2E8BC0] px-3 py-2 text-xs font-bold uppercase tracking-wide text-white">
                                    <div class="col-span-3">Denom.</div>
                                    <div class="col-span-5 text-center">Cantidad</div>
                                    <div class="col-span-4 text-right">Subtotal</div>
                                </div>

                                <div class="divide-y divide-[#D7E4F3] bg-white">
                                    @foreach ($denominacionesCordobas as $denominacion => $valorDenominacion)
                                        <div class="grid grid-cols-12 items-center gap-2 px-3 py-4">
                                            <div class="col-span-3">
                                                <span class="rounded-lg bg-[#F8FAFC] px-2.5 py-1.5 text-xs font-extrabold text-[#1A2B42]">
                                                    C${{ $this->formatearDenominacionCordoba($valorDenominacion) }}
                                                </span>
                                            </div>

                                            <div class="col-span-5">
                                                <x-input
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    placeholder="0"
                                                    wire:model.live="conteoCordobas.{{ $denominacion }}"
                                                    class="{{ $inputCounterClass }}"
                                                />
                                            </div>

                                            <div class="col-span-4 text-right">
                                                <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                                    C$ {{ $this->formatear($this->subtotalCordoba($denominacion)) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-[#EAF2FB] px-3 py-3">
                                <span class="text-sm font-bold text-[#1A2B42]">
                                    Efectivo contado C$
                                </span>

                                <span class="text-lg font-extrabold text-[#0B6FE4]">
                                    C$ {{ $this->formatear($this->totalCordobas()) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="{{ $cardClass }} overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-[#D7E4F3] bg-white px-4 py-3">
                            <div>
                                <h2 class="text-base font-bold text-[#1A2B42]">
                                    Conteo en dólares
                                </h2>

                                <p class="mt-0.5 text-xs text-[#5F6B7A]">
                                    Ingrese cantidad por denominación.
                                </p>
                            </div>

                            <div class="rounded-xl bg-[#EAF2FB] px-3 py-1.5 text-right">
                                <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Total $
                                </span>

                                <span class="block text-base font-extrabold text-[#0B6FE4]">
                                    {{ $this->formatear($this->totalDolares()) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                                <div class="grid grid-cols-12 bg-[#2E8BC0] px-3 py-2 text-xs font-bold uppercase tracking-wide text-white">
                                    <div class="col-span-3">Denom.</div>
                                    <div class="col-span-5 text-center">Cantidad</div>
                                    <div class="col-span-4 text-right">Subtotal</div>
                                </div>

                                <div class="divide-y divide-[#D7E4F3] bg-white">
                                    @foreach ($denominacionesDolares as $denominacion)
                                        <div class="grid grid-cols-12 items-center gap-2 px-3 py-2">
                                            <div class="col-span-3">
                                                <span class="rounded-lg bg-[#F8FAFC] px-2.5 py-1.5 text-xs font-extrabold text-[#1A2B42]">
                                                    ${{ $denominacion }}
                                                </span>
                                            </div>

                                            <div class="col-span-5">
                                                <x-input
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    placeholder="0"
                                                    wire:model.live="conteoDolares.{{ $denominacion }}"
                                                    class="{{ $inputCounterClass }}"
                                                />
                                            </div>

                                            <div class="col-span-4 text-right">
                                                <span class="shrink-0 whitespace-nowrap text-right text-xs font-extrabold text-[#1A2B42] tabular-nums">
                                                    $ {{ $this->formatear($this->subtotalDolar($denominacion)) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-[#EAF2FB] px-3 py-3">
                                <span class="text-sm font-bold text-[#1A2B42]">
                                    Efectivo contado $
                                </span>

                                <span class="text-lg font-extrabold text-[#0B6FE4]">
                                    $ {{ $this->formatear($this->totalDolares()) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </div>

        <x-modal
            wire:model="abrirCajaModal"
            class="{{ $modalAperturaClass }}"
            box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
        >
            <div class="p-5">
                <div class="mb-4 flex items-center justify-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5m-3.75-3.75h7.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <div class="text-center">
                        <h2 class="text-lg font-bold text-[#0B6FE4]">Apertura de caja</h2>
                        <p class="text-sm text-[#5F6B7A]">Ingresa el monto inicial antes de iniciar ventas</p>
                    </div>
                </div>

                <x-form wire:submit="guardarApertura" no-separator>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-[#1A2B42]">
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
                            <label class="mb-1.5 block text-xs font-semibold text-[#1A2B42]">
                                Monto de apertura
                            </label>

                            <x-input
                                type="number"
                                step="0.01"
                                min="0.01"
                                wire:model="montoAperturaFormulario"
                                placeholder="0.00"
                                prefix="C$"
                                class="{{ $inputEditableClass }}"
                            />
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-[#1A2B42]">
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
                                    <label class="mb-1.5 block text-xs font-semibold text-[#1A2B42]">
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
            class="{{ $modalTasaClass }}"
            box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
        >
            <div class="p-5">
                <div class="mb-4 flex items-center justify-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                        </svg>
                    </div>

                    <div class="text-center">
                        <h2 class="text-lg font-bold text-[#0B6FE4]">Modificar tasa de cambio</h2>
                        <p class="text-sm text-[#5F6B7A]">Actualiza la tasa oficial utilizada por la caja</p>
                    </div>
                </div>

                <x-form wire:submit="guardarTasaCambio" no-separator>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-[#1A2B42]">
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
            class="{{ $modalEgresoClass }}"
            box-class="max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
        >
            <div class="overflow-hidden rounded-2xl bg-white">
                <div class="flex items-center gap-3 border-b border-[#D7E4F3] bg-white px-4 py-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-[#1A2B42]">Egresos de caja</h2>
                        <p class="text-sm text-[#5F6B7A]">Registra salidas de una moneda específica o ambas.</p>
                    </div>
                </div>

                <x-form wire:submit="guardarEgreso" no-separator>
                    <div class="space-y-3 bg-[#F8FAFC] px-4 py-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-[#D7E4F3] bg-white px-3 py-2">
                                <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Disponible C$
                                </span>

                                <span class="mt-0.5 block text-sm font-extrabold text-[#1A2B42]">
                                    C$ {{ $this->formatear($this->disponibleEgresoCordobas()) }}
                                </span>
                            </div>

                            <div class="rounded-xl border border-[#D7E4F3] bg-white px-3 py-2">
                                <span class="block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Disponible $
                                </span>

                                <span class="mt-0.5 block text-sm font-extrabold text-[#1A2B42]">
                                    $ {{ $this->formatear($this->disponibleEgresoDolares()) }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                    Moneda del egreso
                                </label>

                                <x-select
                                    wire:model.live="monedaEgreso"
                                    :options="$monedasEgreso"
                                    class="{{ $inputEditableClass }}"
                                />
                            </div>

                            @if ($this->mostrarMontoEgresoCordobas())
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                        Monto en córdobas
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
                                </div>
                            @endif

                            @if ($this->mostrarMontoEgresoDolares())
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                        Monto en dólares
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
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                Motivo del egreso
                            </label>

                            <x-select
                                wire:model="motivoEgreso"
                                :options="$motivosEgreso"
                                placeholder="Seleccione un motivo"
                                class="{{ $inputEditableClass }}"
                            />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                Descripción del egreso
                            </label>

                            <x-textarea
                                wire:model="descripcionEgreso"
                                rows="3"
                                placeholder="Detalle obligatorio del egreso..."
                                class="rounded-xl border-[#D7E4F3] bg-white text-xs text-[#1A2B42] placeholder:text-[#7B8794]"
                            />
                        </div>
                    </div>

                    <x-slot:actions>
                        <div class="flex w-full flex-col-reverse justify-end gap-3 border-t border-[#D7E4F3] bg-white px-4 py-3 sm:flex-row">
                            <x-button
                                label="Volver"
                                type="button"
                                wire:click="cerrarModalEgreso"
                                class="min-h-0 rounded-xl bg-[#6B7280] px-4 py-2 text-white shadow-sm hover:bg-[#5B6472]"
                            />

                            <x-button
                                label="Guardar egreso"
                                type="submit"
                                spinner="guardarEgreso"
                                class="min-h-0 rounded-xl bg-[#0B6FE4] px-4 py-2 text-white shadow-sm hover:bg-[#2E8BC0]"
                            />
                        </div>
                    </x-slot:actions>
                </x-form>
            </div>
        </x-modal>

        <x-modal
            wire:model="modalReporteCierreCaja"
            class="backdrop-blur-sm"
            box-class="w-[96vw] max-w-6xl max-h-[92vh] overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
        >
            <div class="flex max-h-[88vh] flex-col bg-white">
                <div class="flex flex-col gap-3 border-b border-[#D7E4F3] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-[#1A2B42]">Reporte de cierre de caja</h2>
                        <p class="text-sm text-[#5F6B7A]">
                            {{ $ultimoArqueoCajaId ? 'Arqueo #' . str_pad((string) $ultimoArqueoCajaId, 6, '0', STR_PAD_LEFT) : 'Cierre de caja' }}
                        </p>
                    </div>

                    @if ($reporteCierreCajaUrl !== '')
                        <a
                            href="{{ $reporteCierreCajaUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="rounded-xl bg-[#0B6FE4] px-4 py-2 text-center text-sm font-bold text-white shadow-sm hover:bg-[#2E8BC0]"
                        >
                            Abrir PDF
                        </a>
                    @endif
                </div>

                @if ($reporteCierreCajaUrl !== '')
                    <iframe
                        src="{{ $reporteCierreCajaUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH"
                        loading="eager"
                        class="min-h-[70vh] w-full flex-1 bg-[#F8FAFC]"
                    ></iframe>
                @else
                    <div class="px-4 py-16 text-center text-sm text-[#7B8794]">
                        No hay reporte de cierre para mostrar.
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button
                    label="Cerrar"
                    type="button"
                    wire:click="cerrarModalReporteCierreCaja"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
