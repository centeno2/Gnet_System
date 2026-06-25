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

    public int $idClienteSeleccionado = 0;
    public int $idClienteCreditoSeleccionado = 0;

    public string $cliente = '';
    public string $cedula = '';
    public string $codigoCredito = '';
    public string $estadoCredito = 'PENDIENTE';
    public string $fechaCredito = '';
    public string $proximoPago = '';

    public string $saldoOriginal = '0.00';
    public string $saldoPendiente = '0.00';
    public int $totalCreditosPendientes = 0;

    public string $abonarCordobas = '';
    public string $abonarDolares = '';
    public string $tasaCambio = '36.5000';
    public string $saldoFavorVista = '0.00';

    public string $metodoPago = 'efectivo';
    public string $referenciaPago = '';
    public string $fechaPago = '';
    public string $observacion = '';

    public array $metodosPagoOptions = [];
    public array $detalleCredito = [];
    public array $detalleCreditoCompleto = [];
    public array $porPaginaCreditosOptions = [];
    public array $paginasCreditos = [1];

    public int $paginaCreditos = 1;
    public int $porPaginaCreditos = 3;
    public int $totalPaginasCreditos = 1;
    public int $totalFilasCreditos = 0;

    public array $headersDetalle = [];

    public bool $modalVoucherCredito = false;
    public string $voucherCreditoPreviewUrl = '';
    public string $ultimoReciboCreditoNumero = '';

    public function mount(): void
    {
        $this->fechaPago = now()->format('Y-m-d H:i:s');
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();

        $this->metodosPagoOptions = [
            ['id' => 'efectivo', 'name' => 'Efectivo'],
            ['id' => 'transferencia', 'name' => 'Transferencia'],
            ['id' => 'tarjeta', 'name' => 'Tarjeta'],
            ['id' => 'deposito', 'name' => 'Depósito'],
        ];

        $this->porPaginaCreditosOptions = [
            ['id' => 3, 'name' => '3'],
            ['id' => 5, 'name' => '5'],
            ['id' => 10, 'name' => '10'],
            ['id' => 15, 'name' => '15'],
            ['id' => 25, 'name' => '25'],
        ];

        $this->headersDetalle = [
            ['key' => 'codigo', 'label' => 'Crédito', 'class' => 'w-[95px] min-w-[95px]'],
            ['key' => 'fecha_credito', 'label' => 'Fecha', 'class' => 'w-[100px] min-w-[100px]'],
            ['key' => 'factura', 'label' => 'Factura', 'class' => 'w-[190px] min-w-[190px]'],
            ['key' => 'total', 'label' => 'Total crédito', 'class' => 'w-[130px] min-w-[130px]'],
            ['key' => 'abonado', 'label' => 'Abonado', 'class' => 'w-[120px] min-w-[120px]'],
            ['key' => 'pendiente', 'label' => 'Pendiente', 'class' => 'w-[130px] min-w-[130px]'],
            ['key' => 'estado', 'label' => 'Estado', 'class' => 'w-[95px] min-w-[95px]'],
        ];
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
        $this->calcularSaldoFavorVista();
    }

    public function updatedAbonarDolares(): void
    {
        $this->calcularSaldoFavorVista();
    }

    public function updatedTasaCambio(): void
    {
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();
        $this->calcularSaldoFavorVista();
    }

    public function updatedPorPaginaCreditos(): void
    {
        $this->porPaginaCreditos = max(1, (int) $this->porPaginaCreditos);
        $this->paginaCreditos = 1;
        $this->actualizarDetalleCreditoPaginado();
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

        $clientesCredito = ClienteCredito::query()
            ->with(['cliente.persona'])
            ->where(function ($query) use ($busqueda) {
                if (ctype_digit($busqueda)) {
                    $query->where('Id_Cliente_Credito', (int) $busqueda)
                        ->orWhere('Id_Cliente', (int) $busqueda);
                }

                $query->orWhereHas('cliente', function ($clienteQuery) use ($busqueda) {
                    $this->aplicarFiltroCliente($clienteQuery, $busqueda);
                });
            })
            ->orderByDesc('Id_Cliente_Credito')
            ->limit(12)
            ->get();

        $this->sugerenciasCredito = $clientesCredito
            ->map(function (ClienteCredito $clienteCredito) {
                $resumen = $this->resumenClienteCredito($clienteCredito);

                if ($resumen['saldo_pendiente_valor'] <= 0 || $resumen['creditos_pendientes'] <= 0) {
                    return null;
                }

                return [
                    'id' => (int) $clienteCredito->Id_Cliente_Credito,
                    'cliente' => $this->nombreCliente($clienteCredito->cliente),
                    'documento' => $this->documentoCliente($clienteCredito->cliente),
                    'ubicacion' => $this->ubicacionCliente($clienteCredito->cliente),
                    'factura' => $resumen['creditos_pendientes'] . ' crédito(s) pendiente(s)',
                    'estado' => 'CUENTA',
                    'saldo' => 'C$ ' . $this->formatoMoneda($resumen['saldo_pendiente_valor']),
                ];
            })
            ->filter()
            ->take(8)
            ->values()
            ->toArray();

        $this->mostrarSugerenciasCredito = count($this->sugerenciasCredito) > 0;
    }

    public function seleccionarClienteCredito(int $idClienteCredito): void
    {
        $clienteCredito = ClienteCredito::query()
            ->with(['cliente.persona'])
            ->whereKey($idClienteCredito)
            ->first();

        if (! $clienteCredito) {
            $this->limpiarCreditoCargado();

            $this->notificar(
                type: 'error',
                title: 'Cliente no encontrado',
                description: 'El cliente seleccionado ya no está disponible.'
            );

            return;
        }

        $this->cargarClienteSeleccionado($clienteCredito);
        $this->valorBusqueda = $this->cliente;
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

        $this->fechaPago = now()->format('Y-m-d H:i:s');
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();

        $validator = Validator::make(
            [
                'idClienteCreditoSeleccionado' => $this->idClienteCreditoSeleccionado,
                'abonarCordobas' => $this->abonarCordobas,
                'abonarDolares' => $this->abonarDolares,
                'tasaCambio' => $this->tasaCambio,
                'metodoPago' => $this->metodoPago,
                'referenciaPago' => $this->referenciaPago,
                'observacion' => $this->observacion,
            ],
            [
                'idClienteCreditoSeleccionado' => 'required|integer|exists:cliente_credito,Id_Cliente_Credito',
                'abonarCordobas' => 'nullable|numeric|min:0',
                'abonarDolares' => 'nullable|numeric|min:0',
                'tasaCambio' => 'nullable|numeric|min:0',
                'metodoPago' => 'required|in:efectivo,transferencia,tarjeta,deposito',
                'referenciaPago' => 'nullable|string|max:100',
                'observacion' => 'nullable|string|max:300',
            ],
            [
                'idClienteCreditoSeleccionado.required' => 'Debe seleccionar un cliente con créditos pendientes.',
                'idClienteCreditoSeleccionado.exists' => 'El cliente seleccionado no tiene cuenta de crédito.',
                'abonarCordobas.numeric' => 'El abono en córdobas debe ser numérico.',
                'abonarCordobas.min' => 'El abono en córdobas no puede ser negativo.',
                'abonarDolares.numeric' => 'El abono en dólares debe ser numérico.',
                'abonarDolares.min' => 'El abono en dólares no puede ser negativo.',
                'tasaCambio.numeric' => 'La tasa de cambio debe ser numérica.',
                'tasaCambio.min' => 'La tasa de cambio no puede ser negativa.',
                'metodoPago.required' => 'Debe seleccionar el método de pago.',
                'metodoPago.in' => 'El método de pago seleccionado no es válido.',
                'referenciaPago.max' => 'La referencia no debe superar los 100 caracteres.',
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
                title: 'Datos inválidos',
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
        $creditosCancelados = 0;
        $fechaHoraPago = now();
        $numeroRecibo = $this->generarNumeroReciboCredito();

        try {
            DB::transaction(function () use (
                $datos,
                $montoCordobas,
                $montoDolares,
                $tasaCambio,
                $idUsuario,
                $fechaHoraPago,
                $numeroRecibo,
                &$montoAplicadoTotal,
                &$saldoFavorGenerado,
                &$creditosCancelados
            ) {
                $clienteCredito = ClienteCredito::query()
                    ->with(['cliente.persona'])
                    ->whereKey((int) $datos['idClienteCreditoSeleccionado'])
                    ->lockForUpdate()
                    ->first();

                if (! $clienteCredito) {
                    throw ValidationException::withMessages([
                        'idClienteCreditoSeleccionado' => 'El cliente seleccionado no tiene cuenta de crédito.',
                    ]);
                }

                $idCliente = (int) $clienteCredito->Id_Cliente;

                $creditos = $this->obtenerCreditosCliente(
                    idCliente: $idCliente,
                    idClienteCredito: (int) $clienteCredito->Id_Cliente_Credito,
                    bloquear: true
                );

                $creditosConSaldo = collect();
                $saldoPendienteCuenta = 0.0;

                foreach ($creditos as $credito) {
                    $abonosPrevios = $this->obtenerAbonosCredito($credito, true);
                    $saldoPendienteReal = $this->calcularSaldoPendienteReal($credito, $abonosPrevios);

                    if ($saldoPendienteReal <= 0) {
                        $credito->Saldo_Actual = 0;
                        $credito->Estado = Credito::ESTADO_CANCELADO;
                        $credito->save();
                        continue;
                    }

                    if (! $credito->Id_Cliente_Credito) {
                        $credito->Id_Cliente_Credito = (int) $clienteCredito->Id_Cliente_Credito;
                    }

                    $credito->Saldo_Actual = $saldoPendienteReal;
                    $credito->Estado = $this->resolverEstadoCredito($saldoPendienteReal, $credito, $abonosPrevios);
                    $credito->save();

                    $creditosConSaldo->push([
                        'credito' => $credito,
                        'saldo' => $saldoPendienteReal,
                    ]);

                    $saldoPendienteCuenta = round($saldoPendienteCuenta + $saldoPendienteReal, 2);
                }

                if ($creditosConSaldo->isEmpty() || $saldoPendienteCuenta <= 0) {
                    throw ValidationException::withMessages([
                        'idClienteCreditoSeleccionado' => 'Este cliente no tiene créditos pendientes por cancelar.',
                    ]);
                }

                $totalRecibidoCordobas = round($montoCordobas + ($montoDolares * $tasaCambio), 2);

                if ($totalRecibidoCordobas < $saldoPendienteCuenta) {
                    throw ValidationException::withMessages([
                        'abonarCordobas' => 'El pago debe cancelar todo el saldo pendiente de la institución. Pendiente: C$ ' . $this->formatoMoneda($saldoPendienteCuenta) . '.',
                    ]);
                }

                $saldoFavorGenerado = max(round($totalRecibidoCordobas - $saldoPendienteCuenta, 2), 0);

                $bolsasPago = [
                    [
                        'moneda' => AbonoCredito::MONEDA_CORDOBA,
                        'monto_disponible' => $montoCordobas,
                        'cordobas_disponible' => $montoCordobas,
                        'tipo_cambio' => 1.0,
                    ],
                    [
                        'moneda' => AbonoCredito::MONEDA_DOLAR,
                        'monto_disponible' => $montoDolares,
                        'cordobas_disponible' => round($montoDolares * $tasaCambio, 2),
                        'tipo_cambio' => $tasaCambio,
                    ],
                ];

                foreach ($creditosConSaldo as $item) {
                    /** @var Credito $credito */
                    $credito = $item['credito'];
                    $saldoCredito = round((float) $item['saldo'], 2);
                    $aplicadoCredito = 0.0;

                    foreach ($bolsasPago as $indice => $bolsa) {
                        if ($saldoCredito <= 0) {
                            break;
                        }

                        if ($bolsasPago[$indice]['cordobas_disponible'] <= 0) {
                            continue;
                        }

                        $aplicadoCordobas = min($saldoCredito, $bolsasPago[$indice]['cordobas_disponible']);
                        $aplicadoCordobas = round($aplicadoCordobas, 2);

                        if ($aplicadoCordobas <= 0) {
                            continue;
                        }

                        $montoMoneda = $bolsa['moneda'] === AbonoCredito::MONEDA_DOLAR
                            ? round($aplicadoCordobas / $tasaCambio, 2)
                            : $aplicadoCordobas;

                        $this->crearAbonoCredito(
                            credito: $credito,
                            numeroRecibo: $numeroRecibo,
                            idUsuario: $idUsuario,
                            moneda: $bolsa['moneda'],
                            monto: $montoMoneda,
                            tipoCambio: $bolsa['tipo_cambio'],
                            montoEquivalenteCordobas: $aplicadoCordobas,
                            fechaPago: $fechaHoraPago,
                            referencia: $datos['referenciaPago'] ?? null,
                            metodoPago: $datos['metodoPago'],
                            observacion: $datos['observacion'] ?? null
                        );

                        $bolsasPago[$indice]['monto_disponible'] = max(round($bolsasPago[$indice]['monto_disponible'] - $montoMoneda, 2), 0);
                        $bolsasPago[$indice]['cordobas_disponible'] = max(round($bolsasPago[$indice]['cordobas_disponible'] - $aplicadoCordobas, 2), 0);

                        $saldoCredito = max(round($saldoCredito - $aplicadoCordobas, 2), 0);
                        $aplicadoCredito = round($aplicadoCredito + $aplicadoCordobas, 2);
                        $montoAplicadoTotal = round($montoAplicadoTotal + $aplicadoCordobas, 2);
                    }

                    if ($aplicadoCredito > 0) {
                        $nuevoSaldoCredito = max(round((float) $item['saldo'] - $aplicadoCredito, 2), 0);

                        $credito->Saldo_Actual = $nuevoSaldoCredito;
                        $credito->Estado = $nuevoSaldoCredito <= 0
                            ? Credito::ESTADO_CANCELADO
                            : Credito::ESTADO_PARCIAL;
                        $credito->save();

                        if ($nuevoSaldoCredito <= 0) {
                            $creditosCancelados++;
                        }
                    }
                }

                if ($montoAplicadoTotal <= 0) {
                    throw ValidationException::withMessages([
                        'abonarCordobas' => 'No se pudo aplicar el monto recibido a los créditos pendientes.',
                    ]);
                }

                if ($saldoFavorGenerado > 0) {
                    $this->sumarSaldoFavorClienteCredito(
                        clienteCredito: $clienteCredito,
                        saldoFavorGenerado: $saldoFavorGenerado
                    );
                }

                DB::table('credito_recibo')->insert([
                    'Numero_Recibo' => $numeroRecibo,
                    'Id_Cliente_Credito' => (int) $clienteCredito->Id_Cliente_Credito,
                    'Id_Cliente' => (int) $clienteCredito->Id_Cliente,
                    'Id_Usuario' => $idUsuario,
                    'Fecha_Recibo' => $fechaHoraPago,
                    'Metodo_Pago' => $datos['metodoPago'],
                    'Referencia' => filled($datos['referenciaPago'] ?? null) ? trim((string) $datos['referenciaPago']) : null,
                    'Observacion' => filled($datos['observacion'] ?? null) ? trim((string) $datos['observacion']) : null,
                    'Monto_Cordobas' => $montoCordobas,
                    'Monto_Dolares' => $montoDolares,
                    'Tasa_Cambio' => $tasaCambio,
                    'Total_Pendiente_Antes' => $saldoPendienteCuenta,
                    'Total_Recibido_Cordobas' => $totalRecibidoCordobas,
                    'Total_Aplicado_Cordobas' => $montoAplicadoTotal,
                    'Saldo_Favor_Generado' => $saldoFavorGenerado,
                    'Creditos_Cancelados' => $creditosCancelados,
                    'Estado' => 'REGISTRADO',
                    'Fecha_Registro' => now(),
                ]);
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
                description: collect($e->errors())->flatten()->first() ?? 'No se pudo validar el cliente.'
            );

            return;
        }

        $mensaje = 'Pago guardado. Aplicado: C$ ' . $this->formatoMoneda($montoAplicadoTotal) . '.';

        if ($creditosCancelados > 0) {
            $mensaje .= ' Créditos cancelados: ' . $creditosCancelados . '.';
        }

        if ($saldoFavorGenerado > 0) {
            $mensaje .= ' Saldo a favor: C$ ' . $this->formatoMoneda($saldoFavorGenerado) . '.';
        }

        $this->limpiarBusqueda();
        $this->prepararVoucherCredito($numeroRecibo);

        $this->notificar(
            type: 'success',
            title: 'Pago registrado',
            description: $mensaje
        );
    }

    protected function generarNumeroReciboCredito(): string
    {
        do {
            $numero = 'REC-ABO-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (DB::table('credito_recibo')->where('Numero_Recibo', $numero)->exists());

        return $numero;
    }

    protected function prepararVoucherCredito(string $numeroRecibo): void
    {
        $this->ultimoReciboCreditoNumero = $numeroRecibo;
        $this->voucherCreditoPreviewUrl = route('creditos.voucher', [
            'recibo' => $numeroRecibo,
            'ancho' => 80,
        ]);
        $this->modalVoucherCredito = true;
    }

    public function cerrarModalVoucherCredito(): void
    {
        $this->modalVoucherCredito = false;
        $this->voucherCreditoPreviewUrl = '';
        $this->ultimoReciboCreditoNumero = '';
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

        $posiblesIds = collect();

        if ($usuarioAutenticado) {
            $posiblesIds = $posiblesIds->merge([
                $usuarioAutenticado->Id_Usuario ?? null,
                method_exists($usuarioAutenticado, 'getAttribute') ? $usuarioAutenticado->getAttribute('Id_Usuario') : null,
                method_exists($usuarioAutenticado, 'getAttribute') ? $usuarioAutenticado->getAttribute('id_usuario') : null,
                method_exists($usuarioAutenticado, 'getAttribute') ? $usuarioAutenticado->getAttribute('usuario_id') : null,
                method_exists($usuarioAutenticado, 'getAuthIdentifier') ? $usuarioAutenticado->getAuthIdentifier() : null,
                auth()->id(),
            ]);
        }

        $posiblesIds = $posiblesIds->merge([
            session('Id_Usuario'),
            session('id_usuario'),
            session('usuario_id'),
            session('user_id'),
        ]);

        $posiblesIds = $posiblesIds
            ->filter(fn ($valor) => filled($valor) && is_numeric($valor))
            ->map(fn ($valor) => (int) $valor)
            ->unique()
            ->values();

        if ($posiblesIds->isEmpty()) {
            throw ValidationException::withMessages([
                'Id_Usuario' => 'No hay un usuario autenticado para registrar el abono.',
            ]);
        }

        $usuario = Usuario::query()
            ->whereIn('Id_Usuario', $posiblesIds->all())
            ->first();

        if (! $usuario) {
            throw ValidationException::withMessages([
                'Id_Usuario' => 'El usuario autenticado no está vinculado con la tabla usuario.',
            ]);
        }

        return (int) $usuario->Id_Usuario;
    }

    protected function obtenerCreditosCliente(int $idCliente, int $idClienteCredito = 0, bool $bloquear = false)
    {
        $query = Credito::query()
            ->with(['venta', 'venta.cliente.persona', 'clienteCredito', 'clienteCredito.cliente.persona'])
            ->where(function ($query) use ($idCliente, $idClienteCredito) {
                if ($idClienteCredito > 0) {
                    $query->where('Id_Cliente_Credito', $idClienteCredito);
                }

                $query->orWhereHas('venta', function ($ventaQuery) use ($idCliente) {
                    $ventaQuery->where('Id_Cliente', $idCliente);
                });
            })
            ->where(function ($query) {
                $query->whereNull('Estado')
                    ->orWhereIn('Estado', [
                        Credito::ESTADO_PENDIENTE,
                        Credito::ESTADO_PARCIAL,
                        Credito::ESTADO_VENCIDO,
                    ]);
            })
            ->orderBy('Fecha_Credito')
            ->orderBy('Id_Credito');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    protected function resumenClienteCredito(ClienteCredito $clienteCredito): array
    {
        $creditos = $this->obtenerCreditosCliente(
            idCliente: (int) $clienteCredito->Id_Cliente,
            idClienteCredito: (int) $clienteCredito->Id_Cliente_Credito
        );

        $saldoOriginal = 0.0;
        $saldoPendiente = 0.0;
        $creditosPendientes = 0;

        foreach ($creditos as $credito) {
            $abonos = $this->obtenerAbonosCredito($credito);
            $pendiente = $this->calcularSaldoPendienteReal($credito, $abonos);

            if ($pendiente <= 0) {
                continue;
            }

            $saldoOriginal = round($saldoOriginal + $this->calcularTotalCreditoBase($credito, $abonos), 2);
            $saldoPendiente = round($saldoPendiente + $pendiente, 2);
            $creditosPendientes++;
        }

        return [
            'saldo_original_valor' => $saldoOriginal,
            'saldo_pendiente_valor' => $saldoPendiente,
            'creditos_pendientes' => $creditosPendientes,
        ];
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
        string $numeroRecibo,
        int $idUsuario,
        string $moneda,
        float $monto,
        float $tipoCambio,
        float $montoEquivalenteCordobas,
        Carbon|string $fechaPago,
        ?string $referencia,
        string $metodoPago,
        ?string $observacion
    ): void {
        if ($monto <= 0 || $montoEquivalenteCordobas <= 0) {
            return;
        }

        $usuarioExiste = Usuario::query()
            ->whereKey($idUsuario)
            ->exists();

        if (! $usuarioExiste) {
            throw ValidationException::withMessages([
                'Id_Usuario' => 'El usuario que intenta registrar el abono no existe.',
            ]);
        }

        $textoObservacion = trim(
            'Método: ' . ucfirst($metodoPago) .
            ($observacion ? '. ' . trim($observacion) : '')
        );

        $abono = new AbonoCredito();

        $abono->Id_Credito = (int) $credito->Id_Credito;
        $abono->Id_Usuario = $idUsuario;
        $abono->Numero_Recibo = $numeroRecibo;
        $abono->Fecha_Abono = $fechaPago;
        $abono->Moneda = $moneda;
        $abono->Monto = round($monto, 2);
        $abono->Tipo_Cambio = round($tipoCambio, 4);
        $abono->Monto_Equivalente_Cordobas = round($montoEquivalenteCordobas, 2);
        $abono->Numero_Transferencia = filled($referencia) ? trim((string) $referencia) : null;
        $abono->Observacion = $textoObservacion;

        $abono->save();
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

    protected function cargarClienteSeleccionado(ClienteCredito $clienteCredito): void
    {
        $clienteCredito->loadMissing(['cliente.persona']);

        $resumen = $this->resumenClienteCredito($clienteCredito);
        $creditos = $this->obtenerCreditosCliente(
            idCliente: (int) $clienteCredito->Id_Cliente,
            idClienteCredito: (int) $clienteCredito->Id_Cliente_Credito
        );

        $this->idClienteSeleccionado = (int) $clienteCredito->Id_Cliente;
        $this->idClienteCreditoSeleccionado = (int) $clienteCredito->Id_Cliente_Credito;
        $this->cliente = $this->nombreCliente($clienteCredito->cliente);
        $this->cedula = $this->documentoCliente($clienteCredito->cliente);
        $this->codigoCredito = 'Cuenta #' . str_pad((string) $clienteCredito->Id_Cliente_Credito, 5, '0', STR_PAD_LEFT);
        $this->estadoCredito = $resumen['saldo_pendiente_valor'] <= 0
            ? Credito::ESTADO_CANCELADO
            : Credito::ESTADO_PENDIENTE;
        $this->fechaCredito = $this->formatearFecha($creditos->min('Fecha_Credito'));
        $this->proximoPago = '—';
        $this->totalCreditosPendientes = (int) $resumen['creditos_pendientes'];

        $this->saldoOriginal = $this->formatoMoneda($resumen['saldo_original_valor']);
        $this->saldoPendiente = $this->formatoMoneda($resumen['saldo_pendiente_valor']);

        $this->detalleCreditoCompleto = $this->mapearDetalleCreditosCliente($creditos);
        $this->paginaCreditos = 1;
        $this->actualizarDetalleCreditoPaginado();

        $this->limpiarPago();
        $this->calcularSaldoFavorVista();
    }

    protected function actualizarDetalleCreditoPaginado(): void
    {
        $this->porPaginaCreditos = max(1, (int) $this->porPaginaCreditos);
        $this->totalFilasCreditos = count($this->detalleCreditoCompleto);
        $this->totalPaginasCreditos = max((int) ceil($this->totalFilasCreditos / $this->porPaginaCreditos), 1);

        if ($this->paginaCreditos > $this->totalPaginasCreditos) {
            $this->paginaCreditos = $this->totalPaginasCreditos;
        }

        if ($this->paginaCreditos < 1) {
            $this->paginaCreditos = 1;
        }

        $inicio = ($this->paginaCreditos - 1) * $this->porPaginaCreditos;
        $this->detalleCredito = array_slice($this->detalleCreditoCompleto, $inicio, $this->porPaginaCreditos);
        $this->paginasCreditos = $this->resolverPaginasCreditos();
    }

    protected function resolverPaginasCreditos(): array
    {
        $paginas = collect([
            1,
            $this->totalPaginasCreditos,
            $this->paginaCreditos - 1,
            $this->paginaCreditos,
            $this->paginaCreditos + 1,
        ])
            ->filter(fn ($pagina) => $pagina >= 1 && $pagina <= $this->totalPaginasCreditos)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $resultado = [];
        $paginaAnterior = null;

        foreach ($paginas as $pagina) {
            if ($paginaAnterior !== null && $pagina - $paginaAnterior > 1) {
                $resultado[] = null;
            }

            $resultado[] = (int) $pagina;
            $paginaAnterior = (int) $pagina;
        }

        return $resultado ?: [1];
    }

    public function irPaginaCreditos(int $pagina): void
    {
        $this->paginaCreditos = max(1, min($pagina, $this->totalPaginasCreditos));
        $this->actualizarDetalleCreditoPaginado();
    }

    public function anteriorPaginaCreditos(): void
    {
        $this->irPaginaCreditos($this->paginaCreditos - 1);
    }

    public function siguientePaginaCreditos(): void
    {
        $this->irPaginaCreditos($this->paginaCreditos + 1);
    }

    protected function resetearPaginadoCreditos(): void
    {
        $this->detalleCreditoCompleto = [];
        $this->detalleCredito = [];
        $this->paginaCreditos = 1;
        $this->totalPaginasCreditos = 1;
        $this->totalFilasCreditos = 0;
        $this->paginasCreditos = [1];
    }

    protected function mapearDetalleCreditosCliente($creditos): array
    {
        $filas = [];

        foreach ($creditos as $credito) {
            $abonos = $this->obtenerAbonosCredito($credito);
            $pendiente = $this->calcularSaldoPendienteReal($credito, $abonos);

            if ($pendiente <= 0) {
                continue;
            }

            $total = $this->calcularTotalCreditoBase($credito, $abonos);
            $abonado = max(round($total - $pendiente, 2), 0);
            $estado = $pendiente <= 0 ? Credito::ESTADO_CANCELADO : ($credito->Estado ?: Credito::ESTADO_PENDIENTE);

            $filas[] = [
                'codigo' => 'CR-' . str_pad((string) $credito->Id_Credito, 5, '0', STR_PAD_LEFT),
                'fecha_credito' => $this->formatearFecha($credito->Fecha_Credito),
                'factura' => $credito->venta?->Numero_Factura ?: 'Sin factura',
                'total' => 'C$ ' . $this->formatoMoneda($total),
                'abonado' => 'C$ ' . $this->formatoMoneda($abonado),
                'pendiente' => 'C$ ' . $this->formatoMoneda($pendiente),
                'estado' => $estado,
            ];
        }

        return $filas;
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
        $this->idClienteSeleccionado = 0;
        $this->idClienteCreditoSeleccionado = 0;
        $this->cliente = '';
        $this->cedula = '';
        $this->codigoCredito = '';
        $this->estadoCredito = Credito::ESTADO_PENDIENTE;
        $this->fechaCredito = '';
        $this->proximoPago = '';
        $this->saldoOriginal = '0.00';
        $this->saldoPendiente = '0.00';
        $this->totalCreditosPendientes = 0;
        $this->resetearPaginadoCreditos();

        if ($limpiarPago) {
            $this->limpiarPago();
        }
    }

    public function limpiarPago(bool $limpiarSaldoFavor = true): void
    {
        $this->abonarCordobas = '';
        $this->abonarDolares = '';
        $this->tasaCambio = $this->obtenerTasaCambioDelDia();
        $this->referenciaPago = '';
        $this->observacion = '';
        $this->fechaPago = now()->format('Y-m-d H:i:s');

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
$fieldClass = 'rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#8A97A8] disabled:bg-[#EEF3F8]
disabled:text-[#8A97A8] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

$readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-semibold text-[#1A2B42]
[&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';

$cardClass = 'border border-[#D7E4F3] bg-white shadow-sm [&_.text-base-content\/70]:text-[#5F6B7A]
[&_.text-sm]:text-[#5F6B7A] [&_.text-base-content]:text-[#1A2B42] [&_.card-title]:text-[#1A2B42]
[&_label]:text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42]';

$primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] disabled:border-0
disabled:bg-[#B8CADB] disabled:text-white';

$pagoBloqueado = $idClienteCreditoSeleccionado === 0
|| $estadoCredito === \App\Models\Credito::ESTADO_CANCELADO
|| (float) str_replace(',', '', $saldoPendiente) <= 0;
    $estadoClass=$estadoCredito===\App\Models\Credito::ESTADO_CANCELADO ? 'bg-green-100 text-green-700' :
    ($estadoCredito===\App\Models\Credito::ESTADO_VENCIDO ? 'bg-red-100 text-red-700' : 'bg-[#EAF4FD] text-[#0B6FE4]' );

$inicioCreditos = $totalFilasCreditos === 0 ? 0 : (($paginaCreditos - 1) * $porPaginaCreditos) + 1;
$finCreditos = min($paginaCreditos * $porPaginaCreditos, $totalFilasCreditos);
    @endphp <div
    class="flex min-h-[calc(100vh-3rem)] w-full flex-col gap-3 overflow-y-auto overflow-x-hidden bg-[#F0F3F7] px-4 py-3 md:px-5 md:py-4">
    <x-toast />

    <div class="flex shrink-0 items-center justify-between">
        <h1 class="text-[1.65rem] font-bold leading-none text-[#1A2B42]">Gestión de Créditos</h1>

        @if ($idClienteCreditoSeleccionado > 0)
        <span
            class="rounded-full border border-[#B7D6F2] bg-[#EAF4FD] px-3 py-1.5 text-xs font-semibold text-[#0B6FE4]">
            {{ $codigoCredito }} · {{ $totalCreditosPendientes }} crédito(s)
        </span>
        @endif
    </div>

    <div class="grid min-h-0 grid-cols-1 items-start gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(380px,400px)]">
        <div class="flex min-h-0 flex-col gap-3 overflow-visible">
            <x-card shadow class="{{ $cardClass }} [&_.card-body]:gap-0 [&_.card-body]:p-4">
                <h2 class="mb-3 text-xl font-bold leading-none text-[#1A2B42]">Buscar cliente con crédito</h2>

                <div class="relative">
                    <x-input label="Cliente" wire:model.live.debounce.300ms="valorBusqueda"
                        placeholder="Buscar por cliente, RUC, cédula o teléfono" icon="o-magnifying-glass"
                        autocomplete="off" class="{{ $fieldClass }}" />

                    @if ($mostrarSugerenciasCredito && count($sugerenciasCredito))
                    <div
                        class="absolute left-0 right-0 z-50 mt-2 max-h-72 overflow-auto rounded-2xl border border-[#D7E4F3] bg-white shadow-xl">
                        @foreach ($sugerenciasCredito as $sugerencia)
                        <button type="button" wire:click="seleccionarClienteCredito({{ $sugerencia['id'] }})"
                            class="block w-full border-b border-[#EEF3F8] px-4 py-3 text-left last:border-b-0 hover:bg-[#EAF4FD]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-[#1A2B42]">
                                        {{ $sugerencia['cliente'] }}
                                    </p>

                                    <p class="mt-1 truncate text-xs font-medium text-[#5F6B7A]">
                                        {{ $sugerencia['documento'] }} · {{ $sugerencia['ubicacion'] }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-[#5F6B7A]">
                                        {{ $sugerencia['factura'] }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-black text-[#1A2B42]">
                                        {{ $sugerencia['saldo'] }}
                                    </p>

                                    <span
                                        class="mt-1 inline-flex rounded-full bg-[#EAF4FD] px-2 py-0.5 text-[11px] font-bold text-[#0B6FE4]">
                                        Cuenta
                                    </span>
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="mt-3 grid grid-cols-1 gap-2.5 md:grid-cols-2 2xl:grid-cols-4">
                    <div class="min-w-0 rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-2.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Cliente</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $cliente ?: '—' }}</p>
                        <p class="mt-1 truncate text-xs text-[#5F6B7A]">{{ $cedula ?: 'Sin documento' }}</p>
                    </div>

                    <div class="min-w-0 rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-2.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Cuenta</p>
                        <p class="mt-1 truncate text-sm font-bold text-[#1A2B42]">{{ $codigoCredito ?: '—' }}</p>
                        <p class="mt-1 text-xs text-[#5F6B7A]">Créditos pendientes: {{ $totalCreditosPendientes }}</p>
                    </div>

                    <div class="min-w-0 rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-2.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Total original</p>
                        <p class="mt-1 text-[0.98rem] font-black text-[#1A2B42]">C$ {{ $saldoOriginal }}</p>
                    </div>

                    <div class="min-w-0 rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Total pendiente
                                </p>
                                <p class="mt-1 text-[0.98rem] font-black text-[#1A2B42]">C$ {{ $saldoPendiente }}</p>
                            </div>

                            <span
                                class="{{ $estadoClass }} shrink-0 rounded-full px-2.5 py-1 text-center text-xs font-bold"
                                title="{{ $estadoCredito }}">
                                {{ ucfirst(strtolower($estadoCredito)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card shadow
                class="flex min-h-0 shrink-0 flex-col overflow-hidden {{ $cardClass }} [&_.card-body]:gap-0 [&_.card-body]:p-3">
                <div class="mb-1.5 flex flex-col gap-1.5 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold leading-none text-[#1A2B42]">Créditos pendientes del cliente</h2>
                        <p class="mt-1 text-sm font-semibold text-[#5F6B7A]">
                            Mostrando {{ $inicioCreditos }} - {{ $finCreditos }} de {{ $totalFilasCreditos }} crédito(s)
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span class="text-xs font-semibold text-[#5F6B7A]">Filas</span>
                        <x-select wire:model.live="porPaginaCreditos" :options="$porPaginaCreditosOptions"
                            option-value="id" option-label="name" class="{{ $fieldClass }} w-[5.5rem]" />
                    </div>
                </div>

                <div class="h-[145px] min-h-[145px] max-h-[145px] w-full min-w-0 overflow-hidden rounded-xl border border-[#D7E4F3]">
                    <div class="h-full w-full min-w-0 overflow-auto overscroll-contain">
                        <x-table :headers="$headersDetalle" :rows="$detalleCredito" no-hover
                            class="w-full min-w-[860px] table-fixed text-sm [&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:px-3 [&_thead_th]:py-1.5 [&_thead_th]:font-semibold [&_thead_th]:text-white [&_thead_th]:whitespace-nowrap [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:px-3 [&_tbody_td]:py-1.5 [&_tbody_td]:text-[#1A2B42] [&_tbody_td]:whitespace-nowrap [&_tbody_tr:hover]:bg-[#EAF4FD]!">
                            @scope('cell_codigo', $fila)
                            <span class="block truncate font-semibold text-[#1A2B42]" title="{{ $fila['codigo'] }}">{{ $fila['codigo'] }}</span>
                            @endscope

                            @scope('cell_fecha_credito', $fila)
                            <span class="block truncate" title="{{ $fila['fecha_credito'] }}">{{ $fila['fecha_credito'] }}</span>
                            @endscope

                            @scope('cell_factura', $fila)
                            <span class="block truncate" title="{{ $fila['factura'] }}">{{ $fila['factura'] }}</span>
                            @endscope

                            @scope('cell_total', $fila)
                            <span class="block truncate" title="{{ $fila['total'] }}">{{ $fila['total'] }}</span>
                            @endscope

                            @scope('cell_abonado', $fila)
                            <span class="block truncate" title="{{ $fila['abonado'] }}">{{ $fila['abonado'] }}</span>
                            @endscope

                            @scope('cell_pendiente', $fila)
                            <span class="block truncate font-semibold text-[#1A2B42]" title="{{ $fila['pendiente'] }}">{{ $fila['pendiente'] }}</span>
                            @endscope

                            @scope('cell_estado', $fila)
                            <span
                                class="{{ $fila['estado'] === \App\Models\Credito::ESTADO_VENCIDO ? 'bg-red-100 text-red-700' : 'bg-[#EAF4FD] text-[#0B6FE4]' }} inline-flex max-w-full truncate rounded-full px-2.5 py-1 text-xs font-semibold"
                                title="{{ $fila['estado'] }}">
                                {{ ucfirst(strtolower($fila['estado'])) }}
                            </span>
                            @endscope
                        </x-table>
                    </div>
                </div>

                @if ($totalFilasCreditos > 0)
                <div class="mt-1.5 flex flex-col gap-1.5 md:flex-row md:items-center md:justify-between">
                    <p class="text-xs font-semibold text-[#5F6B7A]">
                        Página {{ $paginaCreditos }} de {{ $totalPaginasCreditos }}
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <x-button label="Anterior" icon="o-chevron-left" wire:click="anteriorPaginaCreditos"
                            class="btn-sm border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                            :disabled="$paginaCreditos <= 1" />

                        @foreach ($paginasCreditos as $pagina)
                        @if ($pagina === null)
                        <span class="px-1 text-sm font-bold text-[#7B8794]">...</span>
                        @else
                        <x-button label="{{ $pagina }}" wire:click="irPaginaCreditos({{ $pagina }})"
                            class="{{ $pagina === $paginaCreditos ? 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]' : 'btn-sm border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]' }}" />
                        @endif
                        @endforeach

                        <x-button label="Siguiente" icon-right="o-chevron-right" wire:click="siguientePaginaCreditos"
                            class="btn-sm border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                            :disabled="$paginaCreditos >= $totalPaginasCreditos" />
                    </div>
                </div>
                @endif
            </x-card>
        </div>

        <aside class="flex min-h-0 flex-col gap-3 overflow-visible xl:sticky xl:top-3 xl:max-h-none">
            <x-card shadow class="{{ $cardClass }} [&_.card-body]:gap-0 [&_.card-body]:p-4">
                <h2 class="mb-3 text-xl font-bold leading-none text-[#1A2B42]">Registrar pago a cuenta</h2>

                <x-form wire:submit="registrarPago" no-separator>
                    <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-2">
                        <x-input label="Total pendiente" wire:model="saldoPendiente" prefix="C$" readonly
                            class="{{ $readonlyFieldClass }}" />

                        <x-input label="Saldo a favor" wire:model="saldoFavorVista" prefix="C$" readonly
                            class="rounded-xl border-[#D7E4F3] bg-[#EEF3F8] font-black text-[#1C7C45] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]" />

                        <x-input label="Abonar en C$" wire:model.live.debounce.250ms="abonarCordobas" placeholder="0.00"
                            type="number" step="0.01" inputmode="decimal" prefix="C$" :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}" />

                        <x-input label="Abonar en US$" wire:model.live.debounce.250ms="abonarDolares" placeholder="0.00"
                            type="number" step="0.01" inputmode="decimal" prefix="US$" :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}" />

                        <x-select label="Método" wire:model="metodoPago" :options="$metodosPagoOptions"
                            option-value="id" option-label="name" :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}" />

                        <x-input label="Tasa del día" wire:model="tasaCambio" prefix="C$" readonly
                            class="{{ $readonlyFieldClass }}" />

                        <x-input label="Referencia" wire:model="referenciaPago"
                            placeholder="Voucher o transferencia" :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }} md:col-span-2" />
                    </div>

                    <div class="mt-2.5">
                        <x-textarea label="Observación" wire:model="observacion" rows="2"
                            placeholder="Observación del pago" :disabled="$pagoBloqueado"
                            class="{{ $fieldClass }}" />
                    </div>

                    <div class="mt-3 border-t border-[#D7E4F3] bg-white pt-3">
                        <x-button label="Guardar pago" type="submit" spinner="registrarPago" icon="o-check-circle"
                            class="{{ $primaryButtonClass }} w-full" :disabled="$pagoBloqueado" />
                    </div>
                </x-form>
            </x-card>
        </aside>
    </div>

    <x-modal wire:model="modalVoucherCredito" class="backdrop-blur-sm"
        box-class="w-full max-w-md rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="mb-4">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Voucher de crédito</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                {{ $ultimoReciboCreditoNumero ?: 'Pago registrado' }}
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-[#F8FBFF]">
            @if ($voucherCreditoPreviewUrl !== '')
            <iframe src="{{ $voucherCreditoPreviewUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" loading="eager"
                class="h-[68vh] w-full bg-white"></iframe>
            @else
            <div class="px-4 py-12 text-center text-sm text-[#7B8794]">No hay voucher para mostrar.</div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalVoucherCredito"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>
    </div>
