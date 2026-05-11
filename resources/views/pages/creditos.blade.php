<?php

use App\Models\AbonoCredito;
use App\Models\ClienteCredito;
use App\Models\Credito;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public string $valorBusqueda = '';

    public int $idCreditoSeleccionado = 0;

    public string $cliente = '';
    public string $cedula = '';
    public string $codigoCredito = '';
    public string $estadoCredito = 'Pendiente';
    public string $fechaCredito = '';
    public string $proximoPago = '';

    public string $saldoOriginal = '0.00';
    public string $saldoPendiente = '0.00';

    public string $abonarCordobas = '0.00';
    public string $abonarDolares = '0.00';
    public string $tasaCambio = '36.5000';
    public string $cambioCordobas = '0.00';

    public string $metodoPago = 'efectivo';
    public string $referenciaPago = '';
    public string $fechaPago = '';
    public string $observacion = '';
    public string $filtroSaldoFavor = '';

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $metodosPagoOptions = [];
    public array $detalleCredito = [];
    public array $clientesSaldoFavor = [];
    public array $headersDetalle = [];
    public array $headersSaldoFavor = [];

    public function mount(): void
    {
        $this->fechaPago = now()->toDateString();
        $this->tasaCambio = '36.5000';

        $this->metodosPagoOptions = [
            ['id' => 'efectivo', 'name' => 'Efectivo'],
            ['id' => 'transferencia', 'name' => 'Transferencia'],
            ['id' => 'tarjeta', 'name' => 'Tarjeta'],
            ['id' => 'deposito', 'name' => 'Depósito'],
        ];

        $this->headersDetalle = [
            ['key' => 'numero', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'fecha_pago', 'label' => 'Fecha', 'class' => 'min-w-[120px]'],
            ['key' => 'monto', 'label' => 'Monto aplicado', 'class' => 'min-w-[140px]'],
            ['key' => 'moneda', 'label' => 'Moneda', 'class' => 'hidden md:table-cell min-w-[100px]'],
            ['key' => 'referencia', 'label' => 'Referencia', 'class' => 'hidden lg:table-cell min-w-[160px]'],
            ['key' => 'estado', 'label' => 'Tipo', 'class' => 'min-w-[110px]'],
        ];

        $this->headersSaldoFavor = [
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'documento', 'label' => 'Documento', 'class' => 'hidden md:table-cell'],
            ['key' => 'saldo', 'label' => 'Saldo', 'class' => 'w-32'],
        ];

        $this->cargarClientesSaldoFavor();
    }

    public function updatedValorBusqueda(): void
    {
        $this->resetErrorBag();

        $busqueda = trim($this->valorBusqueda);

        if ($busqueda === '') {
            $this->limpiarBusqueda();
            return;
        }

        if (! ctype_digit($busqueda) && strlen($busqueda) < 2) {
            $this->limpiarCreditoCargado();
            return;
        }

        $this->buscarCredito(false);
    }

    public function updatedAbonarCordobas(): void
    {
        $this->calcularCambio();
    }

    public function updatedAbonarDolares(): void
    {
        $this->calcularCambio();
    }

    public function updatedTasaCambio(): void
    {
        $this->tasaCambio = '36.5000';
        $this->calcularCambio();
    }

    public function updatedFiltroSaldoFavor(): void
    {
        $this->cargarClientesSaldoFavor();
    }

    public function buscarCredito(bool $mostrarMensajes = true): void
    {
        $this->resetErrorBag();

        $busqueda = trim($this->valorBusqueda);

        if ($busqueda === '') {
            $this->limpiarBusqueda();

            if ($mostrarMensajes) {
                $this->mostrarToast('Ingrese un cliente o número de venta para buscar.', 'error');
            }

            return;
        }

        if (! ctype_digit($busqueda) && strlen($busqueda) < 2) {
            $this->limpiarCreditoCargado();

            if ($mostrarMensajes) {
                $this->mostrarToast('Escriba al menos 2 caracteres para buscar por cliente.', 'error');
            }

            return;
        }

        $credito = Credito::query()
            ->with([
                'venta.cliente.persona',
                'clienteCredito.cliente.persona',
                'abonos',
            ])
            ->where(function ($query) use ($busqueda) {
                $query
                    ->whereHas('venta', function ($ventaQuery) use ($busqueda) {
                        $ventaQuery->where('Numero_Factura', 'like', "%{$busqueda}%");

                        if (ctype_digit($busqueda)) {
                            $ventaQuery->orWhere('Id_Venta', (int) $busqueda);
                        }
                    })
                    ->orWhereHas('clienteCredito.cliente', function ($clienteQuery) use ($busqueda) {
                        $this->aplicarFiltroCliente($clienteQuery, $busqueda);
                    })
                    ->orWhereHas('venta.cliente', function ($clienteQuery) use ($busqueda) {
                        $this->aplicarFiltroCliente($clienteQuery, $busqueda);
                    });
            })
            ->orderByRaw("
                CASE
                    WHEN Estado = ? THEN 1
                    WHEN Estado = ? THEN 2
                    WHEN Estado = ? THEN 3
                    ELSE 4
                END
            ", [
                Credito::ESTADO_PENDIENTE,
                Credito::ESTADO_PARCIAL,
                Credito::ESTADO_VENCIDO,
            ])
            ->orderByDesc('Id_Credito')
            ->first();

        if (! $credito) {
            $this->limpiarCreditoCargado();
            $this->valorBusqueda = $busqueda;

            if ($mostrarMensajes) {
                $this->mostrarToast('No se encontró ningún crédito con ese cliente o número de venta.', 'error');
            }

            return;
        }

        $this->cargarCreditoSeleccionado($credito);

        if ($mostrarMensajes) {
            $this->mostrarToast('Crédito cargado correctamente.');
        }
    }

    protected function aplicarFiltroCliente($clienteQuery, string $busqueda): void
    {
        $like = "%{$busqueda}%";

        $clienteQuery->where(function ($query) use ($like) {
            $query
                ->where('Institucion', 'like', $like)
                ->orWhereHas('persona', function ($personaQuery) use ($like) {
                    $personaQuery->where(function ($nombreQuery) use ($like) {
                        $nombreQuery
                            ->where('Primer_Nombre', 'like', $like)
                            ->orWhere('Segundo_Nombre', 'like', $like)
                            ->orWhere('Primer_Apellido', 'like', $like)
                            ->orWhere('Segundo_Apellido', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) LIKE ?",
                                [$like]
                            );
                    });
                });
        });
    }

    public function registrarPago(): void
    {
        $this->resetErrorBag();

        $this->fechaPago = now()->toDateString();
        $this->tasaCambio = '36.5000';

        $datos = $this->validate(
            [
                'idCreditoSeleccionado' => 'required|integer|exists:credito,Id_Credito',
                'abonarCordobas' => 'nullable|numeric|min:0',
                'abonarDolares' => 'nullable|numeric|min:0',
                'tasaCambio' => 'nullable|numeric|min:0',
                'metodoPago' => 'required|in:efectivo,transferencia,tarjeta,deposito',
                'referenciaPago' => 'nullable|string|max:100',
                'fechaPago' => 'required|date|before_or_equal:today',
                'observacion' => 'nullable|string|max:300',
            ],
            [
                'idCreditoSeleccionado.required' => 'Debe buscar y seleccionar un crédito.',
                'idCreditoSeleccionado.exists' => 'El crédito seleccionado no existe.',

                'abonarCordobas.numeric' => 'El abono en córdobas debe ser numérico.',
                'abonarCordobas.min' => 'El abono en córdobas no puede ser negativo.',

                'abonarDolares.numeric' => 'El abono en dólares debe ser numérico.',
                'abonarDolares.min' => 'El abono en dólares no puede ser negativo.',

                'tasaCambio.numeric' => 'La tasa de cambio debe ser numérica.',
                'tasaCambio.min' => 'La tasa de cambio no puede ser negativa.',

                'metodoPago.required' => 'Debe seleccionar el método de pago.',
                'metodoPago.in' => 'El método de pago seleccionado no es válido.',

                'referenciaPago.max' => 'La referencia no debe superar los 100 caracteres.',

                'fechaPago.required' => 'Debe indicar la fecha del pago.',
                'fechaPago.date' => 'La fecha de pago no es válida.',
                'fechaPago.before_or_equal' => 'La fecha de pago no puede ser futura.',

                'observacion.max' => 'La observación no debe superar los 300 caracteres.',
            ]
        );

        $montoCordobas = $this->normalizarDecimal($datos['abonarCordobas'] ?? 0);
        $montoDolares = $this->normalizarDecimal($datos['abonarDolares'] ?? 0);
        $tasaCambio = $this->normalizarDecimal($datos['tasaCambio'] ?? 36.5);

        if ($montoCordobas <= 0 && $montoDolares <= 0) {
            throw ValidationException::withMessages([
                'abonarCordobas' => 'Debe ingresar al menos un monto a abonar.',
            ]);
        }

        if ($montoDolares > 0 && $tasaCambio <= 0) {
            throw ValidationException::withMessages([
                'tasaCambio' => 'Debe ingresar la tasa de cambio cuando se abona en dólares.',
            ]);
        }

        $creditoActualizado = null;
        $montoAplicadoTotal = 0.0;
        $cambio = 0.0;

        DB::transaction(function () use (
            $datos,
            $montoCordobas,
            $montoDolares,
            $tasaCambio,
            &$creditoActualizado,
            &$montoAplicadoTotal,
            &$cambio
        ) {
            $credito = Credito::query()
                ->with(['clienteCredito'])
                ->whereKey((int) $datos['idCreditoSeleccionado'])
                ->lockForUpdate()
                ->first();

            if (! $credito) {
                throw ValidationException::withMessages([
                    'idCreditoSeleccionado' => 'El crédito seleccionado no existe.',
                ]);
            }

            if ((string) $credito->Estado === Credito::ESTADO_CANCELADO || (float) $credito->Saldo_Actual <= 0) {
                throw ValidationException::withMessages([
                    'idCreditoSeleccionado' => 'Este crédito ya está cancelado.',
                ]);
            }

            $saldoPendiente = round((float) $credito->Saldo_Actual, 2);
            $totalRecibidoCordobas = round($montoCordobas + ($montoDolares * $tasaCambio), 2);

            $montoAplicadoTotal = min($totalRecibidoCordobas, $saldoPendiente);
            $cambio = max($totalRecibidoCordobas - $saldoPendiente, 0);

            $saldoRestanteParaAplicar = $montoAplicadoTotal;

            if ($montoCordobas > 0 && $saldoRestanteParaAplicar > 0) {
                $aplicadoCordobas = min($montoCordobas, $saldoRestanteParaAplicar);

                $this->crearAbonoCredito(
                    credito: $credito,
                    moneda: AbonoCredito::MONEDA_CORDOBA,
                    monto: $aplicadoCordobas,
                    tipoCambio: 1,
                    montoEquivalenteCordobas: $aplicadoCordobas,
                    fechaPago: $datos['fechaPago'],
                    referencia: $datos['referenciaPago'] ?? null,
                    metodoPago: $datos['metodoPago'],
                    observacion: $datos['observacion'] ?? null
                );

                $saldoRestanteParaAplicar = round($saldoRestanteParaAplicar - $aplicadoCordobas, 2);
            }

            if ($montoDolares > 0 && $saldoRestanteParaAplicar > 0) {
                $montoDolaresCordobas = round($montoDolares * $tasaCambio, 2);
                $aplicadoDolaresCordobas = min($montoDolaresCordobas, $saldoRestanteParaAplicar);
                $aplicadoDolares = round($aplicadoDolaresCordobas / $tasaCambio, 2);

                $this->crearAbonoCredito(
                    credito: $credito,
                    moneda: AbonoCredito::MONEDA_DOLAR,
                    monto: $aplicadoDolares,
                    tipoCambio: $tasaCambio,
                    montoEquivalenteCordobas: $aplicadoDolaresCordobas,
                    fechaPago: $datos['fechaPago'],
                    referencia: $datos['referenciaPago'] ?? null,
                    metodoPago: $datos['metodoPago'],
                    observacion: $datos['observacion'] ?? null
                );

                $saldoRestanteParaAplicar = round($saldoRestanteParaAplicar - $aplicadoDolaresCordobas, 2);
            }

            $nuevoSaldoCredito = round($saldoPendiente - $montoAplicadoTotal, 2);

            $credito->Saldo_Actual = max($nuevoSaldoCredito, 0);
            $credito->Estado = $credito->Saldo_Actual <= 0
                ? Credito::ESTADO_CANCELADO
                : Credito::ESTADO_PARCIAL;
            $credito->save();

            if ($credito->clienteCredito) {
                $nuevoSaldoCliente = round((float) $credito->clienteCredito->Saldo_Actual - $montoAplicadoTotal, 2);

                $credito->clienteCredito->Saldo_Actual = max($nuevoSaldoCliente, 0);
                $credito->clienteCredito->Estado = ClienteCredito::ESTADO_ACTIVO;
                $credito->clienteCredito->save();
            }

            $creditoActualizado = $credito->fresh([
                'venta.cliente.persona',
                'clienteCredito.cliente.persona',
                'abonos',
            ]);
        });

        if ($creditoActualizado) {
            $this->cargarCreditoSeleccionado($creditoActualizado);
        }

        $this->cambioCordobas = $this->formatoMoneda($cambio);

        $this->limpiarPago(false);
        $this->cargarClientesSaldoFavor();

        $mensaje = $montoAplicadoTotal > 0
            ? 'Pago registrado correctamente.'
            : 'No se aplicó ningún monto al crédito.';

        $this->mostrarToast($mensaje);
    }

    protected function crearAbonoCredito(
        Credito $credito,
        string $moneda,
        float $monto,
        float $tipoCambio,
        float $montoEquivalenteCordobas,
        string $fechaPago,
        ?string $referencia,
        string $metodoPago,
        ?string $observacion
    ): void {
        $textoObservacion = trim(
            'Método: ' . ucfirst($metodoPago) .
            ($observacion ? '. ' . trim($observacion) : '')
        );

        AbonoCredito::query()->create([
            'Id_Credito' => $credito->Id_Credito,
            'Fecha_Abono' => $fechaPago,
            'Moneda' => $moneda,
            'Monto' => round($monto, 2),
            'Tipo_Cambio' => round($tipoCambio, 4),
            'Monto_Equivalente_Cordobas' => round($montoEquivalenteCordobas, 2),
            'Numero_Transferencia' => $referencia ? trim($referencia) : null,
            'Observacion' => $textoObservacion,
        ]);
    }

    protected function cargarCreditoSeleccionado(Credito $credito): void
    {
        $credito->loadMissing([
            'venta.cliente.persona',
            'clienteCredito.cliente.persona',
            'abonos',
        ]);

        $cliente = $credito->clienteCredito?->cliente ?? $credito->venta?->cliente;

        $this->idCreditoSeleccionado = (int) $credito->Id_Credito;
        $this->cliente = $this->nombreCliente($cliente);
        $this->cedula = $this->documentoCliente($cliente);
        $this->codigoCredito = 'CR-' . str_pad((string) $credito->Id_Credito, 5, '0', STR_PAD_LEFT);
        $this->estadoCredito = $credito->Estado ?: Credito::ESTADO_PENDIENTE;
        $this->fechaCredito = optional($credito->Fecha_Credito)->format('d/m/Y') ?? '—';
        $this->proximoPago = '—';

        $saldoOriginal = $credito->venta?->Total
            ?? ((float) $credito->Abono_Inicial + (float) $credito->Saldo_Actual + (float) $credito->abonos->sum('Monto_Equivalente_Cordobas'));

        $this->saldoOriginal = $this->formatoMoneda((float) $saldoOriginal);
        $this->saldoPendiente = $this->formatoMoneda((float) $credito->Saldo_Actual);

        $this->detalleCredito = $this->mapearDetalleCredito($credito);
        $this->calcularCambio();
    }

    protected function mapearDetalleCredito(Credito $credito): array
    {
        $filas = [];

        if ((float) $credito->Abono_Inicial > 0) {
            $filas[] = [
                'numero' => 1,
                'fecha_pago' => optional($credito->Fecha_Credito)->format('d/m/Y') ?? '—',
                'monto' => 'C$ ' . $this->formatoMoneda((float) $credito->Abono_Inicial),
                'moneda' => 'NIO',
                'referencia' => 'Abono inicial',
                'estado' => 'Inicial',
            ];
        }

        $numero = count($filas) + 1;

        foreach ($credito->abonos->sortBy('Fecha_Abono') as $abono) {
            $filas[] = [
                'numero' => $numero++,
                'fecha_pago' => optional($abono->Fecha_Abono)->format('d/m/Y') ?? '—',
                'monto' => 'C$ ' . $this->formatoMoneda((float) $abono->Monto_Equivalente_Cordobas),
                'moneda' => $abono->moneda_nombre,
                'referencia' => $abono->Numero_Transferencia ?: '—',
                'estado' => 'Abono',
            ];
        }

        return $filas;
    }

    protected function cargarClientesSaldoFavor(): void
    {
        $filtro = trim($this->filtroSaldoFavor);

        $query = ClienteCredito::query()
            ->with(['cliente.persona'])
            ->where('Saldo_Actual', '<', 0)
            ->orderBy('Saldo_Actual');

        if ($filtro !== '') {
            $query->whereHas('cliente', function ($clienteQuery) use ($filtro) {
                $clienteQuery->where('Institucion', 'like', "%{$filtro}%")
                    ->orWhereHas('persona', function ($personaQuery) use ($filtro) {
                        $personaQuery->where('Primer_Nombre', 'like', "%{$filtro}%")
                            ->orWhere('Segundo_Nombre', 'like', "%{$filtro}%")
                            ->orWhere('Primer_Apellido', 'like', "%{$filtro}%")
                            ->orWhere('Segundo_Apellido', 'like', "%{$filtro}%")
                            ->orWhere('Telefono', 'like', "%{$filtro}%");
                    });
            });
        }

        $this->clientesSaldoFavor = $query
            ->limit(15)
            ->get()
            ->map(function ($clienteCredito) {
                return [
                    'cliente' => $this->nombreCliente($clienteCredito->cliente),
                    'documento' => $this->documentoCliente($clienteCredito->cliente),
                    'saldo' => 'C$ ' . $this->formatoMoneda(abs((float) $clienteCredito->Saldo_Actual)),
                ];
            })
            ->toArray();
    }

    public function limpiarBusqueda(): void
    {
        $this->valorBusqueda = '';
        $this->limpiarCreditoCargado();
    }

    protected function limpiarCreditoCargado(): void
    {
        $this->reset([
            'idCreditoSeleccionado',
            'cliente',
            'cedula',
            'codigoCredito',
            'fechaCredito',
            'proximoPago',
            'detalleCredito',
        ]);

        $this->estadoCredito = 'Pendiente';
        $this->saldoOriginal = '0.00';
        $this->saldoPendiente = '0.00';

        $this->limpiarPago(false);
    }

    public function limpiarPago(bool $limpiarCambio = true): void
    {
        $this->reset([
            'abonarCordobas',
            'abonarDolares',
            'tasaCambio',
            'referenciaPago',
            'observacion',
        ]);

        $this->abonarCordobas = '0.00';
        $this->abonarDolares = '0.00';
        $this->tasaCambio = '36.5000';
        $this->fechaPago = now()->toDateString();

        if ($limpiarCambio) {
            $this->cambioCordobas = '0.00';
        }
    }

    protected function calcularCambio(): void
    {
        $saldo = $this->normalizarDecimal($this->saldoPendiente);
        $cordobas = $this->normalizarDecimal($this->abonarCordobas);
        $dolares = $this->normalizarDecimal($this->abonarDolares);
        $tasa = $this->normalizarDecimal($this->tasaCambio);

        $total = $cordobas + ($dolares * $tasa);
        $cambio = max($total - $saldo, 0);

        $this->cambioCordobas = $this->formatoMoneda($cambio);
    }

    protected function nombreCliente($cliente): string
    {
        if (! $cliente) {
            return 'Sin cliente vinculado';
        }

        if (filled($cliente->Institucion ?? null)) {
            return trim((string) $cliente->Institucion);
        }

        $persona = $cliente->persona ?? null;

        if (! $persona) {
            return 'Cliente sin persona vinculada';
        }

        $nombre = collect([
            $persona->Primer_Nombre ?? null,
            $persona->Segundo_Nombre ?? null,
            $persona->Primer_Apellido ?? null,
            $persona->Segundo_Apellido ?? null,
        ])
            ->filter(fn ($valor) => filled($valor))
            ->implode(' ');

        return $nombre !== '' ? $nombre : 'Sin nombre registrado';
    }

    protected function documentoCliente($cliente): string
    {
        if (! $cliente) {
            return 'Sin documento';
        }

        if (filled($cliente->Cedula ?? null)) {
            return (string) $cliente->Cedula;
        }

        if (filled($cliente->RUC ?? null)) {
            return (string) $cliente->RUC;
        }

        if (filled($cliente->persona?->Telefono ?? null)) {
            return (string) $cliente->persona->Telefono;
        }

        return 'Sin documento';
    }

    protected function normalizarDecimal($valor): float
    {
        $valor = (string) $valor;
        $valor = str_replace(',', '', $valor);
        $valor = trim($valor);

        return is_numeric($valor) ? round((float) $valor, 4) : 0.0;
    }

    protected function formatoMoneda(float $valor): string
    {
        return number_format($valor, 2, '.', ',');
    }

    protected function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        $this->toastMensaje = $mensaje;
        $this->toastTipo = $tipo;
        $this->mostrarToast = true;
    }

    public function cerrarToast(): void
    {
        $this->mostrarToast = false;
        $this->toastMensaje = '';
        $this->toastTipo = 'success';
    }
};
?>

@php
    $fieldClass = 'rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#8A97A8] disabled:bg-[#EEF3F8] disabled:text-[#8A97A8] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

    $readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-semibold text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

    $cardClass = 'border border-[#D7E4F3] bg-white shadow-sm [&_.text-base-content\\/70]:text-[#5F6B7A] [&_.text-sm]:text-[#5F6B7A] [&_.text-base-content]:text-[#1A2B42] [&_.card-title]:text-[#1A2B42] [&_label]:text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42]';

    $primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] disabled:border-0 disabled:bg-[#B8CADB] disabled:text-white';

    $secondaryButtonClass = 'btn-sm border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7] disabled:bg-[#EEF3F8] disabled:text-[#8A97A8]';

    $pagoBloqueado = $idCreditoSeleccionado === 0
        || $estadoCredito === \App\Models\Credito::ESTADO_CANCELADO
        || (float) str_replace(',', '', $saldoPendiente) <= 0;

    $estadoClass = $estadoCredito === \App\Models\Credito::ESTADO_CANCELADO
        ? 'bg-green-100 text-green-700'
        : ($estadoCredito === \App\Models\Credito::ESTADO_VENCIDO
            ? 'bg-red-100 text-red-700'
            : 'bg-[#EAF4FD] text-[#0B6FE4]');

    $textoBusqueda = trim($valorBusqueda) === ''
        ? 'Escribe el nombre del cliente, institución o número de venta.'
        : ($idCreditoSeleccionado > 0
            ? 'Crédito cargado automáticamente.'
            : 'Sin coincidencias cargadas todavía.');
@endphp

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    @if ($mostrarToast)
        <div class="fixed right-5 top-5 z-[999] w-full max-w-sm">
            <div
                class="{{ $toastTipo === 'success'
                    ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]'
                    : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex gap-3">
                        <div
                            class="{{ $toastTipo === 'success'
                                ? 'bg-[#2E8BC0]'
                                : 'bg-red-500' }} mt-0.5 h-2.5 w-2.5 rounded-full"
                        ></div>

                        <p class="text-sm font-semibold">{{ $toastMensaje }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="cerrarToast"
                        class="text-lg leading-none text-[#5F6B7A] hover:text-[#1A2B42]"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Gestión de Créditos</h1>
            <p class="text-sm text-[#5F6B7A]">
                Busca, consulta y registra pagos sin brincar entre pantallas.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span
                class="{{ $idCreditoSeleccionado > 0
                    ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#0B6FE4]'
                    : 'border-[#D7E4F3] bg-white text-[#5F6B7A]' }} hidden rounded-full border px-3 py-1.5 text-xs font-semibold md:inline-flex"
            >
                {{ $idCreditoSeleccionado > 0 ? $codigoCredito : 'Sin crédito seleccionado' }}
            </span>

            <x-button
                label="Limpiar"
                icon="o-arrow-path"
                wire:click="limpiarBusqueda"
                class="{{ $secondaryButtonClass }}"
            />
        </div>
    </div>

    <div class="grid min-h-0 grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="flex min-h-0 flex-col gap-4 overflow-hidden">
            <x-card
                title="Búsqueda automática"
                subtitle="Filtra por cliente, institución o número de venta/factura."
                shadow
                separator
                class="{{ $cardClass }}"
            >
                <x-form wire:submit="buscarCredito" no-separator>
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <x-input
                            label="Cliente o número de venta"
                            wire:model.live.debounce.350ms="valorBusqueda"
                            placeholder="Ej: Juan Pérez, Comercial Norte o FV-00025"
                            icon="o-magnifying-glass"
                            class="{{ $fieldClass }}"
                        />

                        <div class="flex items-end">
                            <x-button
                                type="button"
                                icon="o-x-mark"
                                wire:click="limpiarBusqueda"
                                class="{{ $secondaryButtonClass }} h-10 w-full lg:w-11"
                            />
                        </div>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                        <span class="text-[#5F6B7A]" wire:loading.remove wire:target="valorBusqueda">
                            {{ $textoBusqueda }}
                        </span>

                        <span class="inline-flex items-center gap-2 font-semibold text-[#0B6FE4]" wire:loading wire:target="valorBusqueda">
                            <span class="loading loading-spinner loading-xs"></span>
                            Buscando...
                        </span>

                        @if ($idCreditoSeleccionado > 0)
                            <span class="hidden rounded-full bg-[#F0F3F7] px-2.5 py-1 font-semibold text-[#1A2B42] sm:inline-flex">
                                Venta cargada
                            </span>
                        @endif
                    </div>
                </x-form>

                <div class="mt-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Cliente</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $cliente ?: '—' }}</p>
                        <p class="mt-1 truncate text-xs text-[#5F6B7A]">{{ $cedula ?: 'Sin documento cargado' }}</p>
                    </div>

                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Crédito</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $codigoCredito ?: '—' }}</p>
                        <p class="mt-1 text-xs text-[#5F6B7A]">Fecha crédito: {{ $fechaCredito ?: '—' }}</p>
                    </div>

                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Saldo original</p>
                        <p class="mt-1 truncate text-base font-black text-[#1A2B42]">C$ {{ $saldoOriginal }}</p>
                        <p class="mt-1 text-xs text-[#5F6B7A]">Total del crédito</p>
                    </div>

                    <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <div class="flex min-w-0 items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Pendiente</p>
                                <p class="mt-1 truncate text-base font-black text-[#1A2B42]">
                                    C$ {{ $saldoPendiente }}
                                </p>
                            </div>

                            <span
                                class="{{ $estadoClass }} block max-w-[86px] shrink-0 truncate rounded-full px-2.5 py-1 text-center text-xs font-bold"
                                title="{{ $estadoCredito }}"
                            >
                                {{ ucfirst(strtolower($estadoCredito)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card
                title="Detalle del crédito"
                subtitle="Abono inicial, pagos aplicados e historial."
                shadow
                separator
                class="flex min-h-0 flex-1 flex-col {{ $cardClass }}"
            >
                <div class="min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="h-full overflow-auto overscroll-contain">
                        <x-table
                            :headers="$headersDetalle"
                            :rows="$detalleCredito"
                            no-hover
                            class="[&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#1A2B42] [&_tbody_tr:hover]:!bg-[#EAF4FD]"
                        >
                            @scope('cell_estado', $fila)
                                <span
                                    class="{{ $fila['estado'] === 'Inicial'
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-green-100 text-green-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >
                                    {{ $fila['estado'] }}
                                </span>
                            @endscope
                        </x-table>
                    </div>
                </div>

                @if (! count($detalleCredito))
                    <div class="mt-3 rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F8FAFC] px-4 py-6 text-center text-sm font-medium text-[#5F6B7A]">
                        Busca un cliente o número de venta para cargar el historial del crédito.
                    </div>
                @endif
            </x-card>
        </div>

        <aside class="flex min-h-0 flex-col gap-4 overflow-hidden xl:sticky xl:top-4 xl:max-h-[calc(100vh-5rem)]">
            <x-card
                title="Registrar pago"
                subtitle="La fecha se asigna automáticamente al guardar el pago."
                shadow
                separator
                class="{{ $cardClass }}"
            >
                @if ($idCreditoSeleccionado === 0)
                    <div class="mb-3 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] px-3 py-3 text-sm font-medium text-[#5F6B7A]">
                        Primero busca un cliente o número de venta. Luego se habilita el pago.
                    </div>
                @elseif ($pagoBloqueado)
                    <div class="mb-3 rounded-2xl border border-green-200 bg-green-50 px-3 py-3 text-sm font-medium text-green-700">
                        Este crédito no tiene saldo pendiente para pagar.
                    </div>
                @endif

                <x-form wire:submit="registrarPago" no-separator>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-2">
                        <x-input
                            label="Saldo pendiente"
                            wire:model="saldoPendiente"
                            prefix="C$"
                            readonly
                            class="{{ $readonlyFieldClass }}"
                        />

                        <x-input
                            label="Cambio"
                            wire:model="cambioCordobas"
                            prefix="C$"
                            readonly
                            class="rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-black text-[#1C7C45] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]"
                        />

                        <x-input
                            label="Abonar en C$"
                            wire:model.live.debounce.250ms="abonarCordobas"
                            prefix="C$"
                            :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Abonar en US$"
                            wire:model.live.debounce.250ms="abonarDolares"
                            prefix="US$"
                            :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}"
                        />

                        <x-select
                            label="Método"
                            wire:model="metodoPago"
                            :options="$metodosPagoOptions"
                            option-value="id"
                            option-label="name"
                            :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Tasa fija"
                            wire:model="tasaCambio"
                            prefix="C$"
                            readonly
                            class="{{ $readonlyFieldClass }}"
                        />

                        <x-input
                            label="Referencia"
                            wire:model="referenciaPago"
                            placeholder="Voucher, recibo o transferencia"
                            :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }} md:col-span-2"
                        />
                    </div>

                    <div class="mt-3">
                        <x-textarea
                            label="Observación"
                            wire:model="observacion"
                            rows="3"
                            placeholder="Detalle breve del pago realizado..."
                            :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="sticky bottom-0 -mx-1 mt-4 border-t border-[#D7E4F3] bg-white/95 pt-3 backdrop-blur">
                        <div class="grid w-full grid-cols-2 gap-2">
                            <x-button
                                label="Limpiar pago"
                                type="button"
                                icon="o-arrow-path"
                                wire:click="limpiarPago"
                                class="{{ $secondaryButtonClass }}"
                                :disabled="$idCreditoSeleccionado === 0"
                            />

                            <x-button
                                label="Guardar pago"
                                type="submit"
                                spinner="registrarPago"
                                icon="o-check-circle"
                                class="{{ $primaryButtonClass }}"
                                :disabled="$pagoBloqueado"
                            />
                        </div>
                    </div>
                </x-form>
            </x-card>

            <x-card
                title="Saldo a favor"
                subtitle="Consulta rápida de clientes con saldo negativo."
                shadow
                separator
                class="flex min-h-0 flex-1 flex-col {{ $cardClass }}"
            >
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Clientes</p>
                        <p class="mt-1 text-xl font-black text-[#1A2B42]">{{ count($clientesSaldoFavor) }}</p>
                    </div>

                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Saldo total</p>
                        <p class="mt-1 text-xl font-black text-[#1A2B42]">
                            C$
                            {{
                                number_format(
                                    collect($clientesSaldoFavor)
                                        ->sum(fn ($item) => (float) str_replace([',', 'C$'], '', $item['saldo'])),
                                    2,
                                    '.',
                                    ','
                                )
                            }}
                        </p>
                    </div>
                </div>

                <div class="mt-3">
                    <x-input
                        label="Filtrar saldo a favor"
                        wire:model.live.debounce.250ms="filtroSaldoFavor"
                        placeholder="Buscar cliente..."
                        icon="o-magnifying-glass"
                        class="{{ $fieldClass }}"
                    />
                </div>

                <div class="mt-3 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="h-full overflow-auto overscroll-contain">
                        <x-table
                            :headers="$headersSaldoFavor"
                            :rows="$clientesSaldoFavor"
                            no-hover
                            class="[&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#1A2B42] [&_tbody_tr:hover]:!bg-[#EAF4FD]"
                        />
                    </div>
                </div>

                @if (! count($clientesSaldoFavor))
                    <div class="mt-3 rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F8FAFC] px-4 py-5 text-center text-sm font-medium text-[#5F6B7A]">
                        No hay clientes con saldo a favor.
                    </div>
                @endif
            </x-card>
        </aside>
    </div>
</div>