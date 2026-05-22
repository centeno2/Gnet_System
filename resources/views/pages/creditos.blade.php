<?php

use App\Models\AbonoCredito;
use App\Models\ClienteCredito;
use App\Models\Credito;
use App\Models\TasaCambio;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    protected const TASA_CAMBIO_RESPALDO = '36.5000';

    public string $valorBusqueda = '';
    public array $sugerenciasCredito = [];
    public bool $mostrarSugerenciasCredito = false;

    public int $idCreditoSeleccionado = 0;

    public string $cliente = '';
    public string $cedula = '';
    public string $codigoCredito = '';
    public string $estadoCredito = 'PENDIENTE';
    public string $fechaCredito = '';
    public string $proximoPago = '';

    public string $saldoOriginal = '0.00';
    public string $saldoPendiente = '0.00';

    public string $abonarCordobas = '0.00';
    public string $abonarDolares = '0.00';
    public string $tasaCambio = '36.5000';
    public string $saldoFavorVista = '0.00';

    public string $metodoPago = 'efectivo';
    public string $referenciaPago = '';
    public string $fechaPago = '';
    public string $observacion = '';
    public string $filtroSaldoFavor = '';

    public array $metodosPagoOptions = [];
    public array $detalleCredito = [];
    public array $clientesSaldoFavor = [];
    public array $headersDetalle = [];
    public array $headersSaldoFavor = [];

    public function mount(): void
    {
        $this->fechaPago = now()->toDateString();
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();

        $this->metodosPagoOptions = [
            ['id' => 'efectivo', 'name' => 'Efectivo'],
            ['id' => 'transferencia', 'name' => 'Transferencia'],
            ['id' => 'tarjeta', 'name' => 'Tarjeta'],
            ['id' => 'deposito', 'name' => 'Depósito'],
        ];

        $this->headersDetalle = [
            ['key' => 'numero', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'fecha_pago', 'label' => 'Fecha', 'class' => 'min-w-[120px]'],
            ['key' => 'monto', 'label' => 'Monto aplicado', 'class' => 'min-w-[150px]'],
            ['key' => 'moneda', 'label' => 'Moneda', 'class' => 'min-w-[100px]'],
            ['key' => 'referencia', 'label' => 'Referencia', 'class' => 'min-w-[160px]'],
            ['key' => 'observacion', 'label' => 'Observación', 'class' => 'min-w-[260px]'],
            ['key' => 'estado', 'label' => 'Tipo', 'class' => 'min-w-[110px]'],
        ];

        $this->headersSaldoFavor = [
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'documento', 'label' => 'Documento', 'class' => 'hidden md:table-cell'],
            ['key' => 'saldo', 'label' => 'Saldo a favor', 'class' => 'w-36'],
        ];

        $this->cargarClientesSaldoFavor();
    }

    public function updatedValorBusqueda(): void
    {
        $this->resetErrorBag();

        $busqueda = trim($this->valorBusqueda);

        $this->limpiarCreditoCargado(false);

        if ($busqueda === '') {
            $this->limpiarSugerenciasCredito();
            return;
        }

        if (! ctype_digit($busqueda) && mb_strlen($busqueda) < 2) {
            $this->limpiarSugerenciasCredito();
            return;
        }

        $this->buscarSugerenciasCredito();
    }

    public function updatedAbonarCordobas(): void
    {
        if (trim($this->abonarCordobas) === '') {
            $this->abonarCordobas = '0.00';
        }

        $this->calcularSaldoFavorVista();
    }

    public function updatedAbonarDolares(): void
    {
        if (trim($this->abonarDolares) === '') {
            $this->abonarDolares = '0.00';
        }

        $this->calcularSaldoFavorVista();
    }

    public function updatedTasaCambio(): void
    {
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();
        $this->calcularSaldoFavorVista();
    }

    public function updatedFiltroSaldoFavor(): void
    {
        $this->cargarClientesSaldoFavor();
    }

    protected function buscarSugerenciasCredito(): void
    {
        $busqueda = trim($this->valorBusqueda);

        if ($busqueda === '') {
            $this->limpiarSugerenciasCredito();
            return;
        }

        if (! ctype_digit($busqueda) && mb_strlen($busqueda) < 2) {
            $this->limpiarSugerenciasCredito();
            return;
        }

        $idCreditoBuscado = null;
        $soloNumerosBusqueda = preg_replace('/\D+/', '', $busqueda);

        if ($soloNumerosBusqueda !== '' && preg_match('/^(CR[-\s]*)?\d+$/i', $busqueda)) {
            $idCreditoBuscado = (int) $soloNumerosBusqueda;
        }

        $creditos = Credito::query()
            ->with([
                'venta.cliente.persona',
                'clienteCredito.cliente.persona',
            ])
            ->where(function ($query) use ($busqueda, $idCreditoBuscado) {
                if ($idCreditoBuscado) {
                    $query->where('Id_Credito', $idCreditoBuscado);
                }

                $query
                    ->orWhereHas('venta', function ($ventaQuery) use ($busqueda) {
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
            ->limit(8)
            ->get();

        $this->sugerenciasCredito = $creditos
            ->map(function (Credito $credito) {
                $credito->loadMissing([
                    'venta.cliente.persona',
                    'clienteCredito.cliente.persona',
                ]);

                $abonos = $this->obtenerAbonosCredito($credito);
                $cliente = $credito->clienteCredito?->cliente ?? $credito->venta?->cliente;
                $saldoPendienteReal = $this->calcularSaldoPendienteReal($credito, $abonos);

                return [
                    'id' => (int) $credito->Id_Credito,
                    'codigo' => 'CR-' . str_pad((string) $credito->Id_Credito, 5, '0', STR_PAD_LEFT),
                    'cliente' => $this->nombreCliente($cliente),
                    'documento' => $this->documentoCliente($cliente),
                    'ubicacion' => $this->ubicacionCliente($cliente),
                    'factura' => $credito->venta?->Numero_Factura ?: 'Sin factura',
                    'estado' => (string) ($saldoPendienteReal <= 0 ? Credito::ESTADO_CANCELADO : $credito->Estado),
                    'saldo' => 'C$ ' . $this->formatoMoneda($saldoPendienteReal),
                ];
            })
            ->values()
            ->toArray();

        $this->mostrarSugerenciasCredito = count($this->sugerenciasCredito) > 0;
    }

    public function seleccionarCredito(int $idCredito): void
    {
        $credito = Credito::query()
            ->with([
                'venta.cliente.persona',
                'clienteCredito.cliente.persona',
            ])
            ->whereKey($idCredito)
            ->first();

        if (! $credito) {
            $this->limpiarCreditoCargado();

            $this->notificar(
                type: 'error',
                title: 'Crédito no encontrado',
                description: 'El crédito seleccionado ya no está disponible.'
            );

            return;
        }

        $this->cargarCreditoSeleccionado($credito);
        $this->valorBusqueda = $this->codigoCredito . ' - ' . $this->cliente;
        $this->limpiarSugerenciasCredito();
    }

    protected function aplicarFiltroCliente($clienteQuery, string $busqueda): void
    {
        $like = "%{$busqueda}%";

        $terminos = collect(preg_split('/\s+/', trim($busqueda)))
            ->filter(fn ($valor) => filled($valor))
            ->values();

        $clienteQuery->where(function ($query) use ($like, $terminos) {
            $query->where('Institucion', 'like', $like);

            foreach (['Cedula', 'RUC', 'Telefono_Institucion', 'Correo_Institucion', 'Municipio'] as $columna) {
                if ($this->clienteTieneColumna($columna)) {
                    $query->orWhere($columna, 'like', $like);
                }
            }

            $query->orWhereHas('persona', function ($personaQuery) use ($terminos, $like) {
                $personaQuery->where(function ($nombreQuery) use ($terminos, $like) {
                    $nombreQuery->where('Telefono', 'like', $like);

                    foreach ($terminos as $termino) {
                        $token = "%{$termino}%";

                        $nombreQuery->where(function ($tokenQuery) use ($token) {
                            $tokenQuery
                                ->where('Primer_Nombre', 'like', $token)
                                ->orWhere('Segundo_Nombre', 'like', $token)
                                ->orWhere('Primer_Apellido', 'like', $token)
                                ->orWhere('Segundo_Apellido', 'like', $token);
                        });
                    }
                });
            });
        });
    }

    protected function clienteTieneColumna(string $columna): bool
    {
        static $columnas = null;

        if ($columnas === null) {
            $columnas = Schema::getColumnListing('cliente');
        }

        return in_array($columna, $columnas, true);
    }

    public function registrarPago(): void
    {
        $this->resetErrorBag();

        $this->fechaPago = now()->toDateString();
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();

        $validator = Validator::make(
            [
                'idCreditoSeleccionado' => $this->idCreditoSeleccionado,
                'abonarCordobas' => $this->abonarCordobas,
                'abonarDolares' => $this->abonarDolares,
                'tasaCambio' => $this->tasaCambio,
                'metodoPago' => $this->metodoPago,
                'referenciaPago' => $this->referenciaPago,
                'fechaPago' => $this->fechaPago,
                'observacion' => $this->observacion,
            ],
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
                'idCreditoSeleccionado.required' => 'Debe seleccionar un crédito.',
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

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->addError($campo, $mensaje);
                }
            }

            $this->notificar(
                type: 'error',
                title: 'Revisa los datos',
                description: $validator->errors()->first()
            );

            return;
        }

        $datos = $validator->validated();

        $montoCordobas = $this->normalizarDecimal($datos['abonarCordobas'] ?? 0);
        $montoDolares = $this->normalizarDecimal($datos['abonarDolares'] ?? 0);
        $tasaCambio = $this->normalizarDecimal($this->tasaCambio);

        if ($montoCordobas <= 0 && $montoDolares <= 0) {
            $this->addError('abonarCordobas', 'Debe ingresar al menos un monto a abonar.');

            $this->notificar(
                type: 'error',
                title: 'Monto requerido',
                description: 'Debe ingresar al menos un monto a abonar.'
            );

            return;
        }

        if ($montoDolares > 0 && $tasaCambio <= 0) {
            $this->addError('tasaCambio', 'Debe ingresar la tasa de cambio cuando se abona en dólares.');

            $this->notificar(
                type: 'error',
                title: 'Tasa requerida',
                description: 'Debe ingresar la tasa de cambio cuando se abona en dólares.'
            );

            return;
        }

        try {
            $idUsuario = $this->obtenerUsuarioActualId();
        } catch (ValidationException $e) {
            $this->notificar(
                type: 'error',
                title: 'Usuario no encontrado',
                description: collect($e->errors())->flatten()->first() ?? 'No se pudo identificar el usuario actual.'
            );

            return;
        }

        $montoAplicadoTotal = 0.0;
        $saldoFavorGenerado = 0.0;

        try {
            DB::transaction(function () use (
                $datos,
                $montoCordobas,
                $montoDolares,
                $tasaCambio,
                $idUsuario,
                &$montoAplicadoTotal,
                &$saldoFavorGenerado
            ) {
                $credito = Credito::query()
                    ->with([
                        'venta',
                        'venta.cliente.persona',
                        'clienteCredito',
                        'clienteCredito.cliente.persona',
                    ])
                    ->whereKey((int) $datos['idCreditoSeleccionado'])
                    ->lockForUpdate()
                    ->first();

                if (! $credito) {
                    throw ValidationException::withMessages([
                        'idCreditoSeleccionado' => 'El crédito seleccionado no existe.',
                    ]);
                }

                $clienteCredito = $this->resolverClienteCredito($credito);

                $abonosPrevios = $this->obtenerAbonosCredito($credito, true);
                $saldoPendienteReal = $this->calcularSaldoPendienteReal($credito, $abonosPrevios);

                if ((string) $credito->Estado === Credito::ESTADO_CANCELADO || $saldoPendienteReal <= 0) {
                    $credito->Saldo_Actual = 0;
                    $credito->Estado = Credito::ESTADO_CANCELADO;
                    $credito->save();

                    throw ValidationException::withMessages([
                        'idCreditoSeleccionado' => 'Este crédito ya está cancelado.',
                    ]);
                }

                $credito->Saldo_Actual = $saldoPendienteReal;
                $credito->Estado = $this->resolverEstadoCredito($saldoPendienteReal, $credito, $abonosPrevios);
                $credito->save();

                $totalRecibidoCordobas = round($montoCordobas + ($montoDolares * $tasaCambio), 2);

                $montoAplicadoTotal = min($totalRecibidoCordobas, $saldoPendienteReal);
                $saldoFavorGenerado = max(round($totalRecibidoCordobas - $saldoPendienteReal, 2), 0);

                $saldoRestanteParaAplicar = $montoAplicadoTotal;

                if ($montoCordobas > 0 && $saldoRestanteParaAplicar > 0) {
                    $aplicadoCordobas = min($montoCordobas, $saldoRestanteParaAplicar);

                    $this->crearAbonoCredito(
                        credito: $credito,
                        idUsuario: $idUsuario,
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
                        idUsuario: $idUsuario,
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

                $nuevoSaldoCredito = max(round($saldoPendienteReal - $montoAplicadoTotal, 2), 0);

                $credito->Saldo_Actual = $nuevoSaldoCredito;
                $credito->Estado = $nuevoSaldoCredito <= 0
                    ? Credito::ESTADO_CANCELADO
                    : Credito::ESTADO_PARCIAL;
                $credito->save();

                if ($clienteCredito && $saldoFavorGenerado > 0) {
                    $this->sumarSaldoFavorClienteCredito(
                        clienteCredito: $clienteCredito,
                        saldoFavorGenerado: $saldoFavorGenerado
                    );
                }
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->addError($campo, $mensaje);
                }
            }

            $this->notificar(
                type: 'error',
                title: 'No se pudo registrar',
                description: collect($e->errors())->flatten()->first() ?? 'Revisa los datos del crédito.'
            );

            return;
        }

        $mensaje = $saldoFavorGenerado > 0
            ? 'Pago guardado. Saldo a favor: C$ ' . $this->formatoMoneda($saldoFavorGenerado) . '.'
            : 'Pago guardado correctamente.';

        $this->limpiarBusqueda();
        $this->cargarClientesSaldoFavor();

        $this->notificar(
            type: 'success',
            title: 'Pago registrado',
            description: $mensaje
        );
    }

    protected function obtenerTasaCambioDelDia(): string
    {
        $tasaHoy = TasaCambio::query()
            ->whereDate('Fecha_Modificacion', now()->toDateString())
            ->latest('Fecha_Modificacion')
            ->first();

        $valor = (float) ($tasaHoy?->Valor_Cambio ?? TasaCambio::valorActual());

        if ($valor <= 0) {
            $valor = (float) self::TASA_CAMBIO_RESPALDO;
        }

        return number_format($valor, 4, '.', '');
    }

    protected function obtenerUsuarioActualId(): int
    {
        $usuarioAutenticado = auth()->user();

        if ($usuarioAutenticado && isset($usuarioAutenticado->Id_Usuario)) {
            return (int) $usuarioAutenticado->Id_Usuario;
        }

        if ($usuarioAutenticado && isset($usuarioAutenticado->id)) {
            return (int) $usuarioAutenticado->id;
        }

        $primerUsuario = Usuario::query()
            ->orderBy('Id_Usuario')
            ->first();

        if (! $primerUsuario) {
            throw ValidationException::withMessages([
                'Id_Usuario' => 'No se encontró ningún usuario para registrar el abono.',
            ]);
        }

        return (int) $primerUsuario->Id_Usuario;
    }

    protected function resolverClienteCredito(Credito $credito): ?ClienteCredito
    {
        if ($credito->Id_Cliente_Credito) {
            $clienteCredito = ClienteCredito::query()
                ->whereKey((int) $credito->Id_Cliente_Credito)
                ->lockForUpdate()
                ->first();

            if ($clienteCredito) {
                $credito->setRelation('clienteCredito', $clienteCredito);
                return $clienteCredito;
            }
        }

        $idCliente = $credito->venta?->Id_Cliente;

        if (! $idCliente) {
            return null;
        }

        $clienteCredito = ClienteCredito::query()
            ->where('Id_Cliente', (int) $idCliente)
            ->lockForUpdate()
            ->first();

        if (! $clienteCredito) {
            $clienteCredito = ClienteCredito::query()->create([
                'Id_Cliente' => (int) $idCliente,
                'Saldo_Actual' => 0,
                'Estado' => ClienteCredito::ESTADO_ACTIVO,
                'Fecha_Registro' => now(),
            ]);
        }

        $credito->Id_Cliente_Credito = (int) $clienteCredito->Id_Cliente_Credito;
        $credito->save();

        $credito->setRelation('clienteCredito', $clienteCredito);

        return $clienteCredito;
    }

    protected function resolverEstadoCredito(float $saldoPendiente, Credito $credito, $abonosPrevios): string
    {
        if ($saldoPendiente <= 0) {
            return Credito::ESTADO_CANCELADO;
        }

        if ((float) $credito->Abono_Inicial > 0 || $abonosPrevios->isNotEmpty()) {
            return Credito::ESTADO_PARCIAL;
        }

        return Credito::ESTADO_PENDIENTE;
    }

    protected function crearAbonoCredito(
        Credito $credito,
        int $idUsuario,
        string $moneda,
        float $monto,
        float $tipoCambio,
        float $montoEquivalenteCordobas,
        string $fechaPago,
        ?string $referencia,
        string $metodoPago,
        ?string $observacion
    ): void {
        if ($monto <= 0 || $montoEquivalenteCordobas <= 0) {
            return;
        }

        $textoObservacion = trim(
            'Método: ' . ucfirst($metodoPago) .
            ($observacion ? '. ' . trim($observacion) : '')
        );

        AbonoCredito::query()->create([
            'Id_Credito' => (int) $credito->Id_Credito,
            'Id_Usuario' => $idUsuario,
            'Fecha_Abono' => $fechaPago,
            'Moneda' => $moneda,
            'Monto' => round($monto, 2),
            'Tipo_Cambio' => round($tipoCambio, 4),
            'Monto_Equivalente_Cordobas' => round($montoEquivalenteCordobas, 2),
            'Numero_Transferencia' => filled($referencia) ? trim((string) $referencia) : null,
            'Observacion' => $textoObservacion,
        ]);
    }

    protected function obtenerAbonosCredito(Credito $credito, bool $bloquear = false)
    {
        $query = AbonoCredito::query()
            ->where('Id_Credito', (int) $credito->Id_Credito)
            ->orderBy('Fecha_Abono')
            ->orderBy('Id_Abono_Credito');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    protected function calcularTotalCreditoBase(Credito $credito, $abonos = null): float
    {
        $totalVenta = $this->normalizarDecimal($credito->venta?->Total ?? 0);

        if ($totalVenta > 0) {
            return round($totalVenta, 2);
        }

        $abonos = $abonos ?? $this->obtenerAbonosCredito($credito);

        return round(
            (float) $credito->Abono_Inicial
            + (float) $credito->Saldo_Actual
            + (float) $abonos->sum('Monto_Equivalente_Cordobas'),
            2
        );
    }

    protected function calcularSaldoPendienteReal(Credito $credito, $abonos = null): float
    {
        $abonos = $abonos ?? $this->obtenerAbonosCredito($credito);

        $totalVenta = $this->normalizarDecimal($credito->venta?->Total ?? 0);

        if ($totalVenta > 0) {
            return max(
                round(
                    $totalVenta
                    - (float) $credito->Abono_Inicial
                    - (float) $abonos->sum('Monto_Equivalente_Cordobas'),
                    2
                ),
                0
            );
        }

        return max(round((float) $credito->Saldo_Actual, 2), 0);
    }

    protected function calcularSaldoFavorRegistrado(ClienteCredito $clienteCredito): float
    {
        $saldoActualCliente = $this->normalizarDecimal($clienteCredito->Saldo_Actual ?? 0);

        return max(round($saldoActualCliente, 2), 0);
    }

    protected function sumarSaldoFavorClienteCredito(ClienteCredito $clienteCredito, float $saldoFavorGenerado): void
    {
        if ($saldoFavorGenerado <= 0) {
            return;
        }

        $saldoFavorActual = $this->calcularSaldoFavorRegistrado($clienteCredito);

        $clienteCredito->Saldo_Actual = round($saldoFavorActual + $saldoFavorGenerado, 2);
        $clienteCredito->Estado = ClienteCredito::ESTADO_ACTIVO;
        $clienteCredito->save();
    }

    protected function cargarCreditoSeleccionado(Credito $credito): void
    {
        $credito->loadMissing([
            'venta',
            'venta.cliente.persona',
            'clienteCredito.cliente.persona',
        ]);

        $abonos = $this->obtenerAbonosCredito($credito);

        $cliente = $credito->clienteCredito?->cliente ?? $credito->venta?->cliente;

        $saldoOriginal = $this->calcularTotalCreditoBase($credito, $abonos);
        $saldoPendienteReal = $this->calcularSaldoPendienteReal($credito, $abonos);

        $this->idCreditoSeleccionado = (int) $credito->Id_Credito;
        $this->cliente = $this->nombreCliente($cliente);
        $this->cedula = $this->documentoCliente($cliente);
        $this->codigoCredito = 'CR-' . str_pad((string) $credito->Id_Credito, 5, '0', STR_PAD_LEFT);
        $this->estadoCredito = $saldoPendienteReal <= 0
            ? Credito::ESTADO_CANCELADO
            : ($credito->Estado ?: Credito::ESTADO_PENDIENTE);
        $this->fechaCredito = $this->formatearFecha($credito->Fecha_Credito);
        $this->proximoPago = '—';

        $this->saldoOriginal = $this->formatoMoneda($saldoOriginal);
        $this->saldoPendiente = $this->formatoMoneda($saldoPendienteReal);

        $this->detalleCredito = $this->mapearDetalleCredito($credito, $abonos);

        $this->limpiarPago();
        $this->calcularSaldoFavorVista();
    }

    protected function mapearDetalleCredito(Credito $credito, $abonos = null): array
    {
        $filas = [];

        if ((float) $credito->Abono_Inicial > 0) {
            $filas[] = [
                'numero' => 1,
                'fecha_pago' => $this->formatearFecha($credito->Fecha_Credito),
                'monto' => 'C$ ' . $this->formatoMoneda((float) $credito->Abono_Inicial),
                'moneda' => 'NIO',
                'referencia' => 'Abono inicial',
                'observacion' => 'Pago inicial registrado al crear el crédito',
                'estado' => 'Inicial',
            ];
        }

        $abonos = $abonos ?? $this->obtenerAbonosCredito($credito);

        $numero = count($filas) + 1;

        foreach ($abonos as $abono) {
            $filas[] = [
                'numero' => $numero++,
                'fecha_pago' => $this->formatearFecha($abono->Fecha_Abono),
                'monto' => 'C$ ' . $this->formatoMoneda((float) $abono->Monto_Equivalente_Cordobas),
                'moneda' => $abono->moneda_nombre ?? (string) $abono->Moneda,
                'referencia' => $abono->Numero_Transferencia ?: '—',
                'observacion' => $abono->Observacion ?: '—',
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
            ->where('Saldo_Actual', '>', 0)
            ->orderByDesc('Saldo_Actual');

        if ($filtro !== '') {
            $query->whereHas('cliente', function ($clienteQuery) use ($filtro) {
                $clienteQuery->where(function ($query) use ($filtro) {
                    $query->where('Institucion', 'like', "%{$filtro}%");

                    foreach (['Cedula', 'RUC', 'Telefono_Institucion', 'Correo_Institucion', 'Municipio'] as $columna) {
                        if ($this->clienteTieneColumna($columna)) {
                            $query->orWhere($columna, 'like', "%{$filtro}%");
                        }
                    }

                    $query->orWhereHas('persona', function ($personaQuery) use ($filtro) {
                        $personaQuery->where('Primer_Nombre', 'like', "%{$filtro}%")
                            ->orWhere('Segundo_Nombre', 'like', "%{$filtro}%")
                            ->orWhere('Primer_Apellido', 'like', "%{$filtro}%")
                            ->orWhere('Segundo_Apellido', 'like', "%{$filtro}%")
                            ->orWhere('Telefono', 'like', "%{$filtro}%");
                    });
                });
            });
        }

        $this->clientesSaldoFavor = $query
            ->limit(15)
            ->get()
            ->map(function ($clienteCredito) {
                $saldoFavor = max((float) $clienteCredito->Saldo_Actual, 0);

                return [
                    'cliente' => $this->nombreCliente($clienteCredito->cliente),
                    'documento' => $this->documentoCliente($clienteCredito->cliente),
                    'saldo' => 'C$ ' . $this->formatoMoneda($saldoFavor),
                    'saldo_valor' => $saldoFavor,
                ];
            })
            ->toArray();
    }

    public function limpiarBusqueda(): void
    {
        $this->valorBusqueda = '';
        $this->limpiarSugerenciasCredito();
        $this->limpiarCreditoCargado();
    }

    protected function limpiarSugerenciasCredito(): void
    {
        $this->sugerenciasCredito = [];
        $this->mostrarSugerenciasCredito = false;
    }

    protected function limpiarCreditoCargado(bool $limpiarPago = true): void
    {
        $this->idCreditoSeleccionado = 0;
        $this->cliente = '';
        $this->cedula = '';
        $this->codigoCredito = '';
        $this->estadoCredito = Credito::ESTADO_PENDIENTE;
        $this->fechaCredito = '';
        $this->proximoPago = '';
        $this->saldoOriginal = '0.00';
        $this->saldoPendiente = '0.00';
        $this->detalleCredito = [];

        if ($limpiarPago) {
            $this->limpiarPago();
        }
    }

    public function limpiarPago(bool $limpiarSaldoFavor = true): void
    {
        $this->abonarCordobas = '0.00';
        $this->abonarDolares = '0.00';
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();
        $this->referenciaPago = '';
        $this->observacion = '';
        $this->fechaPago = now()->toDateString();

        if ($limpiarSaldoFavor) {
            $this->saldoFavorVista = '0.00';
        }
    }

    protected function calcularSaldoFavorVista(): void
    {
        $saldo = $this->normalizarDecimal($this->saldoPendiente);
        $cordobas = $this->normalizarDecimal($this->abonarCordobas);
        $dolares = $this->normalizarDecimal($this->abonarDolares);
        $tasa = $this->normalizarDecimal($this->tasaCambio);

        $total = $cordobas + ($dolares * $tasa);
        $saldoFavor = max($total - $saldo, 0);

        $this->saldoFavorVista = $this->formatoMoneda($saldoFavor);
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

    protected function ubicacionCliente($cliente): string
    {
        if (! $cliente) {
            return 'Sin ubicación';
        }

        if (filled($cliente->Municipio ?? null)) {
            return (string) $cliente->Municipio;
        }

        if (filled($cliente->Direccion_Institucion ?? null)) {
            return (string) $cliente->Direccion_Institucion;
        }

        if (filled($cliente->persona?->Direccion ?? null)) {
            return (string) $cliente->persona->Direccion;
        }

        return 'Sin ubicación';
    }

    protected function formatearFecha($fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable) {
            return '—';
        }
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

    protected function notificar(string $type, string $title, string $description = ''): void
    {
        $this->toast(
            type: $type,
            title: $title,
            description: $description,
            position: 'toast-top toast-end',
            icon: match ($type) {
                'success' => 'o-check-circle',
                'error' => 'o-x-circle',
                'warning' => 'o-exclamation-triangle',
                default => 'o-information-circle',
            },
            timeout: 3500
        );
    }
};
?>

@php
    $fieldClass = 'rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#8A97A8] disabled:bg-[#EEF3F8] disabled:text-[#8A97A8] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

    $readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-semibold text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

    $cardClass = 'border border-[#D7E4F3] bg-white shadow-sm [&_.text-base-content\\/70]:text-[#5F6B7A] [&_.text-sm]:text-[#5F6B7A] [&_.text-base-content]:text-[#1A2B42] [&_.card-title]:text-[#1A2B42] [&_label]:text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42]';

    $primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] disabled:border-0 disabled:bg-[#B8CADB] disabled:text-white';

    $pagoBloqueado = $idCreditoSeleccionado === 0
        || $estadoCredito === \App\Models\Credito::ESTADO_CANCELADO
        || (float) str_replace(',', '', $saldoPendiente) <= 0;

    $estadoClass = $estadoCredito === \App\Models\Credito::ESTADO_CANCELADO
        ? 'bg-green-100 text-green-700'
        : ($estadoCredito === \App\Models\Credito::ESTADO_VENCIDO
            ? 'bg-red-100 text-red-700'
            : 'bg-[#EAF4FD] text-[#0B6FE4]');

    $totalSaldoFavor = collect($clientesSaldoFavor)
        ->sum(fn ($item) => (float) ($item['saldo_valor'] ?? 0));
@endphp

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <x-toast />

    <div class="flex shrink-0 items-center justify-between">
        <h1 class="text-2xl font-bold text-[#1A2B42]">Gestión de Créditos</h1>

        @if ($idCreditoSeleccionado > 0)
            <span class="rounded-full border border-[#B7D6F2] bg-[#EAF4FD] px-3 py-1.5 text-xs font-semibold text-[#0B6FE4]">
                {{ $codigoCredito }}
            </span>
        @endif
    </div>

    <div class="grid min-h-0 grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="flex min-h-0 flex-col gap-4 overflow-hidden">
            <x-card title="Buscar crédito" shadow separator class="{{ $cardClass }}">
                <div class="relative">
                    <x-input
                        label="Cliente, crédito o venta"
                        wire:model.live.debounce.300ms="valorBusqueda"
                        placeholder="Ej: Juan Pérez, Comercial Norte, CR-00025 o FV-00025"
                        icon="o-magnifying-glass"
                        autocomplete="off"
                        class="{{ $fieldClass }}"
                    />

                    @if ($mostrarSugerenciasCredito && count($sugerenciasCredito))
                        <div class="absolute left-0 right-0 z-50 mt-2 max-h-72 overflow-auto rounded-2xl border border-[#D7E4F3] bg-white shadow-xl">
                            @foreach ($sugerenciasCredito as $sugerencia)
                                <button
                                    type="button"
                                    wire:click="seleccionarCredito({{ $sugerencia['id'] }})"
                                    class="block w-full border-b border-[#EEF3F8] px-4 py-3 text-left last:border-b-0 hover:bg-[#EAF4FD]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-[#1A2B42]">
                                                {{ $sugerencia['cliente'] }}
                                            </p>

                                            <p class="mt-1 truncate text-xs font-medium text-[#5F6B7A]">
                                                {{ $sugerencia['documento'] }} · {{ $sugerencia['ubicacion'] }}
                                            </p>

                                            <p class="mt-1 truncate text-xs text-[#5F6B7A]">
                                                {{ $sugerencia['codigo'] }} · {{ $sugerencia['factura'] }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="text-xs font-black text-[#1A2B42]">
                                                {{ $sugerencia['saldo'] }}
                                            </p>

                                            <span class="mt-1 inline-flex rounded-full bg-[#EAF4FD] px-2 py-0.5 text-[11px] font-bold text-[#0B6FE4]">
                                                {{ ucfirst(strtolower($sugerencia['estado'])) }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-4">
                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Cliente</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $cliente ?: '—' }}</p>
                        <p class="mt-1 truncate text-xs text-[#5F6B7A]">{{ $cedula ?: 'Sin documento' }}</p>
                    </div>

                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Crédito</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $codigoCredito ?: '—' }}</p>
                        <p class="mt-1 text-xs text-[#5F6B7A]">Fecha: {{ $fechaCredito ?: '—' }}</p>
                    </div>

                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Saldo original</p>
                        <p class="mt-1 text-base font-black text-[#1A2B42]">C$ {{ $saldoOriginal }}</p>
                    </div>

                    <div class="min-w-0 rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Pendiente</p>
                                <p class="mt-1 text-base font-black text-[#1A2B42]">C$ {{ $saldoPendiente }}</p>
                            </div>

                            <span class="{{ $estadoClass }} shrink-0 rounded-full px-2.5 py-1 text-center text-xs font-bold" title="{{ $estadoCredito }}">
                                {{ ucfirst(strtolower($estadoCredito)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Detalle del crédito" shadow separator class="flex min-h-0 flex-1 flex-col {{ $cardClass }}">
                <div class="min-h-[230px] max-h-[360px] overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="h-full max-h-[360px] overflow-auto overscroll-contain">
                        <x-table
                            :headers="$headersDetalle"
                            :rows="$detalleCredito"
                            no-hover
                            class="min-w-[960px] [&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#1A2B42] [&_tbody_tr:hover]:!bg-[#EAF4FD]"
                        >
                            @scope('cell_estado', $fila)
                                <span class="{{ $fila['estado'] === 'Inicial' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ $fila['estado'] }}
                                </span>
                            @endscope

                            @scope('cell_observacion', $fila)
                                <span class="block max-w-[260px] truncate" title="{{ $fila['observacion'] }}">
                                    {{ $fila['observacion'] }}
                                </span>
                            @endscope

                            @scope('cell_referencia', $fila)
                                <span class="block max-w-[160px] truncate" title="{{ $fila['referencia'] }}">
                                    {{ $fila['referencia'] }}
                                </span>
                            @endscope
                        </x-table>
                    </div>
                </div>
            </x-card>
        </div>

        <aside class="flex min-h-0 flex-col gap-4 overflow-hidden xl:sticky xl:top-4 xl:max-h-[calc(100vh-5rem)]">
            <x-card title="Registrar pago" shadow separator class="{{ $cardClass }}">
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
                            label="Saldo a favor"
                            wire:model="saldoFavorVista"
                            prefix="C$"
                            readonly
                            class="rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-black text-[#1C7C45] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]"
                        />

                        <x-input
                            label="Abonar en C$"
                            wire:model.live.debounce.250ms="abonarCordobas"
                            prefix="C$"
                            :disabled="$pagoBloqueado"
                            x-on:focus="$event.target.select()"
                            x-on:mouseup.prevent
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Abonar en US$"
                            wire:model.live.debounce.250ms="abonarDolares"
                            prefix="US$"
                            :disabled="$pagoBloqueado"
                            x-on:focus="$event.target.select()"
                            x-on:mouseup.prevent
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
                            label="Tasa del día"
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
                        <x-button
                            label="Guardar pago"
                            type="submit"
                            spinner="registrarPago"
                            icon="o-check-circle"
                            class="{{ $primaryButtonClass }} w-full"
                            :disabled="$pagoBloqueado"
                        />
                    </div>
                </x-form>
            </x-card>

            <x-card title="Saldo a favor" shadow separator class="flex min-h-0 flex-1 flex-col {{ $cardClass }}">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Clientes</p>
                        <p class="mt-1 text-xl font-black text-[#1A2B42]">{{ count($clientesSaldoFavor) }}</p>
                    </div>

                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Saldo total</p>
                        <p class="mt-1 text-xl font-black text-[#1A2B42]">
                            C$ {{ number_format($totalSaldoFavor, 2, '.', ',') }}
                        </p>
                    </div>
                </div>

                <div class="mt-3">
                    <x-input
                        label="Filtrar"
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
            </x-card>
        </aside>
    </div>
</div>