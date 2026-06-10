<?php

use App\Models\Banco;
use App\Models\CategoriaProducto;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Models\CuentaBancaria;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast; // MODIFICADO: se usa el trait nativo de MaryUI para mostrar mensajes temporales.

new class extends Component
{
    use WithPagination;
    use Toast; // MODIFICADO: habilita $this->toast(...) para notificaciones temporales.

    public string $buscarProveedor = '';
    public string $proveedorNombre = '';
    public $idProveedor = '';

    public string $numeroCompra = '';
    public string $fechaCompra = '';
    public string $tipoCompra = 'CONTADO';
    public string $fechaLimiteCredito = '';
    public string $medioPago = 'TRANSFERENCIA';
    public string $idCuentaBancaria = '';
    public string $numeroReferenciaTransferencia = '';
    public string $bancoCuentaSeleccionada = '';
    public string $tipoCuentaSeleccionada = '';
    public string $monedaCuentaSeleccionada = '';
    public string $observacion = '';
    public string $retencion = '0';
    public string $iva = '0';

    public string $modoProducto = 'existente';

    public string $buscarProducto = '';
    public array $proveedoresEncontrados = [];
    public array $productosEncontrados = [];

    public $idProducto = '';
    public string $productoNombre = '';
    public string $productoCategoria = '';
    public string $productoMarca = '';
    public string $productoModelo = '';
    public float $precioVentaActual = 0;
    public bool $precioVentaEditable = false;
    public bool $precioVentaEditadoManual = false;

    public string $nuevoNombreProducto = '';
    public string $nuevoModelo = '';
    public string $nuevoCategoriaSeleccionada = '';
    public string $nuevoMarcaSeleccionada = '';
    public string $nuevoStockMinimo = '0';
    public string $nuevoGarantiaNuevo = '';
    public string $nuevoGarantiaUsado = '';
    public string $nuevoEstado = '1';

    public bool $mostrarNuevaCategoria = false;
    public bool $mostrarNuevaMarca = false;
    public string $nombreCategoriaNueva = '';
    public string $nombreMarcaNueva = '';

    public string $cantidad = '1';
    public string $precioCompra = '0';
    public string $precioVenta = '0';
    public string $garantiaProveedor = '';
    public string $seriesTexto = '';

    public bool $editandoDetalle = false;
    public ?int $indiceDetalleEditando = null;

    public array $categorias = [];
    public array $marcas = [];
    public array $categoriasTemporales = [];
    public array $marcasTemporales = [];
    public array $detalles = [];

    public ?int $compraEditandoId = null;
    public string $buscarCompraRealizada = '';
    public bool $mostrarComprasRealizadas = false; //controla si se muestra la lista de compras realizadas en lugar del detalle temporal.

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $tiposCompra = [
        ['id' => 'CONTADO', 'nombre' => 'Contado'],
        ['id' => 'CREDITO', 'nombre' => 'Crédito'],
    ];

    public array $mediosPago = [
        ['id' => 'TRANSFERENCIA', 'nombre' => 'Transferencia'],
        ['id' => 'PAGO_FISICO', 'nombre' => 'Pago físico'],
    ];

    public array $cuentasBancarias = [];

    public bool $mostrarModalCuentaBancaria = false;
    public string $nuevoBancoNombre = '';
    public string $nuevoNombreTitular = '';
    public string $nuevoUltimosDigitos = '';
    public string $nuevoTipoCuenta = 'CUENTA_AHORRO';
    public string $nuevoMonedaCuenta = 'CORDOBAS';

    public array $tiposCuentaBancaria = [
        ['id' => 'CUENTA_AHORRO', 'nombre' => 'Cuenta de ahorro'],
        ['id' => 'CUENTA_CORRIENTE', 'nombre' => 'Cuenta corriente'],
        ['id' => 'TARJETA_DEBITO', 'nombre' => 'Tarjeta de débito'],
        ['id' => 'TARJETA_CREDITO', 'nombre' => 'Tarjeta de crédito'],
    ];

    public array $monedasCuentaBancaria = [
        ['id' => 'CORDOBAS', 'nombre' => 'Córdobas'],
        ['id' => 'DOLARES', 'nombre' => 'Dólares'],
    ];

    public function mount(): void
    {
        $this->fechaCompra = Carbon::today()->toDateString();
        $this->cargarCatalogos();
        $this->cargarCuentasBancarias();
    }

    public function updatedBuscarProveedor(): void
    {
        if ($this->idProveedor !== '') {
            return;
        }

        $this->proveedorNombre = '';
        $this->buscarProveedores();
    }

    public function updatedBuscarProducto(): void
    {
        if ($this->idProducto !== '') {
            return;
        }

        $this->limpiarProductoSeleccionado(false);
        $this->buscarProductos();
    }

    public function updatedPrecioCompra(): void
    {
        $precioCompra = (float) $this->precioCompra;

        if ($this->modoProducto === 'nuevo') {
            $this->precioVentaEditable = true;
            $this->precioVentaEditadoManual = false;
            return;
        }

        if ($this->modoProducto === 'existente' && $this->idProducto !== '') {
            $habilitadoAutomatico = $this->precioVentaActual > 0 && $precioCompra >= $this->precioVentaActual;

            $this->precioVentaEditable = $habilitadoAutomatico || $this->precioVentaEditadoManual;

            if (! $this->precioVentaEditable) {
                $this->precioVenta = (string) $this->precioVentaActual;
            }
        }
    }

    public function habilitarEdicionPrecioVenta(): void
    {
        if ($this->modoProducto !== 'existente' || $this->idProducto === '') {
            return;
        }

        $this->precioVentaEditable = true;
        $this->precioVentaEditadoManual = true;
        $this->resetErrorBag('precioVenta');
        $this->mostrarToast('Precio de venta habilitado para edición manual.');
    }

    public function precioVentaHabilitadoAutomaticamente(): bool
    {
        return $this->modoProducto === 'existente'
            && $this->idProducto !== ''
            && $this->precioVentaActual > 0
            && (float) $this->precioCompra >= $this->precioVentaActual;
    }

    public function updatedTipoCompra(): void
    {
        if ($this->tipoCompra !== 'CREDITO') {
            $this->fechaLimiteCredito = '';
            $this->resetErrorBag('fechaLimiteCredito');
        }
    }

    public function updatedMedioPago(): void
    {
        if ($this->medioPago !== 'TRANSFERENCIA') {
            $this->limpiarCuentaSeleccionada();
            $this->numeroReferenciaTransferencia = '';
            $this->resetErrorBag('idCuentaBancaria');
            $this->resetErrorBag('numeroReferenciaTransferencia');
            return;
        }

        $this->cargarCuentasBancarias();
    }

    public function updatedIdCuentaBancaria(): void
    {
        $this->cargarDatosCuentaSeleccionada();
    }

    public function updatedBuscarCompraRealizada(): void
    {
        $this->resetPage('comprasPage');
    }

    public function verComprasRealizadas(): void
    {
        // permite cambiar la tabla inferior al listado de compras realizadas sin tocar la lógica de guardado.
        $this->mostrarComprasRealizadas = true;
        $this->resetPage('comprasPage');
    }

    public function verDetalleCompra(): void
    {
        // regresa a la tabla normal de productos agregados a la compra actual.
        $this->mostrarComprasRealizadas = false;
    }

    public function comprasRealizadas()
    {
        $busqueda = trim($this->buscarCompraRealizada);
        $compraTable = (new Compra())->getTable();
        $detalleTable = (new DetalleCompra())->getTable();

        $query = Compra::query()
            ->select("{$compraTable}.*")
            ->selectSub(
                DetalleCompra::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn("{$detalleTable}.Id_Compra", "{$compraTable}.Id_Compra"),
                'detalles_count'
            );

        if ($busqueda !== '') {
            $proveedorTable = (new Proveedor())->getTable();

            $query
                ->leftJoin("{$proveedorTable} as proveedor_busqueda", "{$compraTable}.Id_Proveedor", '=', 'proveedor_busqueda.Id_Proveedor')
                ->leftJoin('persona as persona_busqueda', 'proveedor_busqueda.Id_Persona', '=', 'persona_busqueda.Id_Persona')
                ->where(function ($q) use ($busqueda, $compraTable) {
                    $q->where("{$compraTable}.Numero_Compra", 'like', "%{$busqueda}%")
                        ->orWhere("{$compraTable}.Numero_Referencia_Transferencia", 'like', "%{$busqueda}%")
                        ->orWhere('persona_busqueda.Primer_Nombre', 'like', "%{$busqueda}%")
                        ->orWhere('persona_busqueda.Segundo_Nombre', 'like', "%{$busqueda}%")
                        ->orWhere('persona_busqueda.Primer_Apellido', 'like', "%{$busqueda}%")
                        ->orWhere('persona_busqueda.Segundo_Apellido', 'like', "%{$busqueda}%");
                });
        }

        return $query
            ->orderByDesc("{$compraTable}.Fecha_Compra")
            ->orderByDesc("{$compraTable}.Id_Compra")
            ->paginate(5, ['*'], 'comprasPage');
    }

    public function nombreProveedorPorId(?int $idProveedor): string
    {
        if (! $idProveedor) {
            return 'Sin proveedor';
        }

        $proveedor = Proveedor::query()
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Id_Proveedor',
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where('proveedor.Id_Proveedor', $idProveedor)
            ->first();

        if (! $proveedor) {
            return 'Proveedor no encontrado';
        }

        $nombre = trim(
            ($proveedor->Primer_Nombre ?? '') . ' ' .
            ($proveedor->Segundo_Nombre ?? '') . ' ' .
            ($proveedor->Primer_Apellido ?? '') . ' ' .
            ($proveedor->Segundo_Apellido ?? '')
        );

        return $nombre !== '' ? $nombre : ($proveedor->Codigo_RUC ?: 'Proveedor sin nombre');
    }


    protected function cargarCuentasBancarias(): void
    {
        $this->cuentasBancarias = CuentaBancaria::query()
            ->with('banco')
            ->where('Estado', 1)
            ->orderBy('Id_Cuenta_Bancaria', 'desc')
            ->get()
            ->map(fn (CuentaBancaria $cuenta) => [
                'id' => $cuenta->Id_Cuenta_Bancaria,
                'ultimos_digitos' => $this->formatearUltimosDigitos($cuenta->Ultimos_Digitos),
                'banco' => $cuenta->banco?->Nombre_Banco ?: 'Sin banco',
                'titular' => $cuenta->Nombre_Titular ?: 'Sin titular',
                'tipo' => $this->nombreTipoCuenta($cuenta->Tipo_Cuenta),
                'moneda' => $this->nombreMonedaCuenta($cuenta->Moneda),
            ])
            ->toArray();
    }

    protected function formatearUltimosDigitos(?string $ultimosDigitos): string
    {
        $limpio = preg_replace('/\D+/', '', (string) $ultimosDigitos);

        return $limpio !== '' ? str_pad(substr($limpio, -4), 4, '0', STR_PAD_LEFT) : '----';
    }

    protected function normalizarUltimosDigitos(string $numeroCuenta): string
    {
        $limpio = preg_replace('/\D+/', '', $numeroCuenta);

        return substr($limpio, -4) ?: '';
    }

    protected function nombreTipoCuenta(?string $tipo): string
    {
        return collect($this->tiposCuentaBancaria)->firstWhere('id', $tipo)['nombre'] ?? 'No definido';
    }

    protected function nombreMonedaCuenta(?string $moneda): string
    {
        return collect($this->monedasCuentaBancaria)->firstWhere('id', $moneda)['nombre'] ?? 'No definida';
    }

    protected function limpiarCuentaSeleccionada(): void
    {
        $this->idCuentaBancaria = '';
        $this->bancoCuentaSeleccionada = '';
        $this->tipoCuentaSeleccionada = '';
        $this->monedaCuentaSeleccionada = '';
    }

    protected function cargarDatosCuentaSeleccionada(): void
    {
        if ($this->idCuentaBancaria === '') {
            $this->bancoCuentaSeleccionada = '';
            $this->tipoCuentaSeleccionada = '';
            $this->monedaCuentaSeleccionada = '';
            return;
        }

        $cuenta = CuentaBancaria::query()
            ->with('banco')
            ->where('Estado', 1)
            ->find((int) $this->idCuentaBancaria);

        if (! $cuenta) {
            $this->limpiarCuentaSeleccionada();
            $this->addError('idCuentaBancaria', 'La cuenta bancaria seleccionada no existe o está inactiva.');
            return;
        }

        $this->bancoCuentaSeleccionada = $cuenta->banco?->Nombre_Banco ?: 'Sin banco';
        $this->tipoCuentaSeleccionada = $this->nombreTipoCuenta($cuenta->Tipo_Cuenta);
        $this->monedaCuentaSeleccionada = $this->nombreMonedaCuenta($cuenta->Moneda);
        $this->resetErrorBag('idCuentaBancaria');
    }

    public function abrirModalCuentaBancaria(): void
    {
        $this->resetErrorBag();
        $this->limpiarFormularioCuentaBancaria();
        $this->mostrarModalCuentaBancaria = true;
    }

    public function cerrarModalCuentaBancaria(): void
    {
        $this->mostrarModalCuentaBancaria = false;
        $this->limpiarFormularioCuentaBancaria();
        $this->resetErrorBag();
    }

    protected function limpiarFormularioCuentaBancaria(): void
    {
        $this->nuevoBancoNombre = '';
        $this->nuevoNombreTitular = '';
        $this->nuevoUltimosDigitos = '';
        $this->nuevoTipoCuenta = 'CUENTA_AHORRO';
        $this->nuevoMonedaCuenta = 'CORDOBAS';
    }

    public function guardarCuentaBancaria(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nuevoBancoNombre' => 'required|string|max:100',
            'nuevoNombreTitular' => 'required|string|max:150',
            'nuevoUltimosDigitos' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $limpio = preg_replace('/\D+/', '', (string) $value);

                    if (strlen($limpio) < 4) {
                        $fail('Ingrese al menos 4 dígitos de la cuenta bancaria.');
                    }
                },
            ],
            'nuevoTipoCuenta' => 'required|in:CUENTA_AHORRO,CUENTA_CORRIENTE,TARJETA_DEBITO,TARJETA_CREDITO',
            'nuevoMonedaCuenta' => 'required|in:CORDOBAS,DOLARES',
        ], [
            'nuevoBancoNombre.required' => 'Ingrese el nombre del banco.',
            'nuevoNombreTitular.required' => 'Ingrese el nombre del titular de la cuenta.',
            'nuevoUltimosDigitos.required' => 'Ingrese el número de cuenta o al menos los últimos 4 dígitos.',
        ]);

        $ultimosDigitos = $this->normalizarUltimosDigitos($this->nuevoUltimosDigitos);

        $cuenta = CuentaBancaria::query()->getConnection()->transaction(function () use ($ultimosDigitos) {
            $nombreBanco = trim($this->nuevoBancoNombre);
            $nombreTitular = trim($this->nuevoNombreTitular);

            $banco = Banco::query()
                ->whereRaw('LOWER(Nombre_Banco) = ?', [mb_strtolower($nombreBanco)])
                ->first();

            if (! $banco) {
                $banco = Banco::query()->create([
                    'Nombre_Banco' => $nombreBanco,
                    'Estado' => 1,
                ]);
            } elseif (! $banco->Estado) {
                $banco->Estado = 1;
                $banco->save();
            }

            $cuentaDuplicada = CuentaBancaria::query()
                ->where('Estado', 1)
                ->where('Id_Banco', $banco->Id_Banco)
                ->where('Nombre_Titular', $nombreTitular)
                ->where('Ultimos_Digitos', $ultimosDigitos)
                ->where('Tipo_Cuenta', $this->nuevoTipoCuenta)
                ->where('Moneda', $this->nuevoMonedaCuenta)
                ->exists();

            if ($cuentaDuplicada) {
                return null;
            }

            return CuentaBancaria::query()->create([
                'Id_Banco' => $banco->Id_Banco,
                'Nombre_Titular' => $nombreTitular,
                'Ultimos_Digitos' => $ultimosDigitos,
                'Tipo_Cuenta' => $this->nuevoTipoCuenta,
                'Moneda' => $this->nuevoMonedaCuenta,
                'Estado' => 1,
            ]);
        });

        if (! $cuenta) {
            $this->addError('nuevoUltimosDigitos', 'Ya existe una cuenta bancaria activa con esos últimos dígitos para ese banco, titular, tipo y moneda.');
            return;
        }

        $this->cargarCuentasBancarias();
        $this->idCuentaBancaria = (string) $cuenta->Id_Cuenta_Bancaria;
        $this->cargarDatosCuentaSeleccionada();
        $this->mostrarModalCuentaBancaria = false;
        $this->limpiarFormularioCuentaBancaria();
        $this->mostrarToast('Cuenta bancaria agregada correctamente.');
    }

    public function cambiarModoProducto(string $modo): void
    {
        if ($this->editandoDetalle) {
            $this->mostrarToast('Termine o cancele la edición antes de cambiar el tipo de producto.', 'error');
            return;
        }

        if (! in_array($modo, ['existente', 'nuevo'], true)) {
            return;
        }

        $this->modoProducto = $modo;
        $this->limpiarDetalleProducto();
        $this->precioVentaEditable = $modo === 'nuevo';
        $this->precioVentaEditadoManual = false;
    }

    public function desbloquearProveedor(): void
    {
        $this->idProveedor = '';
        $this->proveedorNombre = '';
        $this->buscarProveedor = '';
        $this->proveedoresEncontrados = [];

        $this->resetErrorBag('idProveedor');
    }

    public function desbloquearProducto(): void
    {
        $this->limpiarProductoSeleccionado();
        $this->resetErrorBag('idProducto');
    }

    protected function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        //los mensajes ya no quedan fijos; ahora usan el toast temporal
        $this->toast(
            type: $tipo,
            title: $mensaje,
            position: 'toast-top toast-end',
            icon: $tipo === 'success' ? 'o-check-circle' : 'o-x-circle',
            css: $tipo === 'success' ? 'alert-success' : 'alert-error',
            timeout: 3000
        );
    }

    public function cerrarToast(): void
    {
        // se conserva por compatibilidad, aunque el cierre ahora lo maneja MaryUI automáticamente.
        $this->toastMensaje = '';
        $this->toastTipo = 'success';
        $this->mostrarToast = false;
    }

    protected function cargarCatalogos(): void
    {
        $categoriasDb = CategoriaProducto::query()
            ->orderBy('Nombre_Categoria')
            ->get(['Id_Categoria', 'Nombre_Categoria'])
            ->map(fn (CategoriaProducto $categoria) => [
                'valor' => 'db:' . $categoria->Id_Categoria,
                'id' => $categoria->Id_Categoria,
                'nombre' => $categoria->Nombre_Categoria,
                'temporal' => false,
            ])
            ->toArray();

        $marcasDb = Marca::query()
            ->orderBy('Nombre_Marca')
            ->get(['Id_Marca', 'Nombre_Marca'])
            ->map(fn (Marca $marca) => [
                'valor' => 'db:' . $marca->Id_Marca,
                'id' => $marca->Id_Marca,
                'nombre' => $marca->Nombre_Marca,
                'temporal' => false,
            ])
            ->toArray();

        $this->categorias = array_merge($categoriasDb, $this->categoriasTemporales);
        $this->marcas = array_merge($marcasDb, $this->marcasTemporales);
    }

    protected function buscarProveedores(): void
    {
        $busqueda = trim($this->buscarProveedor);

        if ($busqueda === '') {
            $this->proveedoresEncontrados = [];
            return;
        }

        $this->proveedoresEncontrados = Proveedor::query()
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Id_Proveedor',
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where(function ($q) use ($busqueda) {
                $q->where('proveedor.Codigo_RUC', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Primer_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Segundo_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Primer_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Segundo_Apellido', 'like', "%{$busqueda}%");
            })
            ->orderBy('pe.Primer_Nombre')
            ->limit(8)
            ->get()
            ->map(function ($proveedor) {
                $nombre = trim(
                    ($proveedor->Primer_Nombre ?? '') . ' ' .
                    ($proveedor->Segundo_Nombre ?? '') . ' ' .
                    ($proveedor->Primer_Apellido ?? '') . ' ' .
                    ($proveedor->Segundo_Apellido ?? '')
                );

                return [
                    'id' => $proveedor->Id_Proveedor,
                    'nombre' => $nombre !== '' ? $nombre : 'Proveedor sin nombre',
                    'ruc' => $proveedor->Codigo_RUC ?: 'Sin RUC',
                ];
            })
            ->toArray();
    }

    public function seleccionarProveedor(int $idProveedor): void
    {
        $proveedor = Proveedor::query()
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Id_Proveedor',
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where('proveedor.Id_Proveedor', $idProveedor)
            ->first();

        if (! $proveedor) {
            $this->mostrarToast('No se encontró el proveedor seleccionado.', 'error');
            return;
        }

        $nombre = trim(
            ($proveedor->Primer_Nombre ?? '') . ' ' .
            ($proveedor->Segundo_Nombre ?? '') . ' ' .
            ($proveedor->Primer_Apellido ?? '') . ' ' .
            ($proveedor->Segundo_Apellido ?? '')
        );

        $this->idProveedor = $proveedor->Id_Proveedor;
        $this->proveedorNombre = $nombre !== '' ? $nombre : 'Proveedor sin nombre';
        $this->buscarProveedor = $this->proveedorNombre . ' - ' . ($proveedor->Codigo_RUC ?: 'Sin RUC');
        $this->proveedoresEncontrados = [];

        $this->resetErrorBag('idProveedor');
    }

    protected function buscarProductos(): void
    {
        $busqueda = trim($this->buscarProducto);

        if ($busqueda === '') {
            $this->productosEncontrados = [];
            return;
        }

        $this->productosEncontrados = Producto::query()
            ->with(['categoria', 'marca'])
            ->where(function ($q) use ($busqueda) {
                $q->where('Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('Modelo', 'like', "%{$busqueda}%")
                    ->orWhereHas('categoria', function ($categoria) use ($busqueda) {
                        $categoria->where('Nombre_Categoria', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('marca', function ($marca) use ($busqueda) {
                        $marca->where('Nombre_Marca', 'like', "%{$busqueda}%");
                    });
            })
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->Id_Producto,
                'nombre' => $producto->Nombre_Producto,
                'modelo' => $producto->Modelo ?: 'Sin modelo',
                'categoria' => $producto->categoria?->Nombre_Categoria ?: 'Sin categoría',
                'marca' => $producto->marca?->Nombre_Marca ?: 'Sin marca',
                'precio_venta' => (float) $producto->Precio_Venta,
                'stock' => (int) $producto->Stock_Actual,
            ])
            ->toArray();
    }

    public function seleccionarProducto(int $idProducto): void
    {
        $producto = Producto::query()
            ->with(['categoria', 'marca'])
            ->find($idProducto);

        if (! $producto) {
            $this->mostrarToast('No se encontró el producto seleccionado.', 'error');
            return;
        }

        $this->idProducto = $producto->Id_Producto;
        $this->productoNombre = $producto->Nombre_Producto;
        $this->productoModelo = $producto->Modelo ?: 'Sin modelo';
        $this->productoCategoria = $producto->categoria?->Nombre_Categoria ?: 'Sin categoría';
        $this->productoMarca = $producto->marca?->Nombre_Marca ?: 'Sin marca';

        $this->precioVentaActual = (float) $producto->Precio_Venta;
        $this->precioVenta = (string) $this->precioVentaActual;
        $this->precioVentaEditadoManual = false;
        $this->precioVentaEditable = $this->precioVentaHabilitadoAutomaticamente();

        $this->buscarProducto = trim(
            $this->productoMarca . ' ' .
            $this->productoNombre . ' ' .
            $this->productoModelo
        );

        $this->productosEncontrados = [];

        $this->resetErrorBag('idProducto');
    }

    public function agregarCategoriaTemporal(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreCategoriaNueva' => 'required|string|max:100',
        ]);

        $nombre = trim($this->nombreCategoriaNueva);

        $existeDb = CategoriaProducto::query()
            ->whereRaw('LOWER(Nombre_Categoria) = ?', [mb_strtolower($nombre)])
            ->exists();

        $existeTemporal = collect($this->categoriasTemporales)
            ->contains(fn ($categoria) => mb_strtolower($categoria['nombre']) === mb_strtolower($nombre));

        if ($existeDb || $existeTemporal) {
            $this->addError('nombreCategoriaNueva', 'Esta categoría ya existe.');
            return;
        }

        $uid = uniqid('cat_', true);

        $this->categoriasTemporales[] = [
            'valor' => 'tmp:' . $uid,
            'id' => null,
            'nombre' => $nombre,
            'temporal' => true,
        ];

        $this->nuevoCategoriaSeleccionada = 'tmp:' . $uid;
        $this->nombreCategoriaNueva = '';
        $this->mostrarNuevaCategoria = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Categoría agregada. Se guardará al finalizar la compra.');
    }

    public function agregarMarcaTemporal(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreMarcaNueva' => 'required|string|max:100',
        ]);

        $nombre = trim($this->nombreMarcaNueva);

        $existeDb = Marca::query()
            ->whereRaw('LOWER(Nombre_Marca) = ?', [mb_strtolower($nombre)])
            ->exists();

        $existeTemporal = collect($this->marcasTemporales)
            ->contains(fn ($marca) => mb_strtolower($marca['nombre']) === mb_strtolower($nombre));

        if ($existeDb || $existeTemporal) {
            $this->addError('nombreMarcaNueva', 'Esta marca ya existe.');
            return;
        }

        $uid = uniqid('mar_', true);

        $this->marcasTemporales[] = [
            'valor' => 'tmp:' . $uid,
            'id' => null,
            'nombre' => $nombre,
            'temporal' => true,
        ];

        $this->nuevoMarcaSeleccionada = 'tmp:' . $uid;
        $this->nombreMarcaNueva = '';
        $this->mostrarNuevaMarca = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Marca agregada. Se guardará al finalizar la compra.');
    }

    protected function existeEnCatalogo(string $valor, array $catalogo): bool
    {
        return collect($catalogo)->contains(fn ($item) => $item['valor'] === $valor);
    }

    protected function nombreCatalogo(string $valor, array $catalogo, string $default): string
    {
        $item = collect($catalogo)->firstWhere('valor', $valor);

        return $item['nombre'] ?? $default;
    }

    protected function obtenerSeries(): array
    {
        $texto = trim($this->seriesTexto);

        if ($texto === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,;]+/', $texto))
            ->map(fn ($serie) => trim($serie))
            ->filter()
            ->values()
            ->toArray();
    }

    protected function validarSeries(array $series, int $cantidad, ?int $indiceIgnorado = null): bool
    {
        if (count($series) === 0) {
            return true;
        }

        if (count($series) !== $cantidad) {
            $this->addError(
                'seriesTexto',
                'Si ingresa números de serie, debe ingresar exactamente ' . $cantidad . ' número(s) de serie, uno por cada producto comprado.'
            );

            return false;
        }

        if (count($series) !== count(array_unique($series))) {
            $this->addError('seriesTexto', 'Hay números de serie repetidos en este detalle.');
            return false;
        }

        foreach ($series as $serie) {
            if (strlen($serie) > 100) {
                $this->addError('seriesTexto', 'Cada número de serie debe tener máximo 100 caracteres.');
                return false;
            }
        }

        $seriesTemporales = collect($this->detalles)
            ->reject(fn ($detalle, $indice) => $indiceIgnorado !== null && $indice === $indiceIgnorado)
            ->flatMap(fn ($detalle) => $detalle['series'])
            ->toArray();

        foreach ($series as $serie) {
            if (in_array($serie, $seriesTemporales, true)) {
                $this->addError('seriesTexto', "El número de serie {$serie} ya está agregado temporalmente.");
                return false;
            }
        }

        $seriesExistentes = ProductoSerie::query()
            ->whereIn('Numero_Serie', $series)
            ->pluck('Numero_Serie')
            ->toArray();

        if (count($seriesExistentes) > 0) {
            $this->addError('seriesTexto', 'Ya existe este número de serie: ' . implode(', ', $seriesExistentes));
            return false;
        }

        return true;
    }

    protected function construirDetalleDesdeFormulario(): ?array
    {
        $reglasBase = [
            'cantidad' => 'required|integer|min:1',
            'precioCompra' => 'required|numeric|min:0.01',
            'precioVenta' => 'required|numeric|min:0.01',
            'garantiaProveedor' => 'nullable|integer|min:0|max:240',
            'seriesTexto' => 'nullable|string|max:1000',
        ];

        $mensajesDetalle = [
            'cantidad.required' => 'Ingrese la cantidad comprada.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'precioCompra.required' => 'Ingrese el precio de compra.',
            'precioCompra.numeric' => 'El precio de compra debe ser numérico.',
            'precioCompra.min' => 'El precio de compra debe ser mayor a 0.',
            'precioVenta.required' => 'Ingrese el precio de venta.',
            'precioVenta.numeric' => 'El precio de venta debe ser numérico.',
            'precioVenta.min' => 'El precio de venta debe ser mayor a 0.',
            'garantiaProveedor.integer' => 'La garantía del proveedor debe ingresarse en meses completos.',
            'garantiaProveedor.min' => 'La garantía del proveedor no puede ser negativa.',
            'garantiaProveedor.max' => 'La garantía del proveedor no puede superar 240 meses.',
            'seriesTexto.max' => 'El listado de series es demasiado largo.',
        ];

        if ($this->modoProducto === 'existente') {
            if ($this->idProducto === '') {
                $this->addError('idProducto', 'Seleccione un producto existente de la lista. Si no aparece, use “Producto nuevo”.');
                return null;
            }

            $this->validate(array_merge($reglasBase, [
                'idProducto' => 'required|exists:producto,Id_Producto',
            ]), $mensajesDetalle);

            if ($this->precioVentaEditable && (float) $this->precioVenta < (float) $this->precioCompra) {
                $this->addError('precioVenta', 'El precio de venta debe ser mayor o igual al precio de compra.');
                return null;
            }

            $productoId = (int) $this->idProducto;
            $nombreProducto = $this->productoNombre;
            $modelo = $this->productoModelo;
            $categoriaValor = null;
            $categoriaNombre = $this->productoCategoria;
            $marcaValor = null;
            $marcaNombre = $this->productoMarca;
            $stockMinimo = null;
            $garantiaNuevo = null;
            $garantiaUsado = null;
            $estado = 1;

            $series = $this->obtenerSeries();

            if (! $this->validarSeries($series, (int) $this->cantidad, $this->indiceDetalleEditando)) {
                return null;
            }
        } else {
            $this->validate(array_merge($reglasBase, [
                'nuevoNombreProducto' => 'required|string|max:150',
                'nuevoModelo' => 'required|string|max:100',
                'nuevoCategoriaSeleccionada' => 'required|string',
                'nuevoMarcaSeleccionada' => 'required|string',
                'nuevoStockMinimo' => 'required|integer|min:0',
                'nuevoGarantiaNuevo' => 'nullable|integer|min:0',
                'nuevoGarantiaUsado' => 'nullable|integer|min:0',
                'nuevoEstado' => 'required|in:0,1',
            ]), $mensajesDetalle);

            if (! $this->existeEnCatalogo($this->nuevoCategoriaSeleccionada, $this->categorias)) {
                $this->addError('nuevoCategoriaSeleccionada', 'Seleccione una categoría válida.');
                return null;
            }

            if (! $this->existeEnCatalogo($this->nuevoMarcaSeleccionada, $this->marcas)) {
                $this->addError('nuevoMarcaSeleccionada', 'Seleccione una marca válida.');
                return null;
            }

            if ((float) $this->precioVenta < (float) $this->precioCompra) {
                $this->addError('precioVenta', 'El precio de venta debe ser mayor o igual al precio de compra.');
                return null;
            }

            $productoId = null;
            $nombreProducto = trim($this->nuevoNombreProducto);
            $modelo = trim($this->nuevoModelo);
            $categoriaValor = $this->nuevoCategoriaSeleccionada;
            $categoriaNombre = $this->nombreCatalogo($this->nuevoCategoriaSeleccionada, $this->categorias, 'Sin categoría');

            $marcaValor = $this->nuevoMarcaSeleccionada;
            $marcaNombre = $this->nombreCatalogo($this->nuevoMarcaSeleccionada, $this->marcas, 'Sin marca');

            $stockMinimo = (int) $this->nuevoStockMinimo;
            $garantiaNuevo = $this->nuevoGarantiaNuevo !== '' ? (int) $this->nuevoGarantiaNuevo : null;
            $garantiaUsado = $this->nuevoGarantiaUsado !== '' ? (int) $this->nuevoGarantiaUsado : null;
            $estado = (int) $this->nuevoEstado;

            $series = $this->obtenerSeries();

            if (! $this->validarSeries($series, (int) $this->cantidad, $this->indiceDetalleEditando)) {
                return null;
            }
        }

        $cantidad = (int) $this->cantidad;
        $precioCompra = (float) $this->precioCompra;
        $precioVenta = (float) $this->precioVenta;
        $subtotal = $cantidad * $precioCompra;
        $garantiaProveedor = $this->garantiaProveedor !== '' ? (int) $this->garantiaProveedor : null;

        $uid = uniqid('detalle_', true);

        if ($this->editandoDetalle && $this->indiceDetalleEditando !== null && isset($this->detalles[$this->indiceDetalleEditando]['uid'])) {
            $uid = $this->detalles[$this->indiceDetalleEditando]['uid'];
        }

        return [
            'uid' => $uid,
            'modo' => $this->modoProducto,
            'producto_id' => $productoId,
            'nombre_producto' => $nombreProducto,
            'modelo' => $modelo,
            'categoria_valor' => $categoriaValor,
            'categoria' => $categoriaNombre,
            'marca_valor' => $marcaValor,
            'marca' => $marcaNombre,
            'stock_minimo' => $stockMinimo,
            'garantia_nuevo' => $garantiaNuevo,
            'garantia_usado' => $garantiaUsado,
            'estado' => $estado,
            'cantidad' => $cantidad,
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,
            'garantia_proveedor' => $garantiaProveedor,
            'subtotal' => $subtotal,
            'series' => $series,
            'actualizar_precio_venta' => $this->modoProducto === 'nuevo' || ($this->precioVentaEditable && abs($precioVenta - $this->precioVentaActual) > 0.009),
        ];
    }

    public function agregarDetalle(): void
    {
        $this->resetErrorBag();

        $detalle = $this->construirDetalleDesdeFormulario();

        if ($detalle === null) {
            return;
        }

        if ($this->editandoDetalle && $this->indiceDetalleEditando !== null) {
            if (! isset($this->detalles[$this->indiceDetalleEditando])) {
                $this->mostrarToast('No se encontró el detalle que intenta editar.', 'error');
                $this->limpiarDetalleProducto();
                return;
            }

            $this->detalles[$this->indiceDetalleEditando] = $detalle;
            $this->detalles = array_values($this->detalles);

            $this->limpiarDetalleProducto();
            $this->mostrarToast('Detalle actualizado correctamente.');

            return;
        }

        $this->detalles[] = $detalle;

        $this->limpiarDetalleProducto();
        $this->mostrarToast('Producto agregado a la compra.');
    }

    public function editarDetalle(int $indice): void
    {
        if (! isset($this->detalles[$indice])) {
            $this->mostrarToast('No se encontró el detalle seleccionado.', 'error');
            return;
        }

        $detalle = $this->detalles[$indice];

        $this->resetErrorBag();
        $this->resetValidation();

        $this->editandoDetalle = true;
        $this->indiceDetalleEditando = $indice;
        $this->modoProducto = $detalle['modo'];

        $this->productosEncontrados = [];

        if ($detalle['modo'] === 'existente') {
            $producto = Producto::query()
                ->with(['categoria', 'marca'])
                ->find($detalle['producto_id']);

            $this->idProducto = $detalle['producto_id'];
            $this->productoNombre = $producto?->Nombre_Producto ?? $detalle['nombre_producto'];
            $this->productoModelo = $producto?->Modelo ?: $detalle['modelo'];
            $this->productoCategoria = $producto?->categoria?->Nombre_Categoria ?: $detalle['categoria'];
            $this->productoMarca = $producto?->marca?->Nombre_Marca ?: $detalle['marca'];

            $this->precioVentaActual = $producto ? (float) $producto->Precio_Venta : (float) $detalle['precio_venta'];
            $this->precioVenta = (string) $detalle['precio_venta'];
            $this->precioCompra = (string) $detalle['precio_compra'];

            $habilitadoAutomatico = $this->precioVentaHabilitadoAutomaticamente();
            $precioCambiado = abs((float) $this->precioVenta - $this->precioVentaActual) > 0.009;

            $this->precioVentaEditadoManual = $precioCambiado && ! $habilitadoAutomatico;
            $this->precioVentaEditable = $habilitadoAutomatico || $this->precioVentaEditadoManual;

            $this->buscarProducto = trim(
                $this->productoMarca . ' ' .
                $this->productoNombre . ' ' .
                $this->productoModelo
            );

            $this->nuevoNombreProducto = '';
            $this->nuevoModelo = '';
            $this->nuevoCategoriaSeleccionada = '';
            $this->nuevoMarcaSeleccionada = '';
            $this->nuevoStockMinimo = '0';
            $this->nuevoGarantiaNuevo = '';
            $this->nuevoGarantiaUsado = '';
            $this->nuevoEstado = '1';
        } else {
            $this->idProducto = '';
            $this->buscarProducto = '';
            $this->productoNombre = '';
            $this->productoCategoria = '';
            $this->productoMarca = '';
            $this->productoModelo = '';
            $this->precioVentaActual = 0;
            $this->precioVentaEditable = true;
            $this->precioVentaEditadoManual = false;

            $this->nuevoNombreProducto = $detalle['nombre_producto'];
            $this->nuevoModelo = $detalle['modelo'];
            $this->nuevoCategoriaSeleccionada = $detalle['categoria_valor'] ?? '';
            $this->nuevoMarcaSeleccionada = $detalle['marca_valor'] ?? '';
            $this->nuevoStockMinimo = (string) ($detalle['stock_minimo'] ?? '0');
            $this->nuevoGarantiaNuevo = $detalle['garantia_nuevo'] !== null ? (string) $detalle['garantia_nuevo'] : '';
            $this->nuevoGarantiaUsado = $detalle['garantia_usado'] !== null ? (string) $detalle['garantia_usado'] : '';
            $this->nuevoEstado = (string) ($detalle['estado'] ?? '1');

            $this->precioCompra = (string) $detalle['precio_compra'];
            $this->precioVenta = (string) $detalle['precio_venta'];
        }

        $this->cantidad = (string) $detalle['cantidad'];
        $this->garantiaProveedor = $detalle['garantia_proveedor'] !== null ? (string) $detalle['garantia_proveedor'] : '';
        $this->seriesTexto = implode(PHP_EOL, $detalle['series'] ?? []);

        $this->mostrarToast('Detalle cargado para edición.');
    }

    public function cancelarEdicionDetalle(): void
    {
        $this->limpiarDetalleProducto();
        $this->mostrarToast('Edición cancelada.');
    }

    public function quitarDetalle(int $indice): void
    {
        if (! isset($this->detalles[$indice])) {
            return;
        }

        $quitandoDetalleEditado = $this->editandoDetalle && $this->indiceDetalleEditando === $indice;

        unset($this->detalles[$indice]);
        $this->detalles = array_values($this->detalles);

        if ($quitandoDetalleEditado) {
            $this->limpiarDetalleProducto();
        } elseif ($this->editandoDetalle && $this->indiceDetalleEditando !== null && $this->indiceDetalleEditando > $indice) {
            $this->indiceDetalleEditando--;
        }

        $this->mostrarToast('Producto quitado de la compra.');
    }

    protected function resolverCategoriaId(string $valor, array &$categoriasCreadas): int
    {
        if (str_starts_with($valor, 'db:')) {
            return (int) str_replace('db:', '', $valor);
        }

        if (! isset($categoriasCreadas[$valor])) {
            $categoriaTemporal = collect($this->categoriasTemporales)->firstWhere('valor', $valor);

            if (! $categoriaTemporal) {
                throw new \RuntimeException('No se encontró la categoría temporal.');
            }

            $categoria = CategoriaProducto::query()->create([
                'Nombre_Categoria' => $categoriaTemporal['nombre'],
            ]);

            $categoriasCreadas[$valor] = $categoria->Id_Categoria;
        }

        return (int) $categoriasCreadas[$valor];
    }

    protected function resolverMarcaId(?string $valor, array &$marcasCreadas): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (str_starts_with($valor, 'db:')) {
            return (int) str_replace('db:', '', $valor);
        }

        if (! isset($marcasCreadas[$valor])) {
            $marcaTemporal = collect($this->marcasTemporales)->firstWhere('valor', $valor);

            if (! $marcaTemporal) {
                throw new \RuntimeException('No se encontró la marca temporal.');
            }

            $marca = Marca::query()->create([
                'Nombre_Marca' => $marcaTemporal['nombre'],
                'Estado' => 1,
            ]);

            $marcasCreadas[$valor] = $marca->Id_Marca;
        }

        return (int) $marcasCreadas[$valor];
    }

    public function editarCompra(int $idCompra): void
    {
        if ($this->editandoDetalle) {
            $this->mostrarToast('Termine o cancele la edición del detalle antes de cargar otra compra.', 'error');
            return;
        }

        $compra = Compra::query()->find($idCompra);

        if (! $compra) {
            $this->mostrarToast('No se encontró la compra seleccionada.', 'error');
            return;
        }

        $this->cancelarCompra(false);
        $this->resetErrorBag();
        $this->resetValidation();

        $this->compraEditandoId = (int) $compra->Id_Compra;
        $this->mostrarComprasRealizadas = false; // al editar una compra se vuelve a mostrar el detalle normal para trabajarla.
        $this->seleccionarProveedor((int) $compra->Id_Proveedor);

        $this->numeroCompra = (string) $compra->Numero_Compra;
        $this->fechaCompra = Carbon::parse($compra->Fecha_Compra)->toDateString();
        $this->tipoCompra = (string) $compra->Tipo_Compra;
        $this->fechaLimiteCredito = $compra->Fecha_Limite_Credito
            ? Carbon::parse($compra->Fecha_Limite_Credito)->toDateString()
            : '';
        $this->medioPago = (string) $compra->Medio_Pago;
        $this->idCuentaBancaria = $compra->Id_Cuenta_Bancaria ? (string) $compra->Id_Cuenta_Bancaria : '';
        $this->numeroReferenciaTransferencia = (string) ($compra->Numero_Referencia_Transferencia ?? '');
        $this->observacion = (string) ($compra->Observacion ?? '');
        $this->retencion = (string) ((float) ($compra->Retencion ?? 0));
        $this->iva = (string) ((float) ($compra->Iva ?? 0));
        $this->cargarDatosCuentaSeleccionada();

        $detallesDb = DetalleCompra::query()
            ->where('Id_Compra', $compra->Id_Compra)
            ->get();

        foreach ($detallesDb as $indice => $detalleDb) {
            $producto = Producto::query()
                ->with(['categoria', 'marca'])
                ->find($detalleDb->Id_Producto);

            if (! $producto) {
                $this->cancelarCompra(false);
                $this->mostrarToast('No se puede editar esta compra porque uno de sus productos ya no existe.', 'error');
                return;
            }

            $this->detalles[] = [
                'uid' => 'compra_' . $compra->Id_Compra . '_detalle_' . $indice,
                'modo' => 'existente',
                'producto_id' => (int) $producto->Id_Producto,
                'nombre_producto' => $producto->Nombre_Producto,
                'modelo' => $producto->Modelo ?: 'Sin modelo',
                'categoria_valor' => null,
                'categoria' => $producto->categoria?->Nombre_Categoria ?: 'Sin categoría',
                'marca_valor' => null,
                'marca' => $producto->marca?->Nombre_Marca ?: 'Sin marca',
                'stock_minimo' => null,
                'garantia_nuevo' => null,
                'garantia_usado' => null,
                'estado' => 1,
                'cantidad' => (int) $detalleDb->Cantidad,
                'precio_compra' => (float) $detalleDb->Precio_Compra,
                'precio_venta' => (float) $producto->Precio_Venta,
                'garantia_proveedor' => $detalleDb->Meses_Garantia_Proveedor !== null
                    ? (int) $detalleDb->Meses_Garantia_Proveedor
                    : null,
                'subtotal' => (float) $detalleDb->Subtotal,
                'series' => [],
                'actualizar_precio_venta' => false,
            ];
        }

        $this->mostrarToast('Compra cargada para edición.');
    }

    protected function datosEncabezadoCompra(int $idUsuario): array
    {
        return [
            'Numero_Compra' => trim($this->numeroCompra),
            'Fecha_Compra' => Carbon::parse($this->fechaCompra)->startOfDay(),
            'Id_Proveedor' => (int) $this->idProveedor,
            'Id_Usuario' => (int) $idUsuario,
            'Tipo_Compra' => $this->tipoCompra,
            'Fecha_Limite_Credito' => $this->tipoCompra === 'CREDITO'
                ? Carbon::parse($this->fechaLimiteCredito)->toDateString()
                : null,
            'Medio_Pago' => $this->medioPago,
            'Id_Cuenta_Bancaria' => $this->medioPago === 'TRANSFERENCIA'
                ? (int) $this->idCuentaBancaria
                : null,
            'Numero_Referencia_Transferencia' => $this->medioPago === 'TRANSFERENCIA'
                ? trim($this->numeroReferenciaTransferencia)
                : null,
            'Total' => $this->totalGeneral(),
            'Observacion' => trim($this->observacion) !== '' ? trim($this->observacion) : null,
            'Retencion' => (float) $this->retencion,
            'Iva' => (float) $this->iva,
            'Id_producto' => null,
        ];
    }

    protected function guardarDetallesEnCompra(Compra $compra, array &$categoriasCreadas, array &$marcasCreadas, array &$cantidadesNuevas, ?int &$primerProductoId): void
    {
        foreach ($this->detalles as $detalle) {
            if ($detalle['modo'] === 'nuevo') {
                $categoriaId = $this->resolverCategoriaId($detalle['categoria_valor'], $categoriasCreadas);
                $marcaId = $this->resolverMarcaId($detalle['marca_valor'], $marcasCreadas);

                $producto = Producto::query()->create([
                    'Id_Categoria' => $categoriaId,
                    'Id_Marca' => $marcaId,
                    'Nombre_Producto' => $detalle['nombre_producto'],
                    'Modelo' => $detalle['modelo'],
                    'Stock_Actual' => 0,
                    'Stock_Minimo' => $detalle['stock_minimo'],
                    'Precio_Venta' => $detalle['precio_venta'],
                    'Fecha_Vencimiento' => now(),
                    'Meses_Garantia_Nuevo' => $detalle['garantia_nuevo'],
                    'Meses_Garantia_Usado' => $detalle['garantia_usado'],
                    'Estado' => $detalle['estado'],
                ]);
            } else {
                $producto = Producto::query()->findOrFail((int) $detalle['producto_id']);

                if ($detalle['actualizar_precio_venta']) {
                    $producto->Precio_Venta = $detalle['precio_venta'];
                    $producto->save();
                }
            }

            if ($primerProductoId === null) {
                $primerProductoId = $producto->Id_Producto;
            }

            $cantidadesNuevas[$producto->Id_Producto] = ($cantidadesNuevas[$producto->Id_Producto] ?? 0) + (int) $detalle['cantidad'];

            foreach ($detalle['series'] as $serie) {
                ProductoSerie::query()->create([
                    'Id_Producto' => $producto->Id_Producto,
                    'Numero_Serie' => $serie,
                    'Fecha_Ingreso' => now(),
                    'Estado' => ProductoSerie::ESTADO_DISPONIBLE,
                    'Observacion' => null,
                ]);
            }

            DetalleCompra::query()->create([
                'Id_Compra' => $compra->Id_Compra,
                'Id_Producto' => $producto->Id_Producto,
                'Cantidad' => $detalle['cantidad'],
                'Precio_Compra' => $detalle['precio_compra'],
                'Meses_Garantia_Proveedor' => $detalle['garantia_proveedor'],
                'Subtotal' => $detalle['subtotal'],
            ]);
        }
    }

    protected function ajustarStockPorDiferencia(array $cantidadesAnteriores, array $cantidadesNuevas): void
    {
        $productosIds = array_unique(array_merge(array_keys($cantidadesAnteriores), array_keys($cantidadesNuevas)));

        foreach ($productosIds as $productoId) {
            $anterior = (int) ($cantidadesAnteriores[$productoId] ?? 0);
            $nuevo = (int) ($cantidadesNuevas[$productoId] ?? 0);
            $diferencia = $nuevo - $anterior;

            if ($diferencia === 0) {
                continue;
            }

            $producto = Producto::query()
                ->where('Id_Producto', (int) $productoId)
                ->lockForUpdate()
                ->first();

            if (! $producto) {
                throw new \RuntimeException('No se encontró uno de los productos relacionados a la compra.');
            }

            if ($diferencia < 0 && (int) $producto->Stock_Actual < abs($diferencia)) {
                throw new \RuntimeException('No se puede reducir la compra del producto "' . $producto->Nombre_Producto . '" porque el stock actual no alcanza para revertir esa cantidad.');
            }

            if ($diferencia > 0) {
                $producto->increment('Stock_Actual', $diferencia);
            } else {
                $producto->decrement('Stock_Actual', abs($diferencia));
            }
        }
    }

    protected function crearCompraNueva(int $idUsuario): void
    {
        Compra::query()->getConnection()->transaction(function () use ($idUsuario) {
            $categoriasCreadas = [];
            $marcasCreadas = [];
            $cantidadesNuevas = [];
            $primerProductoId = null;

            $compra = Compra::query()->create($this->datosEncabezadoCompra($idUsuario));

            $this->guardarDetallesEnCompra($compra, $categoriasCreadas, $marcasCreadas, $cantidadesNuevas, $primerProductoId);
            $this->ajustarStockPorDiferencia([], $cantidadesNuevas);

            $compra->Id_producto = $primerProductoId;
            $compra->save();
        });
    }

    protected function actualizarCompraExistente(int $idUsuario): void
    {
        Compra::query()->getConnection()->transaction(function () use ($idUsuario) {
            $compra = Compra::query()
                ->where('Id_Compra', $this->compraEditandoId)
                ->lockForUpdate()
                ->first();

            if (! $compra) {
                throw new \RuntimeException('No se encontró la compra que intenta actualizar.');
            }

            $detallesActuales = DetalleCompra::query()
                ->where('Id_Compra', $compra->Id_Compra)
                ->lockForUpdate()
                ->get(['Id_Producto', 'Cantidad']);

            $cantidadesAnteriores = $detallesActuales
                ->groupBy('Id_Producto')
                ->map(fn ($items) => (int) $items->sum('Cantidad'))
                ->toArray();

            $compra->fill($this->datosEncabezadoCompra($idUsuario));
            $compra->save();

            DetalleCompra::query()
                ->where('Id_Compra', $compra->Id_Compra)
                ->delete();

            $categoriasCreadas = [];
            $marcasCreadas = [];
            $cantidadesNuevas = [];
            $primerProductoId = null;

            $this->guardarDetallesEnCompra($compra, $categoriasCreadas, $marcasCreadas, $cantidadesNuevas, $primerProductoId);
            $this->ajustarStockPorDiferencia($cantidadesAnteriores, $cantidadesNuevas);

            $compra->Id_producto = $primerProductoId;
            $compra->Total = $this->totalGeneral();
            $compra->save();
        });
    }

    public function guardarCompra(): void
    {
        $this->resetErrorBag();

        if ($this->editandoDetalle) {
            $this->mostrarToast('Primero termine o cancele la edición del detalle.', 'error');
            return;
        }

        $this->validate([
            'idProveedor' => 'required|exists:proveedor,Id_Proveedor',
            'numeroCompra' => 'required|string|max:50',
            'fechaCompra' => 'required|date|before_or_equal:today',
            'tipoCompra' => 'required|in:CONTADO,CREDITO',
            'fechaLimiteCredito' => $this->tipoCompra === 'CREDITO'
                ? 'required|date|after_or_equal:today'
                : 'nullable|date',
            'medioPago' => 'required|in:PAGO_FISICO,TRANSFERENCIA',
            'idCuentaBancaria' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $this->medioPago === 'TRANSFERENCIA'),
                Rule::exists('cuenta_bancaria', 'Id_Cuenta_Bancaria')->where(fn ($query) => $query->where('Estado', 1)),
            ],
            'numeroReferenciaTransferencia' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn () => $this->medioPago === 'TRANSFERENCIA'),
            ],
            'retencion' => 'required|numeric|min:0',
            'iva' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:255',
        ], [
            'fechaLimiteCredito.required' => 'Debe indicar la fecha límite de pago cuando la compra es a crédito.',
            'fechaLimiteCredito.after_or_equal' => 'La fecha límite de pago no puede ser una fecha pasada.',
            'idCuentaBancaria.required' => 'Debe seleccionar la cuenta bancaria cuando el medio de pago es transferencia.',
            'idCuentaBancaria.exists' => 'La cuenta bancaria seleccionada no existe o está inactiva.',
            'numeroReferenciaTransferencia.required' => 'Debe registrar el número de referencia o comprobante de la transferencia.',
        ]);

        if (count($this->detalles) === 0) {
            $this->mostrarToast('Debe agregar al menos un producto a la compra.', 'error');
            return;
        }

        foreach ($this->detalles as $detalle) {
            $series = $detalle['series'] ?? [];

            if (count($series) > 0 && count($series) !== (int) $detalle['cantidad']) {
                $this->mostrarToast(
                    'Si un producto tiene números de serie, la cantidad de series debe coincidir exactamente con la cantidad comprada.',
                    'error'
                );
                return;
            }
        }

        $idUsuario = $this->obtenerUsuarioActual();

        if (! $idUsuario) {
            $this->mostrarToast('No existe un usuario registrado para guardar la compra. Cree al menos un usuario o active el login.', 'error');
            return;
        }

        $todasLasSeries = collect($this->detalles)
            ->flatMap(fn ($detalle) => $detalle['series'])
            ->values()
            ->toArray();

        if (count($todasLasSeries) !== count(array_unique($todasLasSeries))) {
            $this->mostrarToast('Hay números de serie repetidos en la compra.', 'error');
            return;
        }

        if (count($todasLasSeries) > 0) {
            $seriesExistentes = ProductoSerie::query()
                ->whereIn('Numero_Serie', $todasLasSeries)
                ->pluck('Numero_Serie')
                ->toArray();

            if (count($seriesExistentes) > 0) {
                $this->mostrarToast('Ya existen números de serie registrados: ' . implode(', ', $seriesExistentes), 'error');
                return;
            }
        }

        try {
            $editandoCompra = $this->compraEditandoId !== null;

            if ($editandoCompra) {
                $this->actualizarCompraExistente((int) $idUsuario);
            } else {
                $this->crearCompraNueva((int) $idUsuario);
            }

            $this->cancelarCompra(false);
            $this->resetPage('comprasPage');
            $this->mostrarToast($editandoCompra ? 'Compra actualizada correctamente.' : 'Compra guardada correctamente.');
        } catch (\Throwable $e) {
            $this->mostrarToast('Error al guardar la compra: ' . $e->getMessage(), 'error');
        }
    }

    protected function obtenerUsuarioActual(): ?int
    {
        $idAuth = auth()->id();

        if ($idAuth && Usuario::query()->where('Id_Usuario', $idAuth)->exists()) {
            return (int) $idAuth;
        }

        $primerUsuario = Usuario::query()
            ->orderBy('Id_Usuario')
            ->value('Id_Usuario');

        return $primerUsuario ? (int) $primerUsuario : null;
    }

    public function cancelarCompra(bool $mostrarMensaje = true): void
    {
        $estabaEditandoCompra = $this->compraEditandoId !== null;
        $this->compraEditandoId = null;
        $this->mostrarComprasRealizadas = false; // al cancelar se vuelve a la tabla normal de detalle.

        $this->buscarProveedor = '';
        $this->proveedorNombre = '';
        $this->idProveedor = '';

        $this->numeroCompra = '';
        $this->fechaCompra = Carbon::today()->toDateString();
        $this->tipoCompra = 'CONTADO';
        $this->fechaLimiteCredito = '';
        $this->medioPago = 'TRANSFERENCIA';
        $this->limpiarCuentaSeleccionada();
        $this->numeroReferenciaTransferencia = '';
        $this->observacion = '';
        $this->retencion = '0';
        $this->iva = '0';

        $this->detalles = [];
        $this->categoriasTemporales = [];
        $this->marcasTemporales = [];
        $this->mostrarNuevaCategoria = false;
        $this->mostrarNuevaMarca = false;
        $this->nombreCategoriaNueva = '';
        $this->nombreMarcaNueva = '';

        $this->limpiarDetalleProducto();
        $this->cargarCatalogos();
        $this->cargarCuentasBancarias();

        if ($mostrarMensaje) {
            $this->mostrarToast($estabaEditandoCompra ? 'Edición de compra cancelada.' : 'Compra cancelada. No se guardó nada en la base de datos.');
        }
    }

    public function limpiarDetalleProducto(): void
    {
        $this->limpiarProductoSeleccionado();
        $this->cantidad = '1';
        $this->precioCompra = '0';
        $this->precioVenta = '0';
        $this->precioVentaEditadoManual = false;
        $this->garantiaProveedor = '';
        $this->seriesTexto = '';

        $this->editandoDetalle = false;
        $this->indiceDetalleEditando = null;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    protected function limpiarProductoSeleccionado(bool $limpiarBusqueda = true): void
    {
        if ($limpiarBusqueda) {
            $this->buscarProducto = '';
        }

        $this->productosEncontrados = [];
        $this->idProducto = '';
        $this->productoNombre = '';
        $this->productoCategoria = '';
        $this->productoMarca = '';
        $this->productoModelo = '';
        $this->precioVentaActual = 0;
        $this->precioVentaEditadoManual = false;

        $this->nuevoNombreProducto = '';
        $this->nuevoModelo = '';
        $this->nuevoCategoriaSeleccionada = '';
        $this->nuevoMarcaSeleccionada = '';
        $this->nuevoStockMinimo = '0';
        $this->nuevoGarantiaNuevo = '';
        $this->nuevoGarantiaUsado = '';
        $this->nuevoEstado = '1';

        $this->precioVentaEditable = $this->modoProducto === 'nuevo';
    }

    public function subtotalGeneral(): float
    {
        return (float) collect($this->detalles)->sum('subtotal');
    }

    public function ivaGeneral(): float
    {
        return (float) $this->iva;
    }

    public function retencionGeneral(): float
    {
        return (float) $this->retencion;
    }

    public function totalGeneral(): float
    {
        return $this->subtotalGeneral() + $this->ivaGeneral() + $this->retencionGeneral();
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] px-3 py-4 sm:px-4 md:px-6 md:py-5">
    <div class="mx-auto flex w-full max-w-[1450px] flex-col gap-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42]">Compras</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Registre compras, agregue productos existentes o ingrese productos nuevos.
                </p>
            </div>

            <div class="w-full rounded-2xl border border-[#D7E4F3] bg-white px-4 py-3 shadow-sm sm:w-auto">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Total de compra</p>
                <p class="text-2xl font-bold text-[#1A2B42]">
                    C$ {{ number_format($this->totalGeneral(), 2) }}
                </p>
            </div>
        </div>

        {{-- MODIFICADO: se reemplazó la alerta fija por el componente toast temporal de MaryUI. --}}
        <x-toast position="toast-top toast-end" />


        @if ($mostrarModalCuentaBancaria)
            <div class="fixed inset-0 z-998 flex items-start justify-center overflow-y-auto bg-black/40 px-3 py-6 sm:items-center sm:px-4">
                <div class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-xl sm:p-5 max-h-[90vh] overflow-y-auto">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-[#1A2B42]">Agregar cuenta bancaria</h3>
                            <p class="text-sm text-[#5F6B7A]">
                                Esta cuenta quedará disponible para compras por transferencia.
                            </p>
                        </div>
                        <button type="button" wire:click="cerrarModalCuentaBancaria" class="text-2xl leading-none text-[#5F6B7A] hover:text-[#1A2B42]">
                            ×
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Banco <span class="text-red-600">*</span>
                            </label>
                            <x-input
                                wire:model.defer="nuevoBancoNombre"
                                type="text"
                                maxlength="100"
                                placeholder="Ej: BAC, Banpro, Lafise"
                                class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                            />
                            @error('nuevoBancoNombre')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Titular <span class="text-red-600">*</span>
                            </label>
                            <x-input
                                wire:model.defer="nuevoNombreTitular"
                                type="text"
                                maxlength="150"
                                placeholder="Nombre del dueño de la cuenta"
                                class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                            />
                            @error('nuevoNombreTitular')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Número de cuenta o últimos 4 dígitos <span class="text-red-600">*</span>
                            </label>
                            <x-input
                                wire:model.defer="nuevoUltimosDigitos"
                                type="text"
                                maxlength="50"
                                placeholder="Ej: 1234567890123456 o 3456"
                                class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                            />
                            @error('nuevoUltimosDigitos')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Tipo de cuenta <span class="text-red-600">*</span>
                            </label>
                            <select
                                wire:model.defer="nuevoTipoCuenta"
                                class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                            >
                                @foreach ($tiposCuentaBancaria as $tipoCuenta)
                                    <option value="{{ $tipoCuenta['id'] }}">{{ $tipoCuenta['nombre'] }}</option>
                                @endforeach
                            </select>
                            @error('nuevoTipoCuenta')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Moneda <span class="text-red-600">*</span>
                            </label>
                            <select
                                wire:model.defer="nuevoMonedaCuenta"
                                class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                            >
                                @foreach ($monedasCuentaBancaria as $monedaCuenta)
                                    <option value="{{ $monedaCuenta['id'] }}">{{ $monedaCuenta['nombre'] }}</option>
                                @endforeach
                            </select>
                            @error('nuevoMonedaCuenta')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="cerrarModalCuentaBancaria"
                            class="w-full rounded-lg border border-[#D7E4F3] px-4 py-2 text-sm font-semibold text-[#1A2B42] hover:bg-[#F0F3F7] sm:w-auto"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            wire:click="guardarCuentaBancaria"
                            class="w-full rounded-lg bg-[#2E8BC0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0B6FE4] sm:w-auto"
                        >
                            Guardar cuenta
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODIFICADO: se eliminó el panel lateral para que las compras realizadas se muestren abajo bajo demanda. --}}
        <div class="flex min-w-0 flex-col gap-4">
                @if ($compraEditandoId)
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800 shadow-sm">
                        Está editando la compra #{{ $numeroCompra }}. Puede modificar datos y detalles.
                    </div>
                @endif

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Datos generales de la compra</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Seleccione el proveedor, registre la factura y los montos generales.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 xl:grid-cols-12">
                <div class="relative sm:col-span-2 lg:col-span-3 xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Proveedor
                    </label>

                    <x-input
                        wire:model.live.debounce.250ms="buscarProveedor"
                        wire:dblclick="desbloquearProveedor"
                        type="text"
                        :readonly="$idProveedor !== ''"
                        placeholder="Buscar proveedor por nombre o RUC"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @if ($idProveedor !== '')
                        <p class="mt-1 text-xs text-[#5F6B7A]">
                            Doble click para cambiar el proveedor.
                        </p>
                    @endif

                    @error('idProveedor')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror

                    @if (count($proveedoresEncontrados) > 0 && $buscarProveedor !== '' && $idProveedor === '')
                        <div class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                            @foreach ($proveedoresEncontrados as $proveedor)
                                <button
                                    type="button"
                                    wire:click="seleccionarProveedor({{ $proveedor['id'] }})"
                                    class="block w-full px-4 py-3 text-left text-sm text-[#1A2B42] hover:bg-[#EAF2FB]"
                                >
                                    <span class="block font-semibold">{{ $proveedor['nombre'] }}</span>
                                    <span class="text-xs text-[#5F6B7A]">RUC: {{ $proveedor['ruc'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif ($buscarProveedor !== '' && $idProveedor === '' && count($proveedoresEncontrados) === 0)
                        <div class="mt-1 rounded-xl border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                            No se encontraron proveedores con esa búsqueda.
                        </div>
                    @endif
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        N. Factura
                    </label>
                    <x-input
                        wire:model.defer="numeroCompra"
                        type="text"
                        maxlength="50"
                        placeholder="No. factura"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('numeroCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Fecha
                    </label>
                    <x-input
                        wire:model.defer="fechaCompra"
                        type="date"
                        max="{{ now()->toDateString() }}"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('fechaCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Tipo de pago
                    </label>
                    <select
                        wire:model.live="tipoCompra"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                    >
                        @foreach ($tiposCompra as $tipo)
                            <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }}</option>
                        @endforeach
                    </select>
                    @error('tipoCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                @if ($tipoCompra === 'CREDITO')
                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Fecha límite de pago
                        </label>
                        <x-input
                            wire:model.defer="fechaLimiteCredito"
                            type="date"
                            min="{{ now()->toDateString() }}"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('fechaLimiteCredito')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Medio de pago
                    </label>
                    <select
                        wire:model.live="medioPago"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                    >
                        @foreach ($mediosPago as $medio)
                            <option value="{{ $medio['id'] }}">{{ $medio['nombre'] }}</option>
                        @endforeach
                    </select>
                    @error('medioPago')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                @if ($medioPago === 'TRANSFERENCIA')
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Cuenta destino
                        </label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <select
                                wire:model.live="idCuentaBancaria"
                                class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                            >
                                <option value="">{{ count($cuentasBancarias) > 0 ? 'Seleccione una cuenta' : 'No hay cuentas registradas' }}</option>
                                @foreach ($cuentasBancarias as $cuenta)
                                    <option value="{{ $cuenta['id'] }}">
                                         {{ $cuenta['ultimos_digitos'] }} · {{ $cuenta['titular'] }}
                                    </option>
                                @endforeach
                            </select>

                            <button
                                type="button"
                                wire:click="abrirModalCuentaBancaria"
                                title="Agregar cuenta bancaria"
                                class="inline-flex h-10 w-full shrink-0 items-center justify-center rounded-lg bg-[#2E8BC0] text-lg font-bold text-white hover:bg-[#0B6FE4] sm:w-10"
                            >
                                +
                            </button>
                        </div>
                        @error('idCuentaBancaria')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Banco</label>
                        <x-input
                            value="{{ $bancoCuentaSeleccionada }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo de cuenta</label>
                        <x-input
                            value="{{ $tipoCuentaSeleccionada }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="sm:col-span-1 lg:col-span-1">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Moneda</label>
                        <x-input
                            value="{{ $monedaCuentaSeleccionada }}"
                            readonly
                            placeholder="--"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Referencia
                        </label>
                        <x-input
                            wire:model.defer="numeroReferenciaTransferencia"
                            type="text"
                            maxlength="100"
                            placeholder="No. comprobante"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('numeroReferenciaTransferencia')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <div class="sm:col-span-1 lg:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">IVA</label>
                    <x-input
                        wire:model.live.debounce.250ms="iva"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('iva')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 lg:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Retención</label>
                    <x-input
                        wire:model.live.debounce.250ms="retencion"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('retencion')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-12">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación</label>
                    <textarea
                        wire:model.defer="observacion"
                        rows="2"
                        placeholder="Opcional"
                        class="w-full rounded-lg border-0 bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    ></textarea>
                    @error('observacion')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">
                        {{ $editandoDetalle ? 'Editar detalle' : 'Agregar producto' }}
                    </h2>
                    <p class="text-sm text-[#5F6B7A]">
                        {{ $editandoDetalle ? 'Modifique los datos del detalle seleccionado.' : 'Busque un producto existente o registre uno nuevo.' }}
                    </p>
                </div>

                <div class="grid w-full grid-cols-1 gap-1 rounded-xl bg-[#F0F3F7] p-1 sm:grid-cols-2 lg:w-auto">
                    <button
                        type="button"
                        wire:click="cambiarModoProducto('existente')"
                        class="{{ $modoProducto === 'existente' ? 'bg-[#2E8BC0] text-white' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-center text-sm font-semibold transition"
                    >
                        Producto existente
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarModoProducto('nuevo')"
                        class="{{ $modoProducto === 'nuevo' ? 'bg-[#2E8BC0] text-white' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-center text-sm font-semibold transition"
                    >
                        Producto nuevo
                    </button>
                </div>
            </div>

            @if ($editandoDetalle)
                <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
                    Está editando un detalle ya agregado. Actualice los datos o cancele la edición.
                </div>
            @endif

            @if ($modoProducto === 'existente')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 xl:grid-cols-12">
                    <div class="relative sm:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Buscar producto
                        </label>

                        <x-input
                            wire:model.live.debounce.250ms="buscarProducto"
                            wire:dblclick="desbloquearProducto"
                            type="text"
                            :readonly="$idProducto !== ''"
                            placeholder="Escriba para buscar coincidencias"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @if ($idProducto !== '')
                            <p class="mt-1 text-xs text-[#5F6B7A]">
                                Doble click para cambiar el producto.
                            </p>
                        @endif

                        @error('idProducto')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if (count($productosEncontrados) > 0 && $idProducto === '')
                            <div class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($productosEncontrados as $producto)
                                    <button
                                        type="button"
                                        wire:click="seleccionarProducto({{ $producto['id'] }})"
                                        class="block w-full px-4 py-3 text-left text-sm text-[#1A2B42] hover:bg-[#EAF2FB]"
                                    >
                                        <span class="block font-semibold">
                                            {{ $producto['marca'] }} {{ $producto['nombre'] }}
                                        </span>
                                        <span class="text-xs text-[#5F6B7A]">
                                            {{ $producto['modelo'] }} · {{ $producto['categoria'] }} · Stock: {{ $producto['stock'] }} · C$ {{ number_format($producto['precio_venta'], 2) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($buscarProducto !== '' && $idProducto === '' && count($productosEncontrados) === 0)
                            <div class="mt-1 rounded-xl border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                                Este producto no existe. Puede registrarlo usando la opción “Producto nuevo”.
                            </div>
                        @endif
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                        <x-input
                            value="{{ $productoCategoria }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                        <x-input
                            value="{{ $productoMarca }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                        <x-input
                            value="{{ $productoModelo }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 xl:grid-cols-12">
                    <div class="sm:col-span-2 xl:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Nombre del producto
                        </label>
                        <x-input
                            wire:model.defer="nuevoNombreProducto"
                            type="text"
                            maxlength="150"
                            placeholder="Ej: Laptop HP 15"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('nuevoNombreProducto')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Modelo
                        </label>
                        <x-input
                            wire:model.defer="nuevoModelo"
                            type="text"
                            maxlength="100"
                            placeholder="Modelo obligatorio"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('nuevoModelo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 xl:col-span-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Categoría
                            </label>
                            <button type="button" wire:click="$toggle('mostrarNuevaCategoria')" class="text-xs font-semibold text-[#0B6FE4]">
                                + Nueva
                            </button>
                        </div>

                        <select
                            wire:model.defer="nuevoCategoriaSeleccionada"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="">Seleccione categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria['valor'] }}">
                                    {{ $categoria['nombre'] }} {{ $categoria['temporal'] ? '(nueva)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('nuevoCategoriaSeleccionada')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if ($mostrarNuevaCategoria)
                            <div class="mt-2 flex gap-2">
                                <x-input
                                    wire:model.defer="nombreCategoriaNueva"
                                    type="text"
                                    placeholder="Nueva categoría"
                                    class="h-9 min-h-9 w-full rounded-lg bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                />
                                <x-button
                                    label="Agregar"
                                    wire:click="agregarCategoriaTemporal"
                                    class="h-9 min-h-9 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]"
                                />
                            </div>
                            @error('nombreCategoriaNueva')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div class="sm:col-span-2 xl:col-span-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Marca
                            </label>
                            <button type="button" wire:click="$toggle('mostrarNuevaMarca')" class="text-xs font-semibold text-[#0B6FE4]">
                                + Nueva
                            </button>
                        </div>

                        <select
                            wire:model.defer="nuevoMarcaSeleccionada"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="">Seleccione marca</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca['valor'] }}">
                                    {{ $marca['nombre'] }} {{ $marca['temporal'] ? '(nueva)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('nuevoMarcaSeleccionada')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if ($mostrarNuevaMarca)
                            <div class="mt-2 flex gap-2">
                                <x-input
                                    wire:model.defer="nombreMarcaNueva"
                                    type="text"
                                    placeholder="Nueva marca"
                                    class="h-9 min-h-9 w-full rounded-lg bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                />
                                <x-button
                                    label="Agregar"
                                    wire:click="agregarMarcaTemporal"
                                    class="h-9 min-h-9 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]"
                                />
                            </div>
                            @error('nombreMarcaNueva')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div class="sm:col-span-1 xl:col-span-1">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Stock mín.
                        </label>
                        <x-input
                            wire:model.defer="nuevoStockMinimo"
                            type="number"
                            min="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoStockMinimo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía nuevo</label>
                        <x-input
                            wire:model.defer="nuevoGarantiaNuevo"
                            type="number"
                            min="0"
                            placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoGarantiaNuevo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía usado</label>
                        <x-input
                            wire:model.defer="nuevoGarantiaUsado"
                            type="number"
                            min="0"
                            placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoGarantiaUsado')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="sm:col-span-1 xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Estado
                        </label>
                        <select
                            wire:model.defer="nuevoEstado"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        @error('nuevoEstado')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 xl:grid-cols-12">
                <div class="sm:col-span-1 xl:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Cantidad
                    </label>
                    <x-input
                        wire:model.defer="cantidad"
                        type="number"
                        min="1"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('cantidad')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Precio compra
                    </label>
                    <x-input
                        wire:model.live.debounce.250ms="precioCompra"
                        type="number"
                        step="0.01"
                        min="0"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('precioCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 xl:col-span-3">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Precio venta
                    </label>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <x-input
                            wire:model.defer="precioVenta"
                            type="number"
                            step="0.01"
                            min="0"
                            :readonly="! $precioVentaEditable"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />

                        @if ($modoProducto === 'existente' && $idProducto !== '')
                            <button
                                type="button"
                                wire:click="habilitarEdicionPrecioVenta"
                                class="inline-flex h-10 min-h-10 w-full shrink-0 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-semibold transition sm:w-auto {{ $precioVentaEditable ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]' }}"
                            >
                                <span>{{ $precioVentaEditable ? 'Editable' : 'Editar' }}</span>
                            </button>
                        @endif
                    </div>

                    @if ($modoProducto === 'existente' && $idProducto !== '' && $precioVentaEditable)
                        <div class="mt-2 rounded-lg border {{ $this->precioVentaHabilitadoAutomaticamente() && ! $precioVentaEditadoManual ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }} px-3 py-2 text-xs font-medium">
                            {{ $this->precioVentaHabilitadoAutomaticamente() && ! $precioVentaEditadoManual
                                ? 'Habilitado para edición: el precio de compra es igual o mayor al precio de venta actual.'
                                : 'Edición manual habilitada. El nuevo precio de venta se actualizará al guardar la compra.' }}
                        </div>
                    @endif

                    @error('precioVenta')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="sm:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Garantía proveedor
                    </label>
                    <x-input
                        wire:model.defer="garantiaProveedor"
                        type="number"
                        min="0"
                        max="240"
                        step="1"
                        placeholder="Meses"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('garantiaProveedor')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror

                </div>

                <div class="sm:col-span-2 xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Número de serie
                        <span class="text-xs font-normal text-[#5F6B7A]">
                            Opcional
                        </span>
                    </label>
                    <textarea
                        wire:model.defer="seriesTexto"
                        rows="2"
                        placeholder="Debe escribir una por cada unidad. Ej: SN001, SN002"
                        class="w-full rounded-lg border-0 bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    ></textarea>
                    @error('seriesTexto')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
                @if ($editandoDetalle)
                    <x-button
                        label="Cancelar edición"
                        type="button"
                        wire:click="cancelarEdicionDetalle"
                        class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7] sm:w-auto"
                    />

                    <x-button
                        label="Actualizar detalle"
                        icon="o-pencil-square"
                        type="button"
                        wire:click="agregarDetalle"
                        class="h-10 min-h-10 w-full border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4] sm:w-auto"
                    />
                @else
                    <x-button
                        label="Limpiar producto"
                        type="button"
                        wire:click="limpiarDetalleProducto"
                        class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7] sm:w-auto"
                    />

                    <x-button
                        label="Agregar a la compra"
                        icon="o-plus"
                        type="button"
                        wire:click="agregarDetalle"
                        class="h-10 min-h-10 w-full border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4] sm:w-auto"
                    />
                @endif
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            {{-- esta tarjeta alterna entre el detalle temporal y las compras realizadas para evitar el panel lateral amontonado. --}}
            <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">
                        {{ $mostrarComprasRealizadas ? 'Compras realizadas' : 'Detalle de compra' }}
                    </h2>
                    <p class="text-sm text-[#5F6B7A]">
                        {{ $mostrarComprasRealizadas ? 'Seleccione una compra para editarla o vuelva al detalle actual.' : 'Revise los productos agregados antes de guardar la compra.' }}
                    </p>
                </div>

                @if (! $mostrarComprasRealizadas)
                    <div class="flex w-full flex-col gap-3 xl:w-auto xl:flex-row xl:items-start">
                        <div class="grid grid-cols-2 gap-2 text-right sm:grid-cols-4">
                            <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                                <p class="text-xs text-[#5F6B7A]">Subtotal</p>
                                <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->subtotalGeneral(), 2) }}</p>
                            </div>

                            <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                                <p class="text-xs text-[#5F6B7A]">IVA</p>
                                <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->ivaGeneral(), 2) }}</p>
                            </div>

                            <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                                <p class="text-xs text-[#5F6B7A]">Retención</p>
                                <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->retencionGeneral(), 2) }}</p>
                            </div>

                            <div class="rounded-xl bg-[#EAF4FD] px-3 py-2">
                                <p class="text-xs text-[#5F6B7A]">Total</p>
                                <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->totalGeneral(), 2) }}</p>
                            </div>
                        </div>

                        <x-button
                            label="Ver compras"
                            icon="o-list-bullet"
                            type="button"
                            wire:click="verComprasRealizadas"
                            class="h-10 min-h-10 w-full border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4] xl:w-auto"
                        />
                    </div>
                @else
                    <x-button
                        label="Volver al detalle"
                        icon="o-arrow-left"
                        type="button"
                        wire:click="verDetalleCompra"
                        class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7] sm:w-auto"
                    />
                @endif
            </div>

            @if ($mostrarComprasRealizadas)
                @php($comprasRealizadas = $this->comprasRealizadas())

                {{-- las compras realizadas ahora se muestran abajo en tarjetas responsivas, no en un panel lateral. --}}
                <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF] p-3 sm:p-4">
                    <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <x-input
                            wire:model.live.debounce.300ms="buscarCompraRealizada"
                            icon="o-magnifying-glass"
                            type="text"
                            placeholder="Buscar factura/proveedor"
                            class="h-10 min-h-10 w-full rounded-lg bg-white text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        <x-button
                            label="Cerrar compras"
                            icon="o-x-mark"
                            type="button"
                            wire:click="verDetalleCompra"
                            class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7] lg:w-auto"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                        @forelse ($comprasRealizadas as $compraRealizada)
                            <div wire:key="compra-realizada-{{ $compraRealizada->Id_Compra }}" class="rounded-xl border {{ $compraEditandoId === (int) $compraRealizada->Id_Compra ? 'border-[#2E8BC0] bg-[#EAF4FD]' : 'border-[#D7E4F3] bg-white' }} p-4 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-bold text-[#1A2B42]">
                                            #{{ $compraRealizada->Numero_Compra }}
                                        </p>
                                        <p class="truncate text-sm text-[#5F6B7A]">
                                            {{ $this->nombreProveedorPorId((int) $compraRealizada->Id_Proveedor) }}
                                        </p>
                                    </div>

                                    <span class="w-fit shrink-0 rounded-full bg-[#F0F3F7] px-3 py-1 text-xs font-semibold text-[#1A2B42]">
                                        {{ $compraRealizada->Tipo_Compra }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-[#5F6B7A] sm:grid-cols-3">
                                    <div class="rounded-lg bg-[#F0F3F7] px-3 py-2">
                                        <span class="block font-semibold text-[#1A2B42]">
                                            {{ Carbon::parse($compraRealizada->Fecha_Compra)->format('d/m/Y') }}
                                        </span>
                                        Fecha
                                    </div>

                                    <div class="rounded-lg bg-[#F0F3F7] px-3 py-2">
                                        <span class="block font-semibold text-[#1A2B42]">
                                            {{ (int) ($compraRealizada->detalles_count ?? 0) }} producto(s)
                                        </span>
                                        Detalle
                                    </div>

                                    <div class="rounded-lg bg-[#EAF4FD] px-3 py-2 sm:text-right">
                                        <span class="block font-semibold text-[#1A2B42]">
                                            C$ {{ number_format((float) $compraRealizada->Total, 2) }}
                                        </span>
                                        Total
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <a
                                        href="{{ route('compras.comprobante', $compraRealizada->Id_Compra) }}"
                                        target="_blank"
                                        rel="noopener"
                                        title="Imprimir comprobante de compra"
                                        aria-label="Imprimir comprobante de compra"
                                        class="inline-flex h-9 min-h-9 w-full items-center justify-center rounded-lg border border-[#D7E4F3] bg-white px-3 text-[#1A2B42] transition hover:bg-[#F0F3F7] sm:w-10"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 9V3.75h10.5V9m-10.5 8.25H5.25A2.25 2.25 0 0 1 3 15v-3.75A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25V15a2.25 2.25 0 0 1-2.25 2.25h-1.5m-10.5 0v3h10.5v-6.75H6.75v3.75Z" />
                                        </svg>
                                    </a>

                                    <x-button
                                        label="Editar"
                                        icon="o-pencil-square"
                                        type="button"
                                        wire:click="editarCompra({{ $compraRealizada->Id_Compra }})"
                                        class="h-9 min-h-9 w-full border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4] sm:w-auto"
                                    />
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-[#D7E4F3] bg-white px-3 py-8 text-center text-sm text-[#7B8794] lg:col-span-2 2xl:col-span-3">
                                No hay compras para mostrar.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 text-xs">
                        <x-pagination :rows="$comprasRealizadas" />
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
                    <div class="max-h-[430px] overflow-x-auto overflow-y-auto">
                        <table class="min-w-[1350px] w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Tipo</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Producto</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Categoría</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Marca</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Cantidad</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">P. compra</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">P. venta</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Garantía </th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Series</th>
                                    <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Subtotal</th>
                                    <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Acción</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($detalles as $index => $detalle)
                                    <tr class="{{ $editandoDetalle && $indiceDetalleEditando === $index ? 'bg-blue-50' : 'odd:bg-white even:bg-[#F8FBFF]' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <span class="{{ $detalle['modo'] === 'nuevo' ? 'bg-[#EAF4FD] text-[#0E48A1]' : 'bg-green-100 text-green-700' }} rounded-full px-2.5 py-1 text-xs font-semibold">
                                                {{ $detalle['modo'] === 'nuevo' ? 'Nuevo' : 'Existente' }}
                                            </span>
                                        </td>

                                        <td class="px-3 py-3 whitespace-nowrap font-semibold">
                                            {{ $detalle['nombre_producto'] }}
                                            <span class="block text-xs font-normal text-[#5F6B7A]">{{ $detalle['modelo'] }}</span>
                                        </td>

                                        <td class="px-3 py-3 whitespace-nowrap">{{ $detalle['categoria'] }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $detalle['marca'] }}</td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">{{ $detalle['cantidad'] }}</td>
                                        <td class="px-3 py-3 text-right whitespace-nowrap">C$ {{ number_format($detalle['precio_compra'], 2) }}</td>
                                        <td class="px-3 py-3 text-right whitespace-nowrap">C$ {{ number_format($detalle['precio_venta'], 2) }}</td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            {{ $detalle['garantia_proveedor'] !== null ? $detalle['garantia_proveedor'] . ' mes(es)' : 'Sin garantía' }}
                                        </td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">{{ count($detalle['series']) }}</td>
                                        <td class="px-3 py-3 text-right font-semibold whitespace-nowrap">C$ {{ number_format($detalle['subtotal'], 2) }}</td>

                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <x-button
                                                    icon="o-pencil-square"
                                                    wire:click="editarDetalle({{ $index }})"
                                                    class="h-8 min-h-8 border-0 bg-blue-50 px-3 text-xs text-blue-700 hover:bg-blue-100"
                                                />

                                                <x-button
                                                    icon="o-trash"
                                                    wire:click="quitarDetalle({{ $index }})"
                                                    class="h-8 min-h-8 border-0 bg-red-50 px-3 text-xs text-red-700 hover:bg-red-100"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                            Todavía no hay productos agregados a la compra.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
                    <x-button
                        label="{{ $compraEditandoId ? 'Cancelar edición' : 'Cancelar compra' }}"
                        icon="o-x-mark"
                        type="button"
                        wire:click="cancelarCompra"
                        class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7] sm:w-auto"
                    />

                    <x-button
                        label="{{ $compraEditandoId ? 'Actualizar compra' : 'Guardar compra' }}"
                        icon="o-check"
                        type="button"
                        wire:click="guardarCompra"
                        class="h-10 min-h-10 w-full border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4] sm:w-auto"
                    />
                </div>
            @endif
        </x-card>
        </div>
    </div>
</div>
