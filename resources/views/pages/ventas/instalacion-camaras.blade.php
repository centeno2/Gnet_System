<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\Cliente;
use App\Models\Trabajador;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\MovimientoInventario;
use App\Models\ContratoInstalacionCamara;
use App\Models\ContratoInstalacionCamaraChecklist;
use App\Models\ContratoInstalacionCamaraProducto;

new class extends Component
{
    public array $clientes = [];
    public array $tecnicos = [];
    public array $productosDisponibles = [];
    public array $seriesDisponibles = [];
    public array $productosUsados = [];
    public array $contratosPendientes = [];

    public ?int $contratoInstalacionIdSeleccionado = null;
    public ?array $mensaje = null;

    public bool $modalPendientes = false;
    public string $filtroPendientes = '';

    public ?int $clienteId = null;
    public string $telefonoCliente = '';
    public string $municipio = '';
    public ?int $tecnicoId = null;

    public int|string $cantidadCamaras = 0;
    public float|string $metrosCableado = 0;
    public float|string $costoManoObra = 0;
    public float|string $porcentajeAnticipo = 30;

    public ?string $fechaEstimada = null;
    public string $direccionInstalacion = '';
    public string $detalleContrato = '';
    public string $estadoContrato = 'PENDIENTE';

    public ?int $productoId = null;
    public ?int $productoSerieId = null;
    public float|string $productoCantidad = 1;
    public float|string $productoPrecio = 0;
    public bool $productoTieneSeries = false;

    public array $checklist = [
        'incluye_instalacion_fisica' => true,
        'incluye_configuracion_app' => false,
        'incluye_pruebas_sistema' => false,
        'incluye_capacitacion_basica' => false,
        'incluye_garantia' => false,
        'anticipo_recibido' => false,
        'contrato_firmado' => false,
        'cliente_aprueba_recorrido' => false,
        'sistema_energizado' => false,
        'observacion_checklist' => '',
    ];

    public array $condicionesChecklist = [
        'incluye_instalacion_fisica' => 'Instalación física',
        'incluye_configuracion_app' => 'Configuración en app',
        'incluye_pruebas_sistema' => 'Pruebas del sistema',
        'incluye_capacitacion_basica' => 'Capacitación básica',
        'incluye_garantia' => 'Incluye garantía',
        'anticipo_recibido' => 'Anticipo recibido',
        'contrato_firmado' => 'Contrato firmado',
        'cliente_aprueba_recorrido' => 'Cliente aprueba recorrido',
        'sistema_energizado' => 'Sistema energizado',
    ];

    public array $estadosContrato = [
        ['id' => 'PENDIENTE', 'name' => 'Pendiente'],
        ['id' => 'EN_PROCESO', 'name' => 'En proceso'],
        ['id' => 'FINALIZADO', 'name' => 'Finalizado'],
        ['id' => 'CANCELADO', 'name' => 'Cancelado'],
    ];

    public array $headers = [
        ['key' => 'codigo', 'label' => 'Código'],
        ['key' => 'descripcion', 'label' => 'Descripción'],
        ['key' => 'serie', 'label' => 'Serie'],
        ['key' => 'cantidad', 'label' => 'Cantidad'],
        ['key' => 'precio', 'label' => 'Precio'],
        ['key' => 'subtotal', 'label' => 'Subtotal'],
        ['key' => 'acciones', 'label' => ''],
    ];

    public function mount(): void
    {
        $this->cargarCombos();
        $this->cargarPendientes();
    }

    public function cargarCombos(): void
    {
        $this->clientes = Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Estado', 1)
            ->select([
                'cliente.Id_Cliente as id',
                'cliente.Institucion',
                'cliente.Telefono_Institucion',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'p.Telefono',
            ])
            ->orderBy('cliente.Institucion')
            ->orderBy('p.Primer_Nombre')
            ->get()
            ->map(function ($item) {
                $nombrePersona = trim(
                    ($item->Primer_Nombre ?? '') . ' ' .
                    ($item->Segundo_Nombre ?? '') . ' ' .
                    ($item->Primer_Apellido ?? '') . ' ' .
                    ($item->Segundo_Apellido ?? '')
                );

                $nombre = trim(
                    ($item->Institucion ? $item->Institucion . ' - ' : '') .
                    $nombrePersona
                );

                $telefono = $item->Telefono ?: $item->Telefono_Institucion;

                return [
                    'id' => (int) $item->id,
                    'name' => $this->limpiarTexto(($nombre ?: 'Cliente sin nombre') . ' | Tel: ' . ($telefono ?: 'N/A')),
                ];
            })
            ->toArray();

        $this->tecnicos = Trabajador::query()
            ->join('persona as p', 'p.Id_Persona', '=', 'trabajador.Id_Persona')
            ->leftJoin('cargo as cg', 'cg.Id_Cargo', '=', 'trabajador.Id_Cargo')
            ->where('trabajador.Estado', 1)
            ->select([
                'trabajador.Id_Trabajador as id',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'cg.Cargo_Asignado',
            ])
            ->orderBy('p.Primer_Nombre')
            ->orderBy('p.Primer_Apellido')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $this->limpiarTexto(
                    trim(
                        ($item->Primer_Nombre ?? '') . ' ' .
                        ($item->Segundo_Nombre ?? '') . ' ' .
                        ($item->Primer_Apellido ?? '') . ' ' .
                        ($item->Segundo_Apellido ?? '')
                    ) . ' - ' . ($item->Cargo_Asignado ?: 'Trabajador')
                ),
            ])
            ->toArray();

        $seriesDisponiblesPorProducto = ProductoSerie::query()
            ->where('Estado', 'DISPONIBLE')
            ->get(['Id_Producto'])
            ->groupBy('Id_Producto')
            ->map(fn ($items) => $items->count());

        $this->productosDisponibles = Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Estado', 1)
            ->where('producto.Stock_Actual', '>', 0)
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->orderBy('producto.Nombre_Producto')
            ->get()
            ->map(function ($item) use ($seriesDisponiblesPorProducto) {
                $seriesDisponibles = (int) ($seriesDisponiblesPorProducto[$item->id] ?? 0);

                $nombre = $this->limpiarTexto(
                    trim(
                        ($item->Nombre_Marca ? $item->Nombre_Marca . ' ' : '') .
                        $item->Nombre_Producto . ' ' .
                        ($item->Modelo ?? '')
                    )
                );

                return [
                    'id' => (int) $item->id,
                    'name' => $nombre .
                        ' - Stock: ' . (int) $item->Stock_Actual .
                        ($seriesDisponibles > 0 ? ' | Series: ' . $seriesDisponibles : ''),
                    'precio' => (float) $item->precio,
                    'series_disponibles' => $seriesDisponibles,
                ];
            })
            ->toArray();
    }

    public function cargarPendientes(): void
    {
        $tablaContrato = (new ContratoInstalacionCamara())->getTable();

        $query = ContratoInstalacionCamara::query()
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', $tablaContrato . '.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', $tablaContrato . '.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->whereNotIn($tablaContrato . '.Estado_Contrato', ['FINALIZADO', 'CANCELADO'])
            ->select([
                $tablaContrato . '.Id_Contrato_Instalacion_Camara as id',
                $tablaContrato . '.Numero_Contrato as numero',
                $tablaContrato . '.Fecha_Contrato as fecha',
                $tablaContrato . '.Municipio as municipio',
                $tablaContrato . '.Direccion_Instalacion as direccion',
                $tablaContrato . '.Cantidad_Camaras as camaras',
                $tablaContrato . '.Estado_Contrato as estado',
                $tablaContrato . '.Total_Contrato as total',
                $tablaContrato . '.Saldo_Pendiente as saldo',
                'c.Institucion as cliente_institucion',
                'pc.Primer_Nombre as cliente_primer_nombre',
                'pc.Segundo_Nombre as cliente_segundo_nombre',
                'pc.Primer_Apellido as cliente_primer_apellido',
                'pc.Segundo_Apellido as cliente_segundo_apellido',
                'pt.Primer_Nombre as tecnico_primer_nombre',
                'pt.Primer_Apellido as tecnico_primer_apellido',
            ])
            ->orderByDesc($tablaContrato . '.Fecha_Contrato')
            ->limit(25);

        $filtro = trim($this->filtroPendientes);

        if ($filtro !== '') {
            $query->where(function ($q) use ($filtro, $tablaContrato) {
                $q->where($tablaContrato . '.Numero_Contrato', 'like', '%' . $filtro . '%')
                    ->orWhere($tablaContrato . '.Municipio', 'like', '%' . $filtro . '%')
                    ->orWhere($tablaContrato . '.Direccion_Instalacion', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Nombre', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Apellido', 'like', '%' . $filtro . '%')
                    ->orWhere('c.Institucion', 'like', '%' . $filtro . '%');
            });
        }

        $this->contratosPendientes = $query
            ->get()
            ->map(function ($item) {
                $cliente = $this->limpiarTexto(
                    trim(
                        ($item->cliente_institucion ? $item->cliente_institucion . ' - ' : '') .
                        ($item->cliente_primer_nombre ?? '') . ' ' .
                        ($item->cliente_segundo_nombre ?? '') . ' ' .
                        ($item->cliente_primer_apellido ?? '') . ' ' .
                        ($item->cliente_segundo_apellido ?? '')
                    )
                );

                $tecnico = $this->limpiarTexto(
                    trim(
                        ($item->tecnico_primer_nombre ?? '') . ' ' .
                        ($item->tecnico_primer_apellido ?? '')
                    )
                );

                return [
                    'id' => (int) $item->id,
                    'numero' => $item->numero,
                    'fecha' => $item->fecha,
                    'cliente' => $cliente ?: 'Cliente no especificado',
                    'ubicacion' => $this->limpiarTexto(
                        trim(($item->municipio ?? '') . ' ' . ($item->direccion ?? ''))
                    ) ?: 'Sin ubicación',
                    'tecnico' => $tecnico ?: 'Sin técnico',
                    'camaras' => (int) $item->camaras,
                    'estado' => $item->estado,
                    'total' => (float) $item->total,
                    'saldo' => (float) $item->saldo,
                ];
            })
            ->toArray();
    }

    public function abrirPendientes(): void
    {
        $this->cargarPendientes();
        $this->modalPendientes = true;
    }

    public function updatedFiltroPendientes(): void
    {
        $this->cargarPendientes();
    }

    public function cargarPendiente(int $id, bool $cerrarModal = true): void
    {
        $contrato = ContratoInstalacionCamara::query()
            ->where('Id_Contrato_Instalacion_Camara', $id)
            ->first();

        if (!$contrato) {
            $this->mostrarMensaje('error', 'No encontrado', 'El contrato seleccionado ya no existe.');
            return;
        }

        $this->contratoInstalacionIdSeleccionado = (int) $contrato->Id_Contrato_Instalacion_Camara;
        $this->clienteId = $contrato->Id_Cliente ? (int) $contrato->Id_Cliente : null;
        $this->updatedClienteId($this->clienteId);

        $this->tecnicoId = $contrato->Id_Trabajador ? (int) $contrato->Id_Trabajador : null;
        $this->municipio = (string) ($contrato->Municipio ?? $this->municipio);
        $this->direccionInstalacion = (string) $contrato->Direccion_Instalacion;
        $this->cantidadCamaras = (int) $contrato->Cantidad_Camaras;
        $this->metrosCableado = (float) $contrato->Metros_Cableado;
        $this->costoManoObra = (float) $contrato->Costo_Mano_Obra;
        $this->porcentajeAnticipo = (float) $contrato->Porcentaje_Anticipo;
        $this->fechaEstimada = $this->normalizarFechaInput($contrato->Fecha_Estimada);
        $this->detalleContrato = (string) ($contrato->Detalle_Contrato ?? '');
        $this->estadoContrato = (string) $contrato->Estado_Contrato;

        $this->cargarChecklistContrato((int) $contrato->Id_Contrato_Instalacion_Camara);
        $this->cargarProductosDelContrato((int) $contrato->Id_Contrato_Instalacion_Camara);
        $this->resetProductoForm();
        $this->resetErrorBag();

        if ($cerrarModal) {
            $this->modalPendientes = false;
        }

        $this->mostrarMensaje('success', 'Pendiente cargado', 'Ya podés actualizar el contrato o agregar materiales.');
    }

    public function nuevoContrato(): void
    {
        $this->limpiarFormulario();
        $this->cargarCombos();
        $this->cargarPendientes();

        $this->mostrarMensaje(
            'success',
            'Formulario limpio',
            'Listo para registrar un nuevo contrato de instalación.'
        );
    }

    public function updatedClienteId($value): void
    {
        $this->telefonoCliente = '';
        $this->municipio = '';

        if (!$value) {
            return;
        }

        $cliente = Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Id_Cliente', $value)
            ->select([
                'p.Telefono',
                'cliente.Telefono_Institucion',
                'cliente.Municipio',
            ])
            ->first();

        $this->telefonoCliente = (string) (
            $cliente->Telefono
            ?: $cliente->Telefono_Institucion
            ?: ''
        );

        $this->municipio = (string) ($cliente->Municipio ?? '');
    }

    public function updatedProductoId($value): void
    {
        $this->productoSerieId = null;
        $this->seriesDisponibles = [];
        $this->productoTieneSeries = false;
        $this->productoCantidad = 1;
        $this->productoPrecio = 0;

        if (!$value) {
            return;
        }

        $producto = Producto::query()
            ->where('Id_Producto', $value)
            ->first();

        if (!$producto) {
            return;
        }

        $this->productoPrecio = (float) $producto->Precio_Venta;

        $seriesUsadasEnPantalla = collect($this->productosUsados)
            ->pluck('producto_serie_id')
            ->filter()
            ->values()
            ->all();

        $query = ProductoSerie::query()
            ->where('Id_Producto', $value)
            ->where('Estado', 'DISPONIBLE');

        if (!empty($seriesUsadasEnPantalla)) {
            $query->whereNotIn('id_producto_serie', $seriesUsadasEnPantalla);
        }

        $this->seriesDisponibles = $query
            ->orderBy('Numero_Serie')
            ->get(['id_producto_serie', 'Numero_Serie'])
            ->map(fn ($item) => [
                'id' => (int) $item->id_producto_serie,
                'name' => $item->Numero_Serie,
            ])
            ->toArray();

        $this->productoTieneSeries = ProductoSerie::query()
            ->where('Id_Producto', $value)
            ->exists();

        if ($this->productoTieneSeries) {
            $this->productoCantidad = 1;
        }
    }

    public function agregarProducto(): void
    {
        $this->validate([
            'productoId' => ['required', 'integer'],
            'productoCantidad' => ['required', 'numeric', 'min:0.01'],
            'productoPrecio' => ['required', 'numeric', 'min:0'],
        ], [
            'productoId.required' => 'Seleccione un producto.',
            'productoCantidad.min' => 'La cantidad debe ser mayor a cero.',
        ]);

        $producto = Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Id_Producto', $this->productoId)
            ->select([
                'producto.*',
                'm.Nombre_Marca',
            ])
            ->first();

        if (!$producto || (int) $producto->Estado !== 1 || (int) $producto->Stock_Actual <= 0) {
            $this->addError('productoId', 'El producto no está disponible.');
            return;
        }

        $serie = null;
        $cantidad = (float) $this->productoCantidad;

        if ($this->productoTieneSeries) {
            if (!$this->productoSerieId) {
                $this->addError('productoSerieId', 'Seleccione la serie del producto.');
                return;
            }

            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $this->productoSerieId)
                ->where('Id_Producto', $this->productoId)
                ->where('Estado', 'DISPONIBLE')
                ->first();

            if (!$serie) {
                $this->addError('productoSerieId', 'La serie seleccionada ya no está disponible.');
                return;
            }

            $cantidad = 1;
        }

        $cantidadYaAgregada = collect($this->productosUsados)
            ->where('producto_id', $this->productoId)
            ->sum('cantidad');

        if (!$this->productoTieneSeries && ($cantidadYaAgregada + $cantidad) > (float) $producto->Stock_Actual) {
            $this->addError('productoCantidad', 'La cantidad supera el stock disponible.');
            return;
        }

        if ($serie && collect($this->productosUsados)->contains('producto_serie_id', (int) $serie->id_producto_serie)) {
            $this->addError('productoSerieId', 'Esta serie ya fue agregada.');
            return;
        }

        $precio = round((float) $this->productoPrecio, 2);
        $subtotal = round($cantidad * $precio, 2);

        $this->productosUsados[] = [
            'tmp_id' => uniqid('prod_', true),
            'ya_guardado' => false,
            'producto_id' => (int) $producto->Id_Producto,
            'producto_serie_id' => $serie?->id_producto_serie ? (int) $serie->id_producto_serie : null,
            'codigo' => (string) $producto->Id_Producto,
            'descripcion' => $this->limpiarTexto(
                trim(
                    ($producto->Nombre_Marca ? $producto->Nombre_Marca . ' ' : '') .
                    $producto->Nombre_Producto . ' ' .
                    ($producto->Modelo ?? '')
                )
            ),
            'serie' => $serie->Numero_Serie ?? 'N/A',
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'acciones' => '',
        ];

        $this->resetProductoForm();
        $this->cargarCombos();

        $this->mostrarMensaje(
            'success',
            'Producto agregado',
            'El material quedó listo para guardarse con el contrato.'
        );
    }

    public function quitarProducto(string $tmpId): void
    {
        $producto = collect($this->productosUsados)->firstWhere('tmp_id', $tmpId);

        if ($producto && !empty($producto['ya_guardado'])) {
            $this->mostrarMensaje('error', 'No permitido', 'Este material ya fue descontado del inventario. Para revertirlo hay que hacer una devolución o ajuste de inventario.');
            return;
        }

        $this->productosUsados = array_values(array_filter(
            $this->productosUsados,
            fn ($item) => $item['tmp_id'] !== $tmpId
        ));

        $this->cargarCombos();

        $this->mostrarMensaje(
            'success',
            'Producto quitado',
            'El material fue removido del contrato.'
        );
    }

    public function guardar(): void
    {
        $this->validate([
            'clienteId' => ['required', 'integer'],
            'tecnicoId' => ['nullable', 'integer'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'direccionInstalacion' => ['required', 'string', 'max:255'],
            'cantidadCamaras' => ['required', 'integer', 'min:1'],
            'metrosCableado' => ['required', 'numeric', 'min:0'],
            'costoManoObra' => ['required', 'numeric', 'min:0'],
            'porcentajeAnticipo' => ['required', 'numeric', 'min:0', 'max:100'],
            'fechaEstimada' => ['nullable', 'date'],
            'detalleContrato' => ['nullable', 'string', 'max:1000'],
            'estadoContrato' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADO,CANCELADO'],
        ], [
            'clienteId.required' => 'Seleccione el cliente.',
            'direccionInstalacion.required' => 'Ingrese la dirección de instalación.',
            'cantidadCamaras.min' => 'Ingrese al menos una cámara.',
        ]);

        try {
            if ($this->contratoInstalacionIdSeleccionado) {
                $this->actualizarContratoInstalacion();

                $id = $this->contratoInstalacionIdSeleccionado;

                $this->cargarCombos();
                $this->cargarPendientes();
                $this->cargarPendiente($id, false);

                $this->mostrarMensaje('success', 'Contrato actualizado', 'La instalación de cámaras se actualizó correctamente.');
                return;
            }

            $contratoId = $this->crearContratoInstalacion();

            $this->limpiarFormulario();
            $this->cargarCombos();
            $this->cargarPendientes();

            $this->mostrarMensaje(
                'success',
                'Contrato guardado',
                'La instalación de cámaras se registró correctamente. Contrato #' . $contratoId . '.'
            );
        } catch (\Throwable $e) {
            report($e);

            $this->mostrarMensaje(
                'error',
                'No se pudo guardar',
                $e->getMessage()
            );
        }
    }

    public function estadoNombre(?string $estado): string
    {
        return match ($estado) {
            'PENDIENTE' => 'Pendiente',
            'EN_PROCESO' => 'En proceso',
            'FINALIZADO' => 'Finalizado',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', (string) $estado),
        };
    }

    private function crearContratoInstalacion(): int
    {
        return DB::transaction(function () {
            $usuarioId = $this->usuarioActualId();

            $numeroContrato = $this->generarNumeroUnico(
                'IC',
                ContratoInstalacionCamara::class,
                'Numero_Contrato'
            );

            $servicioId = $this->servicioPorTipo('INSTALACION');

            $totalMateriales = round((float) collect($this->productosUsados)->sum('subtotal'), 2);
            $totalContrato = round($totalMateriales + (float) $this->costoManoObra, 2);
            $montoAnticipo = round($totalContrato * ((float) $this->porcentajeAnticipo / 100), 2);
            $saldoPendiente = round($totalContrato - $montoAnticipo, 2);

            $contrato = ContratoInstalacionCamara::query()->create([
                'Numero_Contrato' => $numeroContrato,
                'Fecha_Contrato' => now(),
                'Id_Cliente' => $this->clienteId,
                'Id_Usuario' => $usuarioId,
                'Id_Servicio' => $servicioId,
                'Id_Trabajador' => $this->tecnicoId,
                'Municipio' => $this->municipio ?: null,
                'Direccion_Instalacion' => $this->direccionInstalacion,
                'Cantidad_Camaras' => (int) $this->cantidadCamaras,
                'Metros_Cableado' => (float) $this->metrosCableado,
                'Costo_Mano_Obra' => (float) $this->costoManoObra,
                'Porcentaje_Anticipo' => (float) $this->porcentajeAnticipo,
                'Monto_Anticipo' => $montoAnticipo,
                'Fecha_Estimada' => $this->fechaEstimada ?: null,
                'Detalle_Contrato' => $this->detalleContrato ?: null,
                'Estado_Contrato' => $this->estadoContrato,
                'Total_Materiales' => $totalMateriales,
                'Total_Contrato' => $totalContrato,
                'Saldo_Pendiente' => $saldoPendiente,
            ]);

            $contratoId = (int) $contrato->Id_Contrato_Instalacion_Camara;

            ContratoInstalacionCamaraChecklist::query()->create(
                $this->datosChecklist($contratoId)
            );

            foreach ($this->productosUsados as $item) {
                $this->registrarProductoContrato($contratoId, $item);
            }

            return $contratoId;
        }, 3);
    }

    private function actualizarContratoInstalacion(): void
    {
        DB::transaction(function () {
            $contratoId = (int) $this->contratoInstalacionIdSeleccionado;

            $contrato = ContratoInstalacionCamara::query()
                ->where('Id_Contrato_Instalacion_Camara', $contratoId)
                ->lockForUpdate()
                ->first();

            if (!$contrato) {
                throw new \RuntimeException('El contrato seleccionado ya no existe.');
            }

            $totalMateriales = round((float) collect($this->productosUsados)->sum('subtotal'), 2);
            $totalContrato = round($totalMateriales + (float) $this->costoManoObra, 2);
            $montoAnticipo = round($totalContrato * ((float) $this->porcentajeAnticipo / 100), 2);
            $saldoPendiente = round($totalContrato - $montoAnticipo, 2);

            $contrato->forceFill([
                'Id_Cliente' => $this->clienteId,
                'Id_Trabajador' => $this->tecnicoId,
                'Municipio' => $this->municipio ?: null,
                'Direccion_Instalacion' => $this->direccionInstalacion,
                'Cantidad_Camaras' => (int) $this->cantidadCamaras,
                'Metros_Cableado' => (float) $this->metrosCableado,
                'Costo_Mano_Obra' => (float) $this->costoManoObra,
                'Porcentaje_Anticipo' => (float) $this->porcentajeAnticipo,
                'Monto_Anticipo' => $montoAnticipo,
                'Fecha_Estimada' => $this->fechaEstimada ?: null,
                'Detalle_Contrato' => $this->detalleContrato ?: null,
                'Estado_Contrato' => $this->estadoContrato,
                'Total_Materiales' => $totalMateriales,
                'Total_Contrato' => $totalContrato,
                'Saldo_Pendiente' => $saldoPendiente,
            ])->save();

            $checklist = ContratoInstalacionCamaraChecklist::query()
                ->firstOrNew(['Id_Contrato_Instalacion_Camara' => $contratoId]);

            $checklist->forceFill($this->datosChecklist($contratoId))->save();

            foreach ($this->productosUsados as $item) {
                if (!empty($item['ya_guardado'])) {
                    continue;
                }

                $this->registrarProductoContrato($contratoId, $item);
            }
        }, 3);
    }

    private function registrarProductoContrato(int $contratoId, array $item): void
    {
        $this->descontarInventario($item, 'INSTALADO', 'SALIDA_INSTALACION');

        ContratoInstalacionCamaraProducto::query()->create([
            'Id_Contrato_Instalacion_Camara' => $contratoId,
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Cantidad' => $item['cantidad'],
            'Precio_Unitario' => $item['precio'],
            'Subtotal' => $item['subtotal'],
            'Observacion' => null,
        ]);
    }

    private function cargarChecklistContrato(int $contratoId): void
    {
        $check = ContratoInstalacionCamaraChecklist::query()
            ->where('Id_Contrato_Instalacion_Camara', $contratoId)
            ->first();

        $this->checklist = [
            'incluye_instalacion_fisica' => (bool) ($check->Incluye_Instalacion_Fisica ?? true),
            'incluye_configuracion_app' => (bool) ($check->Incluye_Configuracion_App ?? false),
            'incluye_pruebas_sistema' => (bool) ($check->Incluye_Pruebas_Sistema ?? false),
            'incluye_capacitacion_basica' => (bool) ($check->Incluye_Capacitacion_Basica ?? false),
            'incluye_garantia' => (bool) ($check->Incluye_Garantia ?? false),
            'anticipo_recibido' => (bool) ($check->Anticipo_Recibido ?? false),
            'contrato_firmado' => (bool) ($check->Contrato_Firmado ?? false),
            'cliente_aprueba_recorrido' => (bool) ($check->Cliente_Aprueba_Recorrido ?? false),
            'sistema_energizado' => (bool) ($check->Sistema_Energizado ?? false),
            'observacion_checklist' => (string) ($check->Observacion_Checklist ?? ''),
        ];
    }

    private function cargarProductosDelContrato(int $contratoId): void
    {
        $tablaDetalle = (new ContratoInstalacionCamaraProducto())->getTable();

        $this->productosUsados = ContratoInstalacionCamaraProducto::query()
            ->join('producto as p', 'p.Id_Producto', '=', $tablaDetalle . '.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', $tablaDetalle . '.Id_Producto_Serie')
            ->where($tablaDetalle . '.Id_Contrato_Instalacion_Camara', $contratoId)
            ->select([
                $tablaDetalle . '.Id_Producto',
                $tablaDetalle . '.Id_Producto_Serie',
                $tablaDetalle . '.Cantidad',
                $tablaDetalle . '.Precio_Unitario',
                $tablaDetalle . '.Subtotal',
                'p.Nombre_Producto',
                'p.Modelo',
                'm.Nombre_Marca',
                'ps.Numero_Serie',
            ])
            ->orderBy($tablaDetalle . '.Id_Producto')
            ->get()
            ->values()
            ->map(fn ($item, $index) => [
                'tmp_id' => 'guardado_' . $contratoId . '_' . $index,
                'ya_guardado' => true,
                'producto_id' => (int) $item->Id_Producto,
                'producto_serie_id' => $item->Id_Producto_Serie ? (int) $item->Id_Producto_Serie : null,
                'codigo' => (string) $item->Id_Producto,
                'descripcion' => $this->limpiarTexto(
                    trim(
                        ($item->Nombre_Marca ? $item->Nombre_Marca . ' ' : '') .
                        $item->Nombre_Producto . ' ' .
                        ($item->Modelo ?? '')
                    )
                ),
                'serie' => $item->Numero_Serie ?? 'N/A',
                'cantidad' => (float) $item->Cantidad,
                'precio' => (float) $item->Precio_Unitario,
                'subtotal' => (float) $item->Subtotal,
                'acciones' => '',
            ])
            ->toArray();
    }

    private function datosChecklist(int $contratoId): array
    {
        return [
            'Id_Contrato_Instalacion_Camara' => $contratoId,
            'Incluye_Instalacion_Fisica' => (bool) $this->checklist['incluye_instalacion_fisica'],
            'Incluye_Configuracion_App' => (bool) $this->checklist['incluye_configuracion_app'],
            'Incluye_Pruebas_Sistema' => (bool) $this->checklist['incluye_pruebas_sistema'],
            'Incluye_Capacitacion_Basica' => (bool) $this->checklist['incluye_capacitacion_basica'],
            'Incluye_Garantia' => (bool) $this->checklist['incluye_garantia'],
            'Anticipo_Recibido' => (bool) $this->checklist['anticipo_recibido'],
            'Contrato_Firmado' => (bool) $this->checklist['contrato_firmado'],
            'Cliente_Aprueba_Recorrido' => (bool) $this->checklist['cliente_aprueba_recorrido'],
            'Sistema_Energizado' => (bool) $this->checklist['sistema_energizado'],
            'Observacion_Checklist' => $this->checklist['observacion_checklist'] ?: null,
        ];
    }

    private function resetProductoForm(): void
    {
        $this->productoId = null;
        $this->productoSerieId = null;
        $this->productoCantidad = 1;
        $this->productoPrecio = 0;
        $this->productoTieneSeries = false;
        $this->seriesDisponibles = [];

        $this->resetErrorBag([
            'productoId',
            'productoSerieId',
            'productoCantidad',
            'productoPrecio',
        ]);
    }

    private function limpiarFormulario(): void
    {
        $this->contratoInstalacionIdSeleccionado = null;
        $this->clienteId = null;
        $this->telefonoCliente = '';
        $this->municipio = '';
        $this->tecnicoId = null;

        $this->cantidadCamaras = 0;
        $this->metrosCableado = 0;
        $this->costoManoObra = 0;
        $this->porcentajeAnticipo = 30;

        $this->fechaEstimada = null;
        $this->direccionInstalacion = '';
        $this->detalleContrato = '';
        $this->estadoContrato = 'PENDIENTE';

        $this->productosUsados = [];

        $this->checklist = [
            'incluye_instalacion_fisica' => true,
            'incluye_configuracion_app' => false,
            'incluye_pruebas_sistema' => false,
            'incluye_capacitacion_basica' => false,
            'incluye_garantia' => false,
            'anticipo_recibido' => false,
            'contrato_firmado' => false,
            'cliente_aprueba_recorrido' => false,
            'sistema_energizado' => false,
            'observacion_checklist' => '',
        ];

        $this->resetProductoForm();
        $this->resetErrorBag();
    }

    private function usuarioActualId(): int
    {
        $id = session('Id_Usuario')
            ?? session('id_usuario')
            ?? session('usuario_id')
            ?? auth()->user()?->Id_Usuario
            ?? auth()->id();

        if (!$id) {
            $id = Usuario::query()
                ->where('Estado', 1)
                ->value('Id_Usuario');
        }

        if (!$id) {
            throw new \RuntimeException('No hay usuario activo para registrar el movimiento.');
        }

        return (int) $id;
    }

    private function servicioPorTipo(string $tipo): int
    {
        $id = Servicio::query()
            ->where('Tipo_Servicio', $tipo)
            ->where('Estado', 1)
            ->value('Id_Servicio');

        if ($id) {
            return (int) $id;
        }

        $datos = match ($tipo) {
            'INSTALACION' => [
                'Nombre_Servicio' => 'Instalación de cámaras',
                'Descripcion' => 'Contrato de instalación, configuración y pruebas de sistemas de cámaras.',
                'Tipo_Servicio' => 'INSTALACION',
                'Unidad_Medida' => 'CONTRATO',
            ],
            default => [
                'Nombre_Servicio' => 'Servicio general',
                'Descripcion' => 'Servicio registrado desde el sistema.',
                'Tipo_Servicio' => 'GENERAL',
                'Unidad_Medida' => 'SERVICIO',
            ],
        };

        $servicio = $this->crearModelo(Servicio::class, array_merge($datos, [
            'Precio_Base' => 0,
            'Requiere_Contrato' => 1,
            'Requiere_Anticipo' => 1,
            'Porcentaje_Anticipo' => 30,
            'Garantia' => 1,
            'Estado' => 1,
            'Permite_Credito' => 1,
        ]));

        return (int) $servicio->Id_Servicio;
    }

    private function generarNumeroUnico(string $prefijo, string $modelo, string $columna): string
    {
        do {
            $numero = $prefijo . '-' . now()->format('Ymd') . '-' . str_pad(
                (string) random_int(1, 9999),
                4,
                '0',
                STR_PAD_LEFT
            );
        } while ($modelo::query()->where($columna, $numero)->exists());

        return $numero;
    }

    private function descontarInventario(array $item, string $estadoSerie, string $tipoMovimiento): void
    {
        $producto = Producto::query()
            ->where('Id_Producto', $item['producto_id'])
            ->lockForUpdate()
            ->first();

        if (!$producto || (int) $producto->Estado !== 1) {
            throw new \RuntimeException('Producto no disponible: ' . $item['descripcion']);
        }

        $cantidad = (int) ceil((float) $item['cantidad']);

        if ($cantidad <= 0) {
            throw new \RuntimeException('Cantidad inválida para: ' . $item['descripcion']);
        }

        if ((int) $producto->Stock_Actual < $cantidad) {
            throw new \RuntimeException('Stock insuficiente para: ' . $item['descripcion']);
        }

        if ($item['producto_serie_id']) {
            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $item['producto_serie_id'])
                ->lockForUpdate()
                ->first();

            if (!$serie || $serie->Estado !== 'DISPONIBLE') {
                throw new \RuntimeException('La serie ya no está disponible: ' . $item['serie']);
            }

            $serie->forceFill([
                'Estado' => $estadoSerie,
                'Observacion' => 'Instalado en contrato de cámaras',
            ])->save();
        }

        $producto->decrement('Stock_Actual', $cantidad);

        $this->crearModelo(MovimientoInventario::class, [
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Fecha_Movimiento' => now(),
            'Tipo_Movimiento' => $tipoMovimiento,
            'Cantidad' => $cantidad,
            'Motivo_Movimiento' => 1,
        ]);
    }

    private function crearModelo(string $modelo, array $datos): Model
    {
        /** @var Model $instancia */
        $instancia = new $modelo();

        $instancia->forceFill($datos);
        $instancia->save();

        return $instancia;
    }

    private function normalizarFechaInput(mixed $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('Y-m-d');
        }

        return substr((string) $fecha, 0, 10);
    }

    private function limpiarTexto(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $texto));
    }

    private function mostrarMensaje(string $tipo, string $titulo, string $descripcion): void
    {
        $this->mensaje = [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
        ];
    }
};
?>

<div class="h-[calc(100vh-3rem)] min-h-0 overflow-hidden bg-[#F0F3F7]">
    <div class="flex h-full min-h-0 flex-col gap-4 px-4 py-4 md:px-6">

        <div
            class="sticky top-0 z-20 -mx-4 -mt-4 border-b border-[#D7E4F3] bg-[#F0F3F7]/95 px-4 py-4 backdrop-blur md:-mx-6 md:px-6">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-[#1A2B42]">
                            Instalación de cámaras
                        </h1>

                        @if($contratoInstalacionIdSeleccionado)
                        <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                            Editando #{{ $contratoInstalacionIdSeleccionado }}
                        </span>
                        @else
                        <span
                            class="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#5F6B7A] ring-1 ring-[#D7E4F3]">
                            Nuevo contrato
                        </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Registro del contrato, materiales, condiciones y resumen económico en una sola pantalla.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-button icon="o-document-plus" label="Nuevo" wire:click="nuevoContrato"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </div>
        </div>

        @if($mensaje)
        <div
            class="fixed right-5 top-5 z-50 w-[min(420px,calc(100vw-2rem))] rounded-2xl border px-4 py-3 shadow-xl {{ $mensaje['tipo'] === 'success' ? 'border-[#B7E4C7] bg-[#ECFDF3] text-[#166534]' : 'border-[#F5C2C7] bg-[#FEF2F2] text-[#991B1B]' }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-black">{{ $mensaje['titulo'] }}</p>
                    <p class="text-sm">{{ $mensaje['descripcion'] }}</p>
                </div>

                <button type="button" wire:click="$set('mensaje', null)"
                    class="rounded-lg px-2 text-sm font-black opacity-70 hover:bg-white/60 hover:opacity-100">
                    X
                </button>
            </div>
        </div>
        @endif

        @php
        $totalMateriales = round((float) collect($productosUsados)->sum('subtotal'), 2);
        $totalContrato = round($totalMateriales + (float) $costoManoObra, 2);
        $anticipo = round($totalContrato * ((float) $porcentajeAnticipo / 100), 2);
        $saldo = round($totalContrato - $anticipo, 2);
        @endphp

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 overflow-hidden xl:grid-cols-12">
            <div class="min-h-0 overflow-y-auto pr-0 xl:col-span-8 xl:pr-1">
                <div class="space-y-4">

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Datos del contrato</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Cliente, técnico, ubicación y condiciones principales de instalación.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Total contrato</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalContrato, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    Cliente / institución
                                </label>

                                <x-select wire:model.live="clienteId" :options="$clientes" option-value="id"
                                    option-label="name" placeholder="Seleccione cliente"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('clienteId')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Teléfono</label>
                                <x-input wire:model="telefonoCliente" readonly
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Municipio</label>
                                <x-input wire:model="municipio"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('municipio')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cámaras</label>
                                <x-input wire:model.live="cantidadCamaras" type="number"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('cantidadCamaras')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Metros cableado</label>
                                <x-input wire:model.live="metrosCableado" type="number" step="0.01"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('metrosCableado')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Mano de obra</label>
                                <x-input wire:model.live="costoManoObra" type="number" step="0.01" prefix="C$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('costoManoObra')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Anticipo</label>
                                <x-input wire:model.live="porcentajeAnticipo" type="number" step="0.01" suffix="%"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('porcentajeAnticipo')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Técnico asignado</label>

                                <x-select wire:model="tecnicoId" :options="$tecnicos" option-value="id"
                                    option-label="name" placeholder="Opcional"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('tecnicoId')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Fecha estimada</label>
                                <x-input wire:model="fechaEstimada" type="date"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('fechaEstimada')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Estado</label>

                                <x-select wire:model="estadoContrato" :options="$estadosContrato" option-value="id"
                                    option-label="name"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('estadoContrato')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    Dirección de instalación
                                </label>

                                <x-input wire:model="direccionInstalacion"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('direccionInstalacion')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    Detalle del contrato
                                </label>

                                <x-textarea wire:model="detalleContrato" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                @error('detalleContrato')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-black text-[#1A2B42]">Condiciones del servicio</h2>
                            <p class="text-sm text-[#5F6B7A]">
                                Checklist rápido de instalación y entrega.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-4">
                            @foreach($condicionesChecklist as $key => $label)
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-2xl border border-[#2E8BC0] bg-[#F7F9FC] px-4 py-3 text-sm font-bold text-[#1A2B42] transition hover:bg-[#EAF2FB]">
                                <x-checkbox wire:model.live="checklist.{{ $key }}"
                                    class="checkbox-sm border-2 border-[#2E8BC0] bg-white text-white checked:border-[#0B6FE4] checked:bg-[#0B6FE4] checked:[--chkbg:#0B6FE4] checked:[--chkfg:white]" />

                                <span class="leading-tight">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                Observación del checklist
                            </label>

                            <x-textarea wire:model="checklist.observacion_checklist" rows="2"
                                class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Materiales / productos usados</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Agregá productos directo aquí. Las series instaladas salen del inventario
                                    disponible.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Materiales</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalMateriales, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3"
                            wire:keydown.enter.prevent="agregarProducto">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <div class="md:col-span-5">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Producto</label>

                                    <x-select wire:model.live="productoId" :options="$productosDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione producto"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />

                                    @error('productoId')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if($productoTieneSeries)
                                <div class="md:col-span-3">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie</label>

                                    <x-select wire:model="productoSerieId" :options="$seriesDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione serie"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />

                                    @error('productoSerieId')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                @else
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cantidad</label>

                                    <x-input wire:model="productoCantidad" type="number" step="0.01"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />

                                    @error('productoCantidad')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                @endif

                                <div class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-2' }}">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Precio</label>

                                    <x-input wire:model="productoPrecio" type="number" step="0.01" prefix="C$"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />

                                    @error('productoPrecio')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div
                                    class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-3' }} flex items-end">
                                    <x-button icon="o-plus" label="Agregar" wire:click="agregarProducto"
                                        class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-bold text-white hover:bg-[#0B6FE4]" />
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                            <x-table :headers="$headers" :rows="$productosUsados"
                                class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:font-bold [&_thead_th]:text-white [&_tbody_tr:hover]:bg-[#F7F9FC]">

                                @scope('cell_cantidad', $row)
                                {{ number_format((float) $row['cantidad'], 2) }}
                                @endscope

                                @scope('cell_precio', $row)
                                C$ {{ number_format((float) $row['precio'], 2) }}
                                @endscope

                                @scope('cell_subtotal', $row)
                                C$ {{ number_format((float) $row['subtotal'], 2) }}
                                @endscope

                                @scope('cell_acciones', $row)
                                @if(!empty($row['ya_guardado']))
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-bold text-[#0B6FE4]">
                                    Guardado
                                </span>
                                @else
                                <x-button icon="o-trash" wire:click="quitarProducto('{{ $row['tmp_id'] }}')"
                                    class="btn-ghost btn-sm text-red-600 hover:bg-red-50" />
                                @endif
                                @endscope
                            </x-table>
                        </div>
                    </x-card>
                </div>
            </div>

            <div class="min-h-0 overflow-y-auto xl:col-span-4">
                <div class="space-y-4">

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Pendientes rápidos</h2>
                                <p class="text-sm text-[#5F6B7A]">Buscá y cargá contratos sin salir de esta pantalla.
                                </p>
                            </div>

                            <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-black text-[#0B6FE4]">
                                {{ count($contratosPendientes) }}
                            </span>
                        </div>

                        <x-input wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                            placeholder="Contrato, cliente, municipio..."
                            class="mb-3 h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                        <div class="space-y-2">
                            @forelse(array_slice($contratosPendientes, 0, 7) as $item)
                            <button type="button" wire:click="cargarPendiente({{ $item['id'] }}, false)"
                                class="w-full rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3 text-left transition hover:border-[#2E8BC0] hover:bg-[#EAF2FB]">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[#1A2B42]">
                                            {{ $item['numero'] }}
                                        </p>

                                        <p class="truncate text-xs font-semibold text-[#5F6B7A]">
                                            {{ $item['cliente'] }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-white px-2 py-1 text-[11px] font-black text-[#0B6FE4] ring-1 ring-[#D7E4F3]">
                                        {{ $this->estadoNombre($item['estado']) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="truncate text-xs text-[#5F6B7A]">{{ $item['ubicacion'] }}</p>
                                    <p class="shrink-0 text-xs font-black text-[#1A2B42]">
                                        C$ {{ number_format((float) $item['saldo'], 2) }}
                                    </p>
                                </div>
                            </button>
                            @empty
                            <div
                                class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-4 py-8 text-center">
                                <p class="text-sm font-bold text-[#1A2B42]">Sin pendientes</p>
                                <p class="text-xs text-[#5F6B7A]">No hay contratos con ese filtro.</p>
                            </div>
                            @endforelse
                        </div>

                        <x-button icon="o-folder-open" label="Abrir listado completo" wire:click="abrirPendientes"
                            class="mt-3 h-10 min-h-10 w-full rounded-xl border border-[#D7E4F3] bg-white text-sm font-bold text-[#1A2B42] hover:bg-[#F7F9FC]" />
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <h2 class="text-lg font-black text-[#1A2B42]">Resumen</h2>
                        <p class="mb-3 text-sm text-[#5F6B7A]">
                            Vista rápida antes de guardar el contrato.
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Estado</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    {{ $this->estadoNombre($estadoContrato) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Cámaras</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    {{ (int) $cantidadCamaras }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Materiales</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalMateriales, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Mano de obra</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format((float) $costoManoObra, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Anticipo</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($anticipo, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Saldo</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($saldo, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl bg-[#2E8BC0] p-4 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide text-white/80">Total general</p>
                            <p class="text-2xl font-black">
                                C$ {{ number_format($totalContrato, 2) }}
                            </p>
                        </div>

                        <x-button icon="o-check"
                            label="{{ $contratoInstalacionIdSeleccionado ? 'Actualizar contrato' : 'Guardar contrato' }}"
                            wire:click="guardar" spinner="guardar"
                            class="mt-3 h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-black text-white hover:bg-[#0B6FE4]" />
                    </x-card>


                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <h2 class="text-lg font-black text-[#1A2B42]">Materiales agregados</h2>
                        <p class="mb-3 text-sm text-[#5F6B7A]">
                            Control rápido de productos cargados al contrato.
                        </p>

                        <div class="space-y-2">
                            @forelse(array_slice($productosUsados, 0, 6) as $item)
                            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[#1A2B42]">
                                            {{ $item['descripcion'] }}
                                        </p>

                                        <p class="truncate text-xs font-semibold text-[#5F6B7A]">
                                            Serie: {{ $item['serie'] }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-white px-2 py-1 text-[11px] font-black text-[#0B6FE4] ring-1 ring-[#D7E4F3]">
                                        x{{ number_format((float) $item['cantidad'], 2) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-xs text-[#5F6B7A]">
                                        C$ {{ number_format((float) $item['precio'], 2) }} c/u
                                    </p>

                                    <p class="shrink-0 text-xs font-black text-[#1A2B42]">
                                        C$ {{ number_format((float) $item['subtotal'], 2) }}
                                    </p>
                                </div>
                            </div>
                            @empty
                            <div
                                class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-4 py-8 text-center">
                                <p class="text-sm font-bold text-[#1A2B42]">Sin materiales</p>
                                <p class="text-xs text-[#5F6B7A]">
                                    Agregá productos para calcular el contrato.
                                </p>
                            </div>
                            @endforelse
                        </div>
                    </x-card>

                </div>
            </div>
        </div>
    </div>


    <x-modal wire:model="modalPendientes" title="Contratos de instalación pendientes" separator class="backdrop-blur">
        <div class="space-y-3">
            <x-input wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                placeholder="Buscar por contrato, cliente, municipio o dirección..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div class="max-h-[60vh] overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Contrato</th>
                            <th class="px-3 py-2 font-bold">Cliente</th>
                            <th class="px-3 py-2 font-bold">Ubicación</th>
                            <th class="px-3 py-2 font-bold">Estado</th>
                            <th class="px-3 py-2 font-bold">Saldo</th>
                            <th class="px-3 py-2 text-center font-bold">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($contratosPendientes as $item)
                        <tr class="hover:bg-[#F7F9FC]">
                            <td class="px-3 py-2 font-black">{{ $item['numero'] }}</td>
                            <td class="px-3 py-2">{{ $item['cliente'] }}</td>
                            <td class="px-3 py-2">{{ $item['ubicacion'] }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-black text-[#0B6FE4]">
                                    {{ $this->estadoNombre($item['estado']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 font-bold">C$ {{ number_format((float) $item['saldo'], 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button icon="o-arrow-down-tray" label="Cargar"
                                    wire:click="cargarPendiente({{ $item['id'] }})"
                                    class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-3 text-xs font-bold text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-[#5F6B7A]">
                                No hay contratos pendientes con ese filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalPendientes', false)"
                class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>

</div>
