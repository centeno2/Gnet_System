<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\Proveedor;

new class extends Component
{
    use Toast;

    public string $ventasDesde = '';
    public string $ventasHasta = '';
    public string $ventasUsuarioId = '';
    public array $ventasUsuariosOpciones = [];

    public string $devDesde = '';
    public string $devHasta = '';

    public string $provDesde = '';
    public string $provHasta = '';
    public string $provFiltro = '';
    public string $provProveedorId = '';
    public array $provCoincidencias = [];

    public string $arqDesde = '';
    public string $arqHasta = '';
    public string $arqUsuarioId = '0';
    public array $arqCajerosOpciones = [];

    public string $salDesde = '';
    public string $salHasta = '';
    public string $salTipoSalida = '';
    public array $salTiposSalidaOpciones = [];

    public string $credDesde = '';
    public string $credHasta = '';
    public string $credClienteId = '';
    public array $credInstitucionesOpciones = [];

    public string $creditoFacturaFiltro = '';
    public string $creditoFacturaVentaId = '';
    public string $creditoFacturaVista = 'detalle';
    public array $creditoFacturaCoincidencias = [];
    public array $creditoFacturaVistaOpciones = [
        ['id' => 'detalle', 'name' => 'Detalle de factura'],
        ['id' => 'abonos', 'name' => 'Historial de abonos'],
        ['id' => 'movimientos', 'name' => 'Movimientos de crédito'],
    ];

    public string $facturaContadoFiltro = '';
    public string $facturaContadoVentaId = '';
    public array $facturaContadoCoincidencias = [];

    public string $compraGeneralFiltro = '';
    public string $compraGeneralId = '';
    public array $compraGeneralCoincidencias = [];

    public string $servicioTecnicoFiltro = '';
    public string $servicioTecnicoId = '';
    public string $servicioTecnicoEstado = '';
    public string $servicioTecnicoDesde = '';
    public string $servicioTecnicoHasta = '';
    public array $servicioTecnicoCoincidencias = [];
    public array $servicioTecnicoEstadoOpciones = [
        ['id' => '', 'name' => 'Todos los estados'],
        ['id' => 'RECIBIDO', 'name' => 'Recibido'],
        ['id' => 'EN_REVISION', 'name' => 'En revisión'],
        ['id' => 'PENDIENTE_REPUESTO', 'name' => 'Pendiente repuesto'],
        ['id' => 'REPARADO', 'name' => 'Reparado'],
        ['id' => 'ENTREGADO', 'name' => 'Entregado'],
        ['id' => 'CANCELADO', 'name' => 'Cancelado / facturado'],
    ];

    public string $instalacionCamaraFiltro = '';
    public string $instalacionCamaraContratoId = '';
    public string $instalacionCamaraEstado = '';
    public string $instalacionCamaraDesde = '';
    public string $instalacionCamaraHasta = '';
    public array $instalacionCamaraCoincidencias = [];
    public array $instalacionCamaraEstadoOpciones = [
        ['id' => '', 'name' => 'Todos los estados'],
        ['id' => 'PENDIENTE', 'name' => 'Pendiente'],
        ['id' => 'EN_PROCESO', 'name' => 'En proceso'],
        ['id' => 'FINALIZADO', 'name' => 'Finalizado'],
        ['id' => 'CANCELADO', 'name' => 'Cancelado / facturado'],
    ];

    public string $planillaModo = 'ultima';
    public string $planillaVista = 'general';
    public string $planillaDesde = '';
    public string $planillaHasta = '';
    public array $planillaModoOpciones = [
        ['id' => 'ultima', 'name' => 'Última planilla generada'],
        ['id' => 'rango', 'name' => 'Por quincena'],
    ];
    public array $planillaVistaOpciones = [
        ['id' => 'general', 'name' => 'General / cabecera'],
        ['id' => 'detalle', 'name' => 'Detalle por trabajador'],
    ];

    public bool $visorActivo = false;
    public string $visorTitulo = '';
    public string $visorUrl = '';

    public function mount(): void
    {
        $inicioMes = now()->startOfMonth()->toDateString();
        $hoy = now()->toDateString();

        $this->ventasDesde = $inicioMes;
        $this->ventasHasta = $hoy;

        $this->devDesde = $inicioMes;
        $this->devHasta = $hoy;

        $this->provDesde = $inicioMes;
        $this->provHasta = $hoy;

        $this->arqDesde = $inicioMes;
        $this->arqHasta = $hoy;
        $this->arqUsuarioId = '0';

        $this->salDesde = $inicioMes;
        $this->salHasta = $hoy;

        $this->credDesde = $inicioMes;
        $this->credHasta = $hoy;

        $this->creditoFacturaFiltro = '';
        $this->creditoFacturaVentaId = '';
        $this->creditoFacturaVista = 'detalle';
        $this->creditoFacturaCoincidencias = [];

        $this->facturaContadoFiltro = '';
        $this->facturaContadoVentaId = '';
        $this->facturaContadoCoincidencias = [];

        $this->compraGeneralFiltro = '';
        $this->compraGeneralId = '';
        $this->compraGeneralCoincidencias = [];

        $this->servicioTecnicoFiltro = '';
        $this->servicioTecnicoId = '';
        $this->servicioTecnicoEstado = '';
        $this->servicioTecnicoDesde = $inicioMes;
        $this->servicioTecnicoHasta = $hoy;
        $this->servicioTecnicoCoincidencias = [];

        $this->instalacionCamaraFiltro = '';
        $this->instalacionCamaraContratoId = '';
        $this->instalacionCamaraEstado = '';
        $this->instalacionCamaraDesde = $inicioMes;
        $this->instalacionCamaraHasta = $hoy;
        $this->instalacionCamaraCoincidencias = [];

        $this->planillaModo = 'ultima';
        $this->planillaVista = 'general';
        $this->establecerQuincenaActualPlanilla();

        $this->cargarUsuariosVentas();
        $this->cargarCajerosArqueo();
        $this->cargarTiposSalidas();
        $this->cargarInstitucionesCredito();
    }

    private function cargarUsuariosVentas(): void
    {
        $usuarios = DB::table('usuario as u')
            ->join('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->join('cargo as ca', 'ca.Id_Cargo', '=', 't.Id_Cargo')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->where('u.Estado', 1)
            ->where('t.Estado', 1)
            ->where('ca.Cargo_Asignado', 'like', 'Cajer%')
            ->orderBy('u.Nombre_Usuario')
            ->selectRaw("
                u.Id_Usuario as id,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as name
            ")
            ->get()
            ->map(fn ($usuario) => [
                'id' => (string) $usuario->id,
                'name' => (string) $usuario->name,
            ])
            ->values()
            ->toArray();

        array_unshift($usuarios, [
            'id' => '',
            'name' => 'General / todos los cajeros',
        ]);

        $this->ventasUsuariosOpciones = $usuarios;
    }

    private function cargarCajerosArqueo(): void
    {
        $cajeros = DB::table('usuario as u')
            ->join('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->join('cargo as ca', 'ca.Id_Cargo', '=', 't.Id_Cargo')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->where('u.Estado', 1)
            ->where('t.Estado', 1)
            ->where('ca.Cargo_Asignado', 'like', 'Cajer%')
            ->orderBy('u.Nombre_Usuario')
            ->selectRaw("
                u.Id_Usuario as id,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as name
            ")
            ->get()
            ->map(fn ($cajero) => [
                'id' => (string) $cajero->id,
                'name' => (string) $cajero->name,
            ])
            ->values()
            ->toArray();

        array_unshift($cajeros, [
            'id' => '0',
            'name' => 'General / todos los cajeros',
        ]);

        $this->arqCajerosOpciones = $cajeros;
    }

    private function cargarTiposSalidas(): void
    {
        try {
            $tipos = DB::table('vw_reporte_otras_salidas')
                ->select('Tipo_Movimiento as id', 'Tipo_Movimiento_Nombre as name')
                ->distinct()
                ->orderBy('Tipo_Movimiento_Nombre')
                ->get()
                ->map(fn ($tipo) => [
                    'id' => (string) $tipo->id,
                    'name' => (string) $tipo->name,
                ])
                ->values()
                ->toArray();
        } catch (\Throwable) {
            $tipos = [];
        }

        array_unshift($tipos, [
            'id' => '',
            'name' => 'General / todos los tipos',
        ]);

        $this->salTiposSalidaOpciones = $tipos;
    }

    private function cargarInstitucionesCredito(): void
    {
        $instituciones = DB::table('cliente as c')
            ->where('c.Estado', 1)
            ->where('c.Tipo_Cliente', 2)
            ->orderBy('c.Institucion')
            ->selectRaw("
                c.Id_Cliente as id,
                COALESCE(NULLIF(TRIM(c.Institucion), ''), CONCAT('Institución #', c.Id_Cliente)) as name
            ")
            ->get()
            ->map(fn ($institucion) => [
                'id' => (string) $institucion->id,
                'name' => (string) $institucion->name,
            ])
            ->values()
            ->toArray();

        array_unshift($instituciones, [
            'id' => '',
            'name' => 'Selecciona una institución',
        ]);

        $this->credInstitucionesOpciones = $instituciones;
    }

    public function generarReporte(string $reporte, string $formato = 'pdf')
    {
        return match ($reporte) {
            'ventas' => $this->generarVentas($formato),
            'devoluciones' => $this->generarDevoluciones($formato),
            'proveedores' => $this->generarProveedores($formato),
            'compra-general' => $this->generarCompraGeneral($formato),
            'arqueo' => $this->generarArqueo($formato),
            'inventario' => $this->generarInventario($formato),
            'stock-proximo' => $this->generarStockProximoAgotarse($formato),
            'salidas' => $this->generarSalidas($formato),
            'creditos' => $this->generarCreditos($formato),
            'credito-factura' => $this->generarCreditoFactura($formato),
            'factura-contado' => $this->generarFacturaContado($formato),
            'servicio-tecnico-factura' => $this->generarServicioTecnicoFactura($formato),
            'instalacion-camara-factura' => $this->generarInstalacionCamaraFactura($formato),
            'planilla-pago' => $this->generarPlanillaPago($formato),
            default => $this->mostrarToast('Reporte no disponible.', 'error'),
        };
    }

    public function limpiarFiltros(): void
    {
        $this->mount();

        $this->ventasUsuarioId = '';
        $this->provFiltro = '';
        $this->arqUsuarioId = '0';
        $this->salTipoSalida = '';
        $this->credClienteId = '';
        $this->creditoFacturaFiltro = '';
        $this->creditoFacturaVentaId = '';
        $this->creditoFacturaVista = 'detalle';
        $this->creditoFacturaCoincidencias = [];
        $this->compraGeneralFiltro = '';
        $this->compraGeneralId = '';
        $this->compraGeneralCoincidencias = [];
        $this->servicioTecnicoFiltro = '';
        $this->servicioTecnicoId = '';
        $this->servicioTecnicoEstado = '';
        $this->servicioTecnicoCoincidencias = [];
        $this->instalacionCamaraFiltro = '';
        $this->instalacionCamaraContratoId = '';
        $this->instalacionCamaraEstado = '';
        $this->instalacionCamaraCoincidencias = [];
        $this->planillaModo = 'ultima';
        $this->planillaVista = 'general';
        $this->establecerQuincenaActualPlanilla();
        $this->provProveedorId = '';
        $this->provCoincidencias = [];

        $this->cerrarVisor();

        $this->mostrarToast('Filtros restablecidos.', 'success');
    }

    public function updatedPlanillaModo(string $valor): void
    {
        if ($valor === 'rango') {
            $this->establecerQuincenaActualPlanilla();
            return;
        }

        if ($valor !== 'ultima') {
            $this->planillaModo = 'ultima';
        }
    }

    public function cerrarVisor(): void
    {
        $this->visorActivo = false;
        $this->visorTitulo = '';
        $this->visorUrl = '';
    }

    public function tarjetasReportes(): array
    {
        return [
            [
                'id' => 'ventas',
                'titulo' => 'Ventas',
                'descripcion' => 'Ventas por periodo y cajero.',
                'icono' => 'o-chart-bar',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'ventasDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'ventasHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Cajero',
                        'model' => 'ventasUsuarioId',
                        'opciones' => 'ventasUsuariosOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'devoluciones',
                'titulo' => 'Devoluciones',
                'descripcion' => 'Devoluciones por rango de fechas.',
                'icono' => 'o-arrow-path',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'devDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'devHasta'],
                ],
            ],
            [
                'id' => 'proveedores',
                'titulo' => 'Compras proveedor',
                'descripcion' => 'Compras por proveedor o RUC.',
                'icono' => 'o-shopping-cart',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'provDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'provHasta'],
                    [
                        'tipo' => 'proveedor-autocomplete',
                        'label' => 'Proveedor',
                        'model' => 'provFiltro',
                        'placeholder' => 'Nombre o RUC',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'compra-general',
                'titulo' => 'Compra por número',
                'descripcion' => 'Compra específica con cabecera y detalle.',
                'icono' => 'o-document-magnifying-glass',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'reporte-autocomplete',
                        'label' => 'Número de compra',
                        'model' => 'compraGeneralFiltro',
                        'placeholder' => 'Ej: COMP-000001',
                        'coincidencias' => 'compraGeneralCoincidencias',
                        'accion' => 'seleccionarCompraGeneralReporte',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'arqueo',
                'titulo' => 'Arqueos de caja',
                'descripcion' => 'Caja por periodo y cajero.',
                'icono' => 'o-banknotes',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'arqDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'arqHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Cajero',
                        'model' => 'arqUsuarioId',
                        'opciones' => 'arqCajerosOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'inventario',
                'titulo' => 'Inventario',
                'descripcion' => 'Existencias generales.',
                'icono' => 'o-cube',
                'color' => 'azul',
                'boton' => 'Generar',
                'sin_filtros' => 'Inventario completo',
                'campos' => [],
            ],
            [
                'id' => 'stock-proximo',
                'titulo' => 'Stock próximo',
                'descripcion' => 'Productos con stock igual o menor al mínimo.',
                'icono' => 'o-exclamation-triangle',
                'color' => 'ambar',
                'boton' => 'Generar',
                'sin_filtros' => 'Productos próximos a agotarse',
                'campos' => [],
            ],
            [
                'id' => 'salidas',
                'titulo' => 'Otras salidas',
                'descripcion' => 'Salidas por fecha y tipo de movimiento.',
                'icono' => 'o-arrow-up-tray',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'salDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'salHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Tipo movimiento',
                        'model' => 'salTipoSalida',
                        'opciones' => 'salTiposSalidaOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'creditos',
                'titulo' => 'Créditos institucionales',
                'descripcion' => 'Recepción por institución y periodo.',
                'icono' => 'o-credit-card',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'credDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'credHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Institución',
                        'model' => 'credClienteId',
                        'opciones' => 'credInstitucionesOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'credito-factura',
                'titulo' => 'Crédito por factura',
                'descripcion' => 'Factura de venta crédito con detalle, abonos o movimientos.',
                'icono' => 'o-document-magnifying-glass',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'credito-factura-autocomplete',
                        'label' => 'Factura crédito',
                        'model' => 'creditoFacturaFiltro',
                        'placeholder' => 'Número de factura',
                        'span' => 'sm:col-span-2',
                    ],
                    [
                        'tipo' => 'select',
                        'label' => 'Vista',
                        'model' => 'creditoFacturaVista',
                        'opciones' => 'creditoFacturaVistaOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'factura-contado',
                'titulo' => 'Factura contado',
                'descripcion' => 'Factura normal al contado por número de factura.',
                'icono' => 'o-receipt-percent',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'reporte-autocomplete',
                        'label' => 'Factura contado',
                        'model' => 'facturaContadoFiltro',
                        'placeholder' => 'Número de factura',
                        'coincidencias' => 'facturaContadoCoincidencias',
                        'accion' => 'seleccionarFacturaContadoReporte',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'servicio-tecnico-factura',
                'titulo' => 'Servicio técnico',
                'descripcion' => 'Orden específica o listado por estado y periodo.',
                'icono' => 'o-computer-desktop',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'date',
                        'label' => 'Inicial',
                        'model' => 'servicioTecnicoDesde',
                    ],
                    [
                        'tipo' => 'date',
                        'label' => 'Final',
                        'model' => 'servicioTecnicoHasta',
                    ],
                    [
                        'tipo' => 'select',
                        'label' => 'Estado servicio',
                        'model' => 'servicioTecnicoEstado',
                        'opciones' => 'servicioTecnicoEstadoOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                    [
                        'tipo' => 'reporte-autocomplete',
                        'label' => 'Orden / factura',
                        'model' => 'servicioTecnicoFiltro',
                        'placeholder' => 'Opcional: número de orden o factura',
                        'coincidencias' => 'servicioTecnicoCoincidencias',
                        'accion' => 'seleccionarServicioTecnicoReporte',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'instalacion-camara-factura',
                'titulo' => 'Instalación cámaras',
                'descripcion' => 'Contrato específico o listado por estado y periodo.',
                'icono' => 'o-video-camera',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'date',
                        'label' => 'Inicial',
                        'model' => 'instalacionCamaraDesde',
                    ],
                    [
                        'tipo' => 'date',
                        'label' => 'Final',
                        'model' => 'instalacionCamaraHasta',
                    ],
                    [
                        'tipo' => 'select',
                        'label' => 'Estado contrato',
                        'model' => 'instalacionCamaraEstado',
                        'opciones' => 'instalacionCamaraEstadoOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                    [
                        'tipo' => 'reporte-autocomplete',
                        'label' => 'Contrato / factura',
                        'model' => 'instalacionCamaraFiltro',
                        'placeholder' => 'Opcional: número de contrato o factura',
                        'coincidencias' => 'instalacionCamaraCoincidencias',
                        'accion' => 'seleccionarInstalacionCamaraReporte',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'planilla-pago',
                'titulo' => 'Planilla de pago',
                'descripcion' => 'Última planilla o quincena específica.',
                'icono' => 'o-clipboard-document-list',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    [
                        'tipo' => 'select',
                        'label' => 'Vista',
                        'model' => 'planillaVista',
                        'opciones' => 'planillaVistaOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                    [
                        'tipo' => 'select',
                        'label' => 'Consulta',
                        'model' => 'planillaModo',
                        'opciones' => 'planillaModoOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                    [
                        'tipo' => 'date',
                        'label' => 'Inicial',
                        'model' => 'planillaDesde',
                        'mostrar_si' => ['model' => 'planillaModo', 'value' => 'rango'],
                    ],
                    [
                        'tipo' => 'date',
                        'label' => 'Final',
                        'model' => 'planillaHasta',
                        'mostrar_si' => ['model' => 'planillaModo', 'value' => 'rango'],
                    ],
                ],
            ],
        ];
    }

    private function generarVentas(string $formato)
    {
        if (! $this->rangoValido($this->ventasDesde, $this->ventasHasta, 'ventas')) {
            return null;
        }

        if (trim($this->ventasUsuarioId) !== '' && ! ctype_digit(trim($this->ventasUsuarioId))) {
            $this->mostrarToast('Seleccione un cajero válido.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->ventasDesde,
            'hasta' => $this->ventasHasta,
            'usuarioId' => trim($this->ventasUsuarioId),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Ventas por periodo',
                route('reportes.ventas-periodo.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.ventas-periodo.excel', $parametros)),
            'word' => redirect()->to(route('reportes.ventas-periodo.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarDevoluciones(string $formato)
    {
        if (! $this->rangoValido($this->devDesde, $this->devHasta, 'devoluciones')) {
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->devDesde,
            'hasta' => $this->devHasta,
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Devoluciones',
                route('reportes.devoluciones.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.devoluciones.excel', $parametros)),
            'word' => redirect()->to(route('reportes.devoluciones.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarArqueo(string $formato)
    {
        if (! $this->rangoValido($this->arqDesde, $this->arqHasta, 'arqueo de caja')) {
            return null;
        }

        $usuarioId = trim($this->arqUsuarioId);

        if ($usuarioId !== '' && $usuarioId !== '0' && ! ctype_digit($usuarioId)) {
            $this->mostrarToast('Seleccione un cajero válido.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->arqDesde,
            'hasta' => $this->arqHasta,
            'usuarioId' => $usuarioId !== '0' ? $usuarioId : '',
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Arqueos de caja',
                route('reportes.arqueos-caja.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.arqueos-caja.excel', $parametros)),
            'word' => redirect()->to(route('reportes.arqueos-caja.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarInventario(string $formato)
    {
        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Inventario',
                route('reportes.inventario.pdf') . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.inventario.excel')),
            'word' => redirect()->to(route('reportes.inventario.word')),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarStockProximoAgotarse(string $formato)
    {
        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Stock próximo a agotarse',
                route('reportes.stock-proximo-agotarse.pdf') . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.stock-proximo-agotarse.excel')),
            'word' => redirect()->to(route('reportes.stock-proximo-agotarse.word')),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarSalidas(string $formato)
    {
        if (! $this->rangoValido($this->salDesde, $this->salHasta, 'otras salidas')) {
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->salDesde,
            'hasta' => $this->salHasta,
            'tipoSalida' => trim($this->salTipoSalida),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Otras salidas',
                route('reportes.otras-salidas.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.otras-salidas.excel', $parametros)),
            'word' => redirect()->to(route('reportes.otras-salidas.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarCreditos(string $formato)
    {
        if (! $this->rangoValido($this->credDesde, $this->credHasta, 'créditos institucionales')) {
            return null;
        }

        if (trim($this->credClienteId) === '') {
            $this->mostrarToast('Selecciona una institución para generar el reporte.', 'error');
            return null;
        }

        if (! ctype_digit(trim($this->credClienteId))) {
            $this->mostrarToast('La institución seleccionada no es válida.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->credDesde,
            'hasta' => $this->credHasta,
            'clienteId' => trim($this->credClienteId),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Créditos institucionales',
                route('reportes.creditos-institucionales.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.creditos-institucionales.excel', $parametros)),
            'word' => redirect()->to(route('reportes.creditos-institucionales.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarFacturaContado(string $formato)
    {
        $facturaTexto = trim($this->facturaContadoFiltro);
        $ventaId = trim($this->facturaContadoVentaId);

        if ($facturaTexto === '') {
            $this->mostrarToast('Ingrese o seleccione una factura contado.', 'error');
            return null;
        }

        if ($ventaId === '' || ! ctype_digit($ventaId)) {
            $factura = DB::table('venta as v')
                ->leftJoin('servicio_tecnico as st', 'st.Id_Venta', '=', 'v.Id_Venta')
                ->leftJoin('contrato_instalacion_camara as cic', 'cic.Id_Venta', '=', 'v.Id_Venta')
                ->where('v.Tipo_Venta', 'CONTADO')
                ->whereNull('st.Id_Servicio_Tecnico')
                ->whereNull('cic.Id_Contrato_Instalacion_Camara')
                ->where('v.Numero_Factura', $facturaTexto)
                ->select('v.Id_Venta', 'v.Numero_Factura')
                ->first();

            if (! $factura) {
                $this->mostrarToast('Seleccione una factura contado válida desde las coincidencias.', 'error');
                return null;
            }

            $ventaId = (string) $factura->Id_Venta;
            $this->facturaContadoVentaId = $ventaId;
        }

        $parametros = $this->parametrosLimpios([
            'ventaId' => $ventaId,
            'factura' => $facturaTexto,
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Factura contado',
                route('reportes.factura-contado.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.factura-contado.excel', $parametros)),
            'word' => redirect()->to(route('reportes.factura-contado.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarServicioTecnicoFactura(string $formato)
    {
        $numeroOrden = trim($this->servicioTecnicoFiltro);
        $servicioId = trim($this->servicioTecnicoId);
        $estado = trim($this->servicioTecnicoEstado);

        if ($estado !== '' && ! in_array($estado, ['RECIBIDO', 'EN_REVISION', 'PENDIENTE_REPUESTO', 'REPARADO', 'ENTREGADO', 'CANCELADO'], true)) {
            $this->mostrarToast('Seleccione un estado de servicio válido.', 'error');
            return null;
        }

        if ($numeroOrden !== '' && ($servicioId === '' || ! ctype_digit($servicioId))) {
            $servicio = DB::table('servicio_tecnico as st')
                ->leftJoin('venta as v', 'v.Id_Venta', '=', 'st.Id_Venta')
                ->where(function ($query) use ($numeroOrden) {
                    $query
                        ->where('st.Numero_Orden', $numeroOrden)
                        ->orWhere('v.Numero_Factura', $numeroOrden);
                })
                ->select('st.Id_Servicio_Tecnico', 'st.Numero_Orden')
                ->first();

            if (! $servicio) {
                $this->mostrarToast('Seleccione una orden válida desde las coincidencias o deje el campo vacío para filtrar por estado y periodo.', 'error');
                return null;
            }

            $servicioId = (string) $servicio->Id_Servicio_Tecnico;
            $numeroOrden = (string) $servicio->Numero_Orden;
            $this->servicioTecnicoId = $servicioId;
            $this->servicioTecnicoFiltro = $numeroOrden;
        }

        if ($servicioId !== '' && ! ctype_digit($servicioId)) {
            $this->mostrarToast('La orden seleccionada no es válida.', 'error');
            return null;
        }

        $parametros = [
            'servicioTecnicoId' => $servicioId,
            'numeroOrden' => $numeroOrden,
        ];

        if ($servicioId === '' && $numeroOrden === '') {
            if (! $this->rangoValido($this->servicioTecnicoDesde, $this->servicioTecnicoHasta, 'servicio técnico')) {
                return null;
            }

            if ($this->servicioTecnicoDesde === '' || $this->servicioTecnicoHasta === '') {
                $this->mostrarToast('Seleccione el rango de fechas para filtrar servicios técnicos.', 'error');
                return null;
            }

            $parametros['desde'] = $this->servicioTecnicoDesde;
            $parametros['hasta'] = $this->servicioTecnicoHasta;
            $parametros['estado'] = $estado;
        }

        $parametros = $this->parametrosLimpios($parametros);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                $servicioId !== '' || $numeroOrden !== '' ? 'Facturación servicio técnico' : 'Servicios técnicos por estado',
                route('reportes.servicio-tecnico-factura.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.servicio-tecnico-factura.excel', $parametros)),
            'word' => redirect()->to(route('reportes.servicio-tecnico-factura.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarInstalacionCamaraFactura(string $formato)
    {
        $numeroContrato = trim($this->instalacionCamaraFiltro);
        $contratoId = trim($this->instalacionCamaraContratoId);
        $estado = trim($this->instalacionCamaraEstado);

        if ($estado !== '' && ! in_array($estado, ['PENDIENTE', 'EN_PROCESO', 'FINALIZADO', 'CANCELADO'], true)) {
            $this->mostrarToast('Seleccione un estado de contrato válido.', 'error');
            return null;
        }

        if ($numeroContrato !== '' && ($contratoId === '' || ! ctype_digit($contratoId))) {
            $contrato = DB::table('contrato_instalacion_camara as cic')
                ->leftJoin('venta as v', 'v.Id_Venta', '=', 'cic.Id_Venta')
                ->where(function ($query) use ($numeroContrato) {
                    $query
                        ->where('cic.Numero_Contrato', $numeroContrato)
                        ->orWhere('v.Numero_Factura', $numeroContrato);
                })
                ->select('cic.Id_Contrato_Instalacion_Camara', 'cic.Numero_Contrato')
                ->first();

            if (! $contrato) {
                $this->mostrarToast('Seleccione un contrato válido desde las coincidencias o deje el campo vacío para filtrar por estado y periodo.', 'error');
                return null;
            }

            $contratoId = (string) $contrato->Id_Contrato_Instalacion_Camara;
            $numeroContrato = (string) $contrato->Numero_Contrato;
            $this->instalacionCamaraContratoId = $contratoId;
            $this->instalacionCamaraFiltro = $numeroContrato;
        }

        if ($contratoId !== '' && ! ctype_digit($contratoId)) {
            $this->mostrarToast('El contrato seleccionado no es válido.', 'error');
            return null;
        }

        $parametros = [
            'contratoId' => $contratoId,
            'numeroContrato' => $numeroContrato,
        ];

        if ($contratoId === '' && $numeroContrato === '') {
            if (! $this->rangoValido($this->instalacionCamaraDesde, $this->instalacionCamaraHasta, 'instalación de cámaras')) {
                return null;
            }

            if ($this->instalacionCamaraDesde === '' || $this->instalacionCamaraHasta === '') {
                $this->mostrarToast('Seleccione el rango de fechas para filtrar instalaciones de cámaras.', 'error');
                return null;
            }

            $parametros['desde'] = $this->instalacionCamaraDesde;
            $parametros['hasta'] = $this->instalacionCamaraHasta;
            $parametros['estado'] = $estado;
        }

        $parametros = $this->parametrosLimpios($parametros);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                $contratoId !== '' || $numeroContrato !== '' ? 'Facturación instalación de cámaras' : 'Instalaciones de cámaras por estado',
                route('reportes.instalacion-camara-factura.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.instalacion-camara-factura.excel', $parametros)),
            'word' => redirect()->to(route('reportes.instalacion-camara-factura.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarCreditoFactura(string $formato)
    {
        $facturaTexto = trim($this->creditoFacturaFiltro);
        $ventaId = trim($this->creditoFacturaVentaId);
        $vista = trim($this->creditoFacturaVista);

        if ($facturaTexto === '') {
            $this->mostrarToast('Ingresa o selecciona una factura de crédito.', 'error');
            return null;
        }

        if (! in_array($vista, ['detalle', 'abonos', 'movimientos'], true)) {
            $this->mostrarToast('Selecciona una vista válida para el reporte de crédito.', 'error');
            return null;
        }

        if ($ventaId === '' || ! ctype_digit($ventaId)) {
            $venta = DB::table('venta as v')
                ->join('credito as cr', 'cr.Id_Venta', '=', 'v.Id_Venta')
                ->where('v.Tipo_Venta', 'CREDITO')
                ->where('v.Numero_Factura', $facturaTexto)
                ->select('v.Id_Venta')
                ->first();

            if (! $venta) {
                $this->mostrarToast('Selecciona una factura válida desde las coincidencias. Solo se aceptan facturas de crédito.', 'error');
                return null;
            }

            $ventaId = (string) $venta->Id_Venta;
            $this->creditoFacturaVentaId = $ventaId;
        }

        $parametros = $this->parametrosLimpios([
            'ventaId' => $ventaId,
            'factura' => $facturaTexto,
            'vista' => $vista,
        ]);

        $titulo = match ($vista) {
            'abonos' => 'Crédito por factura - abonos',
            'movimientos' => 'Crédito por factura - movimientos',
            default => 'Crédito por factura',
        };

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                $titulo,
                route('reportes.credito-factura.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.credito-factura.excel', $parametros)),
            'word' => redirect()->to(route('reportes.credito-factura.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarPlanillaPago(string $formato)
    {
        $modo = trim($this->planillaModo);
        $vista = trim($this->planillaVista);

        if (! in_array($modo, ['ultima', 'rango'], true)) {
            $this->mostrarToast('Selecciona un modo válido para planilla.', 'error');
            return null;
        }

        if (! in_array($vista, ['general', 'detalle'], true)) {
            $this->mostrarToast('Selecciona una vista válida para planilla.', 'error');
            return null;
        }

        $parametros = [
            'modo' => $modo,
            'vista' => $vista,
        ];

        if ($modo === 'rango') {
            if (! $this->quincenaPlanillaValida($this->planillaDesde, $this->planillaHasta)) {
                return null;
            }

            $parametros['desde'] = $this->planillaDesde;
            $parametros['hasta'] = $this->planillaHasta;
        }

        $parametros = $this->parametrosLimpios($parametros);
        $titulo = $vista === 'detalle'
            ? 'Detalle de planilla de pago'
            : 'Planilla de pago';

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                $titulo,
                route('reportes.planilla-pago.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.planilla-pago.excel', $parametros)),
            'word' => redirect()->to(route('reportes.planilla-pago.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    public function updatedCompraGeneralFiltro(): void
    {
        $this->compraGeneralId = '';
        $this->compraGeneralCoincidencias = [];

        $termino = trim($this->compraGeneralFiltro);

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $this->compraGeneralCoincidencias = DB::table('compra as c')
            ->join('proveedor as prov', 'prov.Id_Proveedor', '=', 'c.Id_Proveedor')
            ->leftJoin('persona as pp', 'pp.Id_Persona', '=', 'prov.Id_Persona')
            ->where('c.Numero_Compra', 'like', $like)
            ->orderByDesc('c.Fecha_Compra')
            ->limit(8)
            ->selectRaw("
                c.Id_Compra as id,
                c.Numero_Compra as numero_compra,
                c.Fecha_Compra as fecha,
                c.Tipo_Compra as tipo_compra,
                c.Medio_Pago as medio_pago,
                c.Total as total,
                COALESCE(
                    NULLIF(TRIM(prov.Empresa), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', pp.Primer_Nombre, pp.Segundo_Nombre, pp.Primer_Apellido, pp.Segundo_Apellido)), ''),
                    CONCAT('Proveedor #', prov.Id_Proveedor)
                ) as proveedor
            ")
            ->get()
            ->map(fn ($compra) => [
                'id' => (string) $compra->id,
                'principal' => 'Compra ' . $compra->numero_compra . ' · ' . $compra->proveedor,
                'secundario' => Carbon::parse($compra->fecha)->format('d/m/Y H:i') . ' · ' . $compra->tipo_compra . ' · ' . $compra->medio_pago . ' · C$ ' . number_format((float) $compra->total, 2),
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarCompraGeneralReporte(string $id): void
    {
        if (! ctype_digit($id)) {
            $this->mostrarToast('La compra seleccionada no es válida.', 'error');
            return;
        }

        $compra = DB::table('compra')
            ->where('Id_Compra', (int) $id)
            ->select('Id_Compra', 'Numero_Compra')
            ->first();

        if (! $compra) {
            $this->mostrarToast('La compra seleccionada no existe.', 'error');
            return;
        }

        $this->compraGeneralId = (string) $compra->Id_Compra;
        $this->compraGeneralFiltro = (string) $compra->Numero_Compra;
        $this->compraGeneralCoincidencias = [];
    }

    public function updatedFacturaContadoFiltro(): void
    {
        $this->facturaContadoVentaId = '';
        $this->facturaContadoCoincidencias = [];

        $termino = trim($this->facturaContadoFiltro);

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $this->facturaContadoCoincidencias = DB::table('venta as v')
            ->leftJoin('servicio_tecnico as st', 'st.Id_Venta', '=', 'v.Id_Venta')
            ->leftJoin('contrato_instalacion_camara as cic', 'cic.Id_Venta', '=', 'v.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('v.Tipo_Venta', 'CONTADO')
            ->whereNull('st.Id_Servicio_Tecnico')
            ->whereNull('cic.Id_Contrato_Instalacion_Camara')
            ->where('v.Numero_Factura', 'like', $like)
            ->orderByDesc('v.Fecha_venta')
            ->limit(8)
            ->selectRaw("
                v.Id_Venta as id,
                v.Numero_Factura as factura,
                v.Fecha_venta as fecha,
                v.Total as total,
                CASE
                    WHEN v.Estado = 1 THEN 'ACTIVA'
                    WHEN v.Estado = 0 THEN 'REGISTRADA'
                    ELSE CONCAT('ESTADO ', v.Estado)
                END as estado,
                COALESCE(
                    NULLIF(TRIM(c.Institucion), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    'Cliente no asignado'
                ) as cliente
            ")
            ->get()
            ->map(fn ($factura) => [
                'id' => (string) $factura->id,
                'principal' => 'Factura ' . $factura->factura . ' · ' . $factura->cliente,
                'secundario' => Carbon::parse($factura->fecha)->format('d/m/Y H:i') . ' · ' . $factura->estado . ' · Total C$ ' . number_format((float) $factura->total, 2),
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarFacturaContadoReporte(string $id): void
    {
        if (! ctype_digit($id)) {
            $this->mostrarToast('La factura seleccionada no es válida.', 'error');
            return;
        }

        $factura = DB::table('venta as v')
            ->leftJoin('servicio_tecnico as st', 'st.Id_Venta', '=', 'v.Id_Venta')
            ->leftJoin('contrato_instalacion_camara as cic', 'cic.Id_Venta', '=', 'v.Id_Venta')
            ->where('v.Tipo_Venta', 'CONTADO')
            ->whereNull('st.Id_Servicio_Tecnico')
            ->whereNull('cic.Id_Contrato_Instalacion_Camara')
            ->where('v.Id_Venta', (int) $id)
            ->select('v.Id_Venta', 'v.Numero_Factura')
            ->first();

        if (! $factura) {
            $this->mostrarToast('La factura seleccionada no existe o no pertenece a una venta contado normal.', 'error');
            return;
        }

        $this->facturaContadoVentaId = (string) $factura->Id_Venta;
        $this->facturaContadoFiltro = (string) $factura->Numero_Factura;
        $this->facturaContadoCoincidencias = [];
    }

    public function updatedServicioTecnicoFiltro(): void
    {
        $this->servicioTecnicoId = '';
        $this->buscarServicioTecnicoCoincidencias();
    }

    public function updatedServicioTecnicoEstado(): void
    {
        $this->buscarServicioTecnicoCoincidencias();
    }

    private function buscarServicioTecnicoCoincidencias(): void
    {
        $this->servicioTecnicoCoincidencias = [];

        $termino = trim($this->servicioTecnicoFiltro);

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $query = DB::table('servicio_tecnico as st')
            ->leftJoin('venta as v', 'v.Id_Venta', '=', 'st.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'st.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where(function ($query) use ($like) {
                $query
                    ->where('st.Numero_Orden', 'like', $like)
                    ->orWhere('v.Numero_Factura', 'like', $like);
            });

        $this->servicioTecnicoCoincidencias = $query
            ->orderByDesc('st.Fecha_Ingreso')
            ->limit(8)
            ->selectRaw("
                st.Id_Servicio_Tecnico as id,
                st.Numero_Orden as numero,
                st.Fecha_Ingreso as fecha,
                st.Estado_Servicio as estado,
                st.Tipo_Equipo as equipo,
                st.Total_Servicio as total_servicio,
                st.Total_Repuestos as total_repuestos,
                v.Numero_Factura as factura,
                COALESCE(
                    NULLIF(TRIM(c.Institucion), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    'Cliente no asignado'
                ) as cliente
            ")
            ->get()
            ->map(fn ($orden) => [
                'id' => (string) $orden->id,
                'principal' => 'Orden ' . $orden->numero . ' · ' . $orden->cliente,
                'secundario' => Carbon::parse($orden->fecha)->format('d/m/Y H:i') . ' · ' . $orden->estado . ' · Factura ' . ($orden->factura ?: 'sin factura') . ' · C$ ' . number_format((float) $orden->total_servicio + (float) $orden->total_repuestos, 2),
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarServicioTecnicoReporte(string $id): void
    {
        if (! ctype_digit($id)) {
            $this->mostrarToast('La orden seleccionada no es válida.', 'error');
            return;
        }

        $orden = DB::table('servicio_tecnico as st')
            ->where('st.Id_Servicio_Tecnico', (int) $id)
            ->select('st.Id_Servicio_Tecnico', 'st.Numero_Orden')
            ->first();

        if (! $orden) {
            $this->mostrarToast('La orden seleccionada no existe.', 'error');
            return;
        }

        $this->servicioTecnicoId = (string) $orden->Id_Servicio_Tecnico;
        $this->servicioTecnicoFiltro = (string) $orden->Numero_Orden;
        $this->servicioTecnicoCoincidencias = [];
    }

    public function updatedInstalacionCamaraFiltro(): void
    {
        $this->instalacionCamaraContratoId = '';
        $this->buscarInstalacionCamaraCoincidencias();
    }

    public function updatedInstalacionCamaraEstado(): void
    {
        $this->buscarInstalacionCamaraCoincidencias();
    }

    private function buscarInstalacionCamaraCoincidencias(): void
    {
        $this->instalacionCamaraCoincidencias = [];

        $termino = trim($this->instalacionCamaraFiltro);

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $query = DB::table('contrato_instalacion_camara as cic')
            ->leftJoin('venta as v', 'v.Id_Venta', '=', 'cic.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'cic.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where(function ($query) use ($like) {
                $query
                    ->where('cic.Numero_Contrato', 'like', $like)
                    ->orWhere('v.Numero_Factura', 'like', $like);
            });

        $this->instalacionCamaraCoincidencias = $query
            ->orderByDesc('cic.Fecha_Contrato')
            ->limit(8)
            ->selectRaw("
                cic.Id_Contrato_Instalacion_Camara as id,
                cic.Numero_Contrato as numero,
                cic.Fecha_Contrato as fecha,
                cic.Estado_Contrato as estado,
                cic.Total_Contrato as total,
                cic.Cantidad_Camaras as camaras,
                v.Numero_Factura as factura,
                COALESCE(
                    NULLIF(TRIM(c.Institucion), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    'Cliente no asignado'
                ) as cliente
            ")
            ->get()
            ->map(fn ($contrato) => [
                'id' => (string) $contrato->id,
                'principal' => 'Contrato ' . $contrato->numero . ' · ' . $contrato->cliente,
                'secundario' => Carbon::parse($contrato->fecha)->format('d/m/Y H:i') . ' · ' . $contrato->estado . ' · Factura ' . ($contrato->factura ?: 'sin factura') . ' · ' . number_format((int) $contrato->camaras) . ' cámaras · C$ ' . number_format((float) $contrato->total, 2),
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarInstalacionCamaraReporte(string $id): void
    {
        if (! ctype_digit($id)) {
            $this->mostrarToast('El contrato seleccionado no es válido.', 'error');
            return;
        }

        $contrato = DB::table('contrato_instalacion_camara as cic')
            ->where('cic.Id_Contrato_Instalacion_Camara', (int) $id)
            ->select('cic.Id_Contrato_Instalacion_Camara', 'cic.Numero_Contrato')
            ->first();

        if (! $contrato) {
            $this->mostrarToast('El contrato seleccionado no existe.', 'error');
            return;
        }

        $this->instalacionCamaraContratoId = (string) $contrato->Id_Contrato_Instalacion_Camara;
        $this->instalacionCamaraFiltro = (string) $contrato->Numero_Contrato;
        $this->instalacionCamaraCoincidencias = [];
    }

    public function updatedCreditoFacturaFiltro(): void
    {
        $this->creditoFacturaVentaId = '';
        $this->creditoFacturaCoincidencias = [];

        $termino = trim($this->creditoFacturaFiltro);

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $this->creditoFacturaCoincidencias = DB::table('venta as v')
            ->join('credito as cr', 'cr.Id_Venta', '=', 'v.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('v.Tipo_Venta', 'CREDITO')
            ->where('v.Numero_Factura', 'like', $like)
            ->orderByDesc('v.Fecha_venta')
            ->limit(8)
            ->selectRaw("
                v.Id_Venta as id,
                v.Numero_Factura as factura,
                DATE_FORMAT(v.Fecha_venta, '%d/%m/%Y') as fecha,
                COALESCE(
                    NULLIF(TRIM(c.Institucion), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    'Cliente no asignado'
                ) as cliente,
                cr.Estado as estado,
                cr.Saldo_Actual as saldo
            ")
            ->get()
            ->map(fn ($factura) => [
                'id' => (string) $factura->id,
                'factura' => (string) $factura->factura,
                'fecha' => (string) $factura->fecha,
                'cliente' => (string) $factura->cliente,
                'estado' => (string) $factura->estado,
                'saldo' => 'C$ ' . number_format((float) $factura->saldo, 2),
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarFacturaCreditoReporte(string $id): void
    {
        if (! ctype_digit($id)) {
            $this->mostrarToast('La factura seleccionada no es válida.', 'error');
            return;
        }

        $factura = DB::table('venta as v')
            ->join('credito as cr', 'cr.Id_Venta', '=', 'v.Id_Venta')
            ->where('v.Tipo_Venta', 'CREDITO')
            ->where('v.Id_Venta', (int) $id)
            ->select('v.Id_Venta', 'v.Numero_Factura')
            ->first();

        if (! $factura) {
            $this->mostrarToast('La factura seleccionada no existe o no pertenece a una venta de crédito.', 'error');
            return;
        }

        $this->creditoFacturaVentaId = (string) $factura->Id_Venta;
        $this->creditoFacturaFiltro = (string) $factura->Numero_Factura;
        $this->creditoFacturaCoincidencias = [];
    }

    public function updatedProvFiltro(): void
{
    $this->provProveedorId = '';
    $this->provCoincidencias = [];

    $termino = trim($this->provFiltro);

    if (mb_strlen($termino) < 2) {
        return;
    }

    $like = '%' . $termino . '%';

    $this->provCoincidencias = Proveedor::query()
        ->with('persona')
        ->where(function ($query) use ($like) {
            $query
                ->where('Empresa', 'like', $like)
                ->orWhere('Codigo_Ruc', 'like', $like)
                ->orWhere('Telefono_Empresa', 'like', $like)
                ->orWhere('Correo_Empresa', 'like', $like)
                ->orWhereHas('persona', function ($personaQuery) use ($like) {
                    $personaQuery
                        ->where('Primer_Nombre', 'like', $like)
                        ->orWhere('Segundo_Nombre', 'like', $like)
                        ->orWhere('Primer_Apellido', 'like', $like)
                        ->orWhere('Segundo_Apellido', 'like', $like)
                        ->orWhere('Telefono', 'like', $like);
                });
        })
        ->orderBy('Empresa')
        ->limit(8)
        ->get()
        ->map(fn (Proveedor $proveedor) => [
            'id' => (string) $proveedor->Id_Proveedor,
            'name' => $this->nombreProveedorReporte($proveedor),
            'ruc' => (string) ($proveedor->Codigo_Ruc ?: 'Sin RUC'),
        ])
        ->values()
        ->toArray();
}

public function seleccionarProveedorReporte(string $id): void
{
    if (! ctype_digit($id)) {
        $this->mostrarToast('El proveedor seleccionado no es válido.', 'error');
        return;
    }

    $proveedor = Proveedor::query()
        ->with('persona')
        ->find((int) $id);

    if (! $proveedor) {
        $this->mostrarToast('El proveedor seleccionado no existe.', 'error');
        return;
    }

    $this->provProveedorId = (string) $proveedor->Id_Proveedor;
    $this->provFiltro = $this->nombreProveedorReporte($proveedor);
    $this->provCoincidencias = [];
}

private function nombreProveedorReporte(Proveedor $proveedor): string
{
    if ((int) $proveedor->Tipo_Proveedor === 2) {
        return trim((string) $proveedor->Empresa) !== ''
            ? (string) $proveedor->Empresa
            : 'Proveedor #' . $proveedor->Id_Proveedor;
    }

    $persona = $proveedor->persona;

    $nombre = trim(
        ($persona?->Primer_Nombre ?? '') . ' ' .
        ($persona?->Segundo_Nombre ?? '') . ' ' .
        ($persona?->Primer_Apellido ?? '') . ' ' .
        ($persona?->Segundo_Apellido ?? '')
    );

    return $nombre !== ''
        ? $nombre
        : 'Proveedor #' . $proveedor->Id_Proveedor;
}

    private function generarCompraGeneral(string $formato)
    {
        $numeroCompra = trim($this->compraGeneralFiltro);
        $compraId = trim($this->compraGeneralId);

        if ($numeroCompra === '') {
            $this->mostrarToast('Ingrese o seleccione un número de compra.', 'error');
            return null;
        }

        if (mb_strlen($numeroCompra) > 50) {
            $this->mostrarToast('El número de compra no debe superar los 50 caracteres.', 'error');
            return null;
        }

        if ($compraId !== '' && ! ctype_digit($compraId)) {
            $this->mostrarToast('La compra seleccionada no es válida.', 'error');
            return null;
        }

        if ($compraId === '') {
            $compra = DB::table('compra')
                ->where('Numero_Compra', $numeroCompra)
                ->select('Id_Compra', 'Numero_Compra')
                ->first();

            if (! $compra) {
                $this->mostrarToast('Selecciona una compra válida desde las coincidencias.', 'error');
                return null;
            }

            $compraId = (string) $compra->Id_Compra;
            $numeroCompra = (string) $compra->Numero_Compra;
            $this->compraGeneralId = $compraId;
            $this->compraGeneralFiltro = $numeroCompra;
        }

        $parametros = $this->parametrosLimpios([
            'compraId' => $compraId,
            'numeroCompra' => $numeroCompra,
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Compra #' . $numeroCompra,
                route('reportes.compra-general.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.compra-general.excel', $parametros)),
            'word' => redirect()->to(route('reportes.compra-general.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

  private function generarProveedores(string $formato)
{
    if (! $this->rangoValido($this->provDesde, $this->provHasta, 'compras de proveedor')) {
        return null;
    }

    $proveedorTexto = trim($this->provFiltro);
    $proveedorId = trim($this->provProveedorId);

    if (mb_strlen($proveedorTexto) > 100) {
        $this->mostrarToast('El filtro de proveedor no debe superar los 100 caracteres.', 'error');
        return null;
    }

    if ($proveedorTexto !== '') {
        if ($proveedorId === '' || ! ctype_digit($proveedorId)) {
            $this->mostrarToast('Selecciona un proveedor válido desde las coincidencias.', 'error');
            return null;
        }

        $existeProveedor = Proveedor::query()
            ->whereKey((int) $proveedorId)
            ->exists();

        if (! $existeProveedor) {
            $this->mostrarToast('El proveedor seleccionado no existe en la base de datos.', 'error');
            return null;
        }
    }

    $parametros = $this->parametrosLimpios([
        'desde' => $this->provDesde,
        'hasta' => $this->provHasta,
        'proveedorId' => $proveedorId,
        'proveedor' => $proveedorTexto,
    ]);

    return match ($formato) {
        'pdf' => $this->abrirVisor(
            'Compras de proveedor',
            route('reportes.compras-proveedor.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
        ),
        'excel' => redirect()->to(route('reportes.compras-proveedor.excel', $parametros)),
        'word' => redirect()->to(route('reportes.compras-proveedor.word', $parametros)),
        default => $this->mostrarToast('Formato no disponible.', 'error'),
    };
}


    private function soloPdfDisponible(string $formato): bool
    {
        if ($formato === 'pdf') {
            return true;
        }

        $this->mostrarToast(
            'Por ahora Excel y Word están habilitados para Inventario, Stock próximo, Ventas, Otras salidas, Créditos institucionales, Devoluciones y Arqueos de caja.',
            'warning'
        );

        return false;
    }

    private function irViewer(string $titulo, string $endpoint, array $parametros = []): null
    {
        $parametros = $this->parametrosLimpios($parametros);
        $separador = str_contains($endpoint, '?') ? '&' : '?';

        $this->visorTitulo = $titulo;
        $this->visorUrl = $endpoint . (count($parametros) ? $separador . http_build_query($parametros) : '');
        $this->visorActivo = true;

        return null;
    }

    private function abrirVisor(string $titulo, string $url): null
    {
        $this->visorTitulo = $titulo;
        $this->visorUrl = $url;
        $this->visorActivo = true;

        return null;
    }

    private function parametrosLimpios(array $parametros): array
    {
        return collect($parametros)
            ->filter(fn ($valor) => ! is_null($valor) && trim((string) $valor) !== '')
            ->toArray();
    }

    private function rangoValido(?string $desde, ?string $hasta, string $nombreReporte): bool
    {
        if ($desde && $hasta && $desde > $hasta) {
            $this->mostrarToast(
                'La fecha inicial no puede ser mayor que la fecha final en el reporte de ' . $nombreReporte . '.',
                'error'
            );

            return false;
        }

        return true;
    }

    private function establecerQuincenaActualPlanilla(): void
    {
        $hoy = now();

        if ((int) $hoy->day <= 15) {
            $this->planillaDesde = $hoy->copy()->startOfMonth()->toDateString();
            $this->planillaHasta = $hoy->copy()->startOfMonth()->day(15)->toDateString();
            return;
        }

        $this->planillaDesde = $hoy->copy()->startOfMonth()->day(16)->toDateString();
        $this->planillaHasta = $hoy->copy()->endOfMonth()->toDateString();
    }

    private function quincenaPlanillaValida(?string $desde, ?string $hasta): bool
    {
        if (! $desde || ! $hasta) {
            $this->mostrarToast('Selecciona la fecha inicial y final de la quincena.', 'error');
            return false;
        }

        try {
            $inicio = Carbon::parse($desde)->startOfDay();
            $fin = Carbon::parse($hasta)->startOfDay();
        } catch (\Throwable) {
            $this->mostrarToast('Las fechas de planilla no son válidas.', 'error');
            return false;
        }

        if ($inicio->gt($fin)) {
            $this->mostrarToast('La fecha inicial no puede ser mayor que la fecha final en planilla.', 'error');
            return false;
        }

        if (! $inicio->isSameMonth($fin) || (int) $inicio->year !== (int) $fin->year) {
            $this->mostrarToast('La planilla debe filtrarse dentro del mismo mes.', 'error');
            return false;
        }

        $ultimoDiaMes = (int) $inicio->copy()->endOfMonth()->day;
        $primeraQuincena = (int) $inicio->day === 1 && (int) $fin->day === 15;
        $segundaQuincena = (int) $inicio->day === 16 && (int) $fin->day === $ultimoDiaMes;

        if (! $primeraQuincena && ! $segundaQuincena) {
            $this->mostrarToast(
                'Solo puedes seleccionar una quincena exacta: del 1 al 15 o del 16 al último día del mes.',
                'error'
            );

            return false;
        }

        return true;
    }

    private function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        match ($tipo) {
            'error' => $this->error($mensaje, position: 'toast-top toast-end', timeout: 3500),
            'warning' => $this->warning($mensaje, position: 'toast-top toast-end', timeout: 3000),
            'info' => $this->info($mensaje, position: 'toast-top toast-end', timeout: 2500),
            default => $this->success($mensaje, position: 'toast-top toast-end', timeout: 2500),
        };
    }
};
?>

<div class="min-h-[calc(100vh-3rem)] bg-[#F0F3F7] px-3 py-3 md:px-4">
    <div class="mx-auto flex w-full max-w-385 flex-col gap-3">

        <section class="rounded-2xl border border-[#D7E4F3] bg-white px-4 py-4 shadow-sm">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-[#1A2B42] md:text-[28px]">
                        {{ $visorActivo ? $visorTitulo : 'Informes del sistema' }}
                    </h1>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($visorActivo)
                    <x-button icon="o-arrow-left" label="Volver a reportes" wire:click="cerrarVisor"
                        spinner="cerrarVisor"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-arrow-top-right-on-square" label="Abrir aparte" link="{{ $visorUrl }}" external
                        class="h-10 min-h-10 rounded-xl border-0 bg-[#2E8BC0] text-xs font-black text-white shadow-sm hover:bg-[#0B6FE4]" />
                    @else
                    <div class="rounded-xl bg-[#2E8BC0] px-4 py-2 text-white shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-white/80">
                            Periodo sugerido
                        </p>

                        <p class="text-sm font-black">
                            {{ now()->startOfMonth()->format('d/m/Y') }} - {{ now()->format('d/m/Y') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($visorActivo)
        <section class="rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-[#1A2B42]">
                        Vista previa del reporte
                    </p>
                </div>
            </div>

            <div wire:ignore class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-[#F7F9FC]">
                <iframe src="{{ $visorUrl }}" class="h-[calc(100vh-210px)] min-h-120 w-full bg-white"></iframe>
            </div>
        </section>
        @else
        <section class="grid gap-3 grid-cols-[repeat(auto-fit,minmax(min(100%,268px),1fr))]">
            @foreach($this->tarjetasReportes() as $reporte)
            @php
            $iconoClass = match ($reporte['color']) {
            'rojo' => 'bg-red-50 text-red-600 ring-red-100',
            'ambar' => 'bg-amber-50 text-amber-700 ring-amber-100',
            default => 'bg-[#EAF2FB] text-[#0B6FE4] ring-[#D7E4F3]',
            };
            @endphp

            <article
                wire:key="reporte-{{ $reporte['id'] }}-{{ match ($reporte['id']) { 'planilla-pago' => $planillaModo, 'credito-factura' => $creditoFacturaVista, 'servicio-tecnico-factura' => $servicioTecnicoEstado, 'instalacion-camara-factura' => $instalacionCamaraEstado, default => 'normal' } }}"
                class="group flex min-h-58 flex-col rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#B7D6F2] hover:shadow-md">
                <div class="mb-3 flex items-start gap-2.5">
                    <div
                        class="{{ $iconoClass }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1">
                        <x-icon :name="$reporte['icono']" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-base font-black leading-5 text-[#1A2B42]">
                            {{ $reporte['titulo'] }}
                        </h2>

                        <p class="mt-0.5 text-xs leading-5 text-[#5F6B7A]">
                            {{ $reporte['descripcion'] }}
                        </p>
                    </div>
                </div>

                @if(!empty($reporte['campos']))
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    @foreach($reporte['campos'] as $campo)
                    @php
                    $mostrarCampo = true;

                    if (! empty($campo['mostrar_si'])) {
                    $modeloCondicion = $campo['mostrar_si']['model'] ?? '';
                    $valorCondicion = $campo['mostrar_si']['value'] ?? null;
                    $mostrarCampo = is_string($modeloCondicion)
                    && property_exists($this, $modeloCondicion)
                    && $this->{$modeloCondicion} === $valorCondicion;
                    }
                    @endphp

                    @continue(! $mostrarCampo)

                    <div class="{{ $campo['span'] ?? '' }}">
                        <label class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#1A2B42]">
                            {{ $campo['label'] }}
                        </label>

                        @if (($campo['tipo'] ?? '') === 'reporte-autocomplete')
                        @php
                        $coincidenciasCampo = $campo['coincidencias'] ?? '';
                        $coincidenciasReporte = [];
                        $accionSeleccion = $campo['accion'] ?? '';

                        if (is_string($coincidenciasCampo) && property_exists($this, $coincidenciasCampo)) {
                        $coincidenciasReporte = $this->{$coincidenciasCampo};
                        }
                        @endphp

                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="{{ $campo['model'] }}"
                                placeholder="{{ $campo['placeholder'] ?? '' }}" autocomplete="off"
                                class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />

                            @if (! empty($coincidenciasReporte) && $accionSeleccion !== '')
                            <div
                                class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($coincidenciasReporte as $opcion)
                                <button type="button" wire:key="{{ $campo['model'] }}-opcion-{{ $opcion['id'] }}"
                                    wire:click="{{ $accionSeleccion }}('{{ $opcion['id'] }}')"
                                    class="block w-full border-b border-[#EEF3F8] px-3 py-2 text-left text-xs transition hover:bg-[#EAF4FD]">
                                    <span class="block truncate font-black text-[#1A2B42]">
                                        {{ $opcion['principal'] }}
                                    </span>

                                    <span class="block truncate font-semibold text-[#5F6B7A]">
                                        {{ $opcion['secundario'] }}
                                    </span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @elseif (($campo['tipo'] ?? '') === 'credito-factura-autocomplete')
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="{{ $campo['model'] }}"
                                placeholder="{{ $campo['placeholder'] ?? '' }}" autocomplete="off"
                                class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />

                            @if (! empty($creditoFacturaCoincidencias))
                            <div
                                class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($creditoFacturaCoincidencias as $factura)
                                <button type="button" wire:key="credito-factura-reporte-{{ $factura['id'] }}"
                                    wire:click="seleccionarFacturaCreditoReporte('{{ $factura['id'] }}')"
                                    class="block w-full border-b border-[#EEF3F8] px-3 py-2 text-left text-xs transition hover:bg-[#EAF4FD]">
                                    <span class="block truncate font-black text-[#1A2B42]">
                                        Factura {{ $factura['factura'] }} · {{ $factura['cliente'] }}
                                    </span>

                                    <span class="block truncate font-semibold text-[#5F6B7A]">
                                        {{ $factura['fecha'] }} · {{ $factura['estado'] }} · Saldo {{ $factura['saldo']
                                        }}
                                    </span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @elseif (($campo['tipo'] ?? '') === 'proveedor-autocomplete')
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.350ms="{{ $campo['model'] }}"
                                placeholder="{{ $campo['placeholder'] ?? '' }}" autocomplete="off"
                                class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />

                            @if (! empty($provCoincidencias))
                            <div
                                class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($provCoincidencias as $proveedor)
                                <button type="button" wire:key="proveedor-reporte-{{ $proveedor['id'] }}"
                                    wire:click="seleccionarProveedorReporte('{{ $proveedor['id'] }}')"
                                    class="block w-full border-b border-[#EEF3F8] px-3 py-2 text-left text-xs transition hover:bg-[#EAF4FD]">
                                    <span class="block truncate font-black text-[#1A2B42]">
                                        {{ $proveedor['name'] }}
                                    </span>

                                    <span class="block truncate font-semibold text-[#5F6B7A]">
                                        RUC: {{ $proveedor['ruc'] }}
                                    </span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @elseif (($campo['tipo'] ?? '') === 'select')
                        @php
                        $opcionesCampo = $campo['opciones'] ?? '';
                        $opcionesSelect = [];

                        if (is_string($opcionesCampo) && property_exists($this, $opcionesCampo)) {
                        $opcionesSelect = $this->{$opcionesCampo};
                        }
                        @endphp

                        <x-select wire:model.live="{{ $campo['model'] }}" :options="$opcionesSelect" option-value="id"
                            option-label="name"
                            class="h-9 min-h-9 rounded-xl bg-[#F7F9FC] text-xs font-bold text-[#1A2B42]" />
                        @else
                        <input type="{{ $campo['tipo'] }}" wire:model="{{ $campo['model'] }}"
                            placeholder="{{ $campo['placeholder'] ?? '' }}" @if(($campo['tipo'] ?? '' )==='number' )
                            min="1" @endif
                            class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="rounded-xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-3 py-4">
                    <p class="text-xs font-black text-[#1A2B42]">
                        {{ $reporte['sin_filtros'] }}
                    </p>

                    <p class="mt-0.5 text-[11px] font-semibold text-[#5F6B7A]">
                        No requiere filtros adicionales.
                    </p>
                </div>
                @endif

                <div class="mt-auto grid grid-cols-3 gap-2 pt-4">
                    <x-button icon="o-eye" label="PDF" wire:click="generarReporte('{{ $reporte['id'] }}', 'pdf')"
                        spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border-0 bg-[#2E8BC0] text-xs font-black text-white shadow-sm hover:bg-[#0B6FE4]" />

                    <x-button icon="o-table-cells" label="Excel"
                        wire:click="generarReporte('{{ $reporte['id'] }}', 'excel')" spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-document-text" label="Word"
                        wire:click="generarReporte('{{ $reporte['id'] }}', 'word')" spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </article>
            @endforeach
        </section>
        @endif
    </div>
</div>
