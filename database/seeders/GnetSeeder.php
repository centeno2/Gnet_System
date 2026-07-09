<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GnetSeeder extends Seeder
{
    private const CANTIDAD = 10000;
    private const CHUNK = 1000;
    private const CLIENTES_INSTITUCIONALES_DESDE = 5001;
    private const PASSWORD_DEMO = 'admin123';

    private array $productosConSerie = [];
    private array $productosSinSerie = [];
    private array $seriesPorProducto = [];

    private array $nombres = [
        'Carlos', 'María', 'José', 'Ana', 'Luis', 'Sofía', 'Javier', 'Daniela', 'Miguel', 'Valeria',
        'Roberto', 'Lucía', 'Fernando', 'Gabriela', 'Kevin', 'Karla', 'Andrés', 'Martha', 'Elena', 'Pedro',
    ];

    private array $apellidos = [
        'González', 'López', 'Martínez', 'Rodríguez', 'Pérez', 'Hernández', 'García', 'Sánchez', 'Ramírez', 'Torres',
        'Flores', 'Castillo', 'Reyes', 'Mendoza', 'Chavarría', 'Centeno', 'Barrera', 'Matamoros', 'Salinas', 'Vargas',
    ];

    private array $municipios = [
        'Managua', 'Masaya', 'Granada', 'León', 'Matagalpa', 'Jinotega', 'Estelí', 'Chinandega', 'Boaco', 'Rivas',
        'Carazo', 'Nueva Segovia', 'Chontales', 'Madriz', 'Río San Juan', 'Bluefields', 'Puerto Cabezas', 'Muy Muy', 'Matiguás', 'Sébaco',
    ];

    private array $productosBase = [
        'Laptop empresarial', 'Laptop gaming', 'Monitor LED', 'Impresora multifuncional', 'Tóner compatible', 'Mouse inalámbrico',
        'Teclado mecánico', 'Disco SSD', 'Memoria RAM', 'Router inalámbrico', 'Switch de red', 'Cámara IP', 'DVR de seguridad',
        'UPS regulador', 'Cable HDMI', 'Cable UTP', 'Fuente de poder', 'Audífonos', 'Webcam HD', 'Adaptador USB',
    ];

    public function run(): void
    {
        DB::disableQueryLog();
        mt_srand(20260603);

        $this->limpiarBase();
        $this->crearCatalogosBase();
        $this->crearAdminAcceso();

        $this->crearClientes();
        $this->crearProveedores();
        $this->crearTrabajadoresYUsuarios();
        $this->crearClientesCredito();
        $this->crearProductosYSeries();
        $this->crearTasasCambio();
        $this->crearCompras();
        $this->crearVentasCreditosYPagos();
        $this->crearServiciosTecnicos();
        $this->crearContratosInstalacion();
        $this->crearCajaArqueosYEgresos();
        $this->crearPlanillasPagosVacaciones();
        $this->crearDevoluciones();
        $this->crearMovimientosInventario();
        $this->ajustarAutoIncrementos();
    }

    private function limpiarBase(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'abono_credito',
            'apertura_caja',
            'arqueo_caja',
            'banco',
            'cache',
            'cache_locks',
            'cargo',
            'categoria_producto',
            'cliente',
            'cliente_credito',
            'cliente_credito_movimiento',
            'compra',
            'contrato_instalacion_camara',
            'contrato_instalacion_camara_checklist',
            'contrato_instalacion_camara_producto',
            'credito',
            'cuenta_bancaria',
            'deduccion_trabajador',
            'detalle_arqueo',
            'detalle_compra',
            'detalle_devolucion',
            'detalle_planilla',
            'detalle_venta',
            'devolucion',
            'egreso',
            'failed_jobs',
            'incentivo_trabajador',
            'job_batches',
            'jobs',
            'marca',
            'movimiento_caja',
            'movimiento_inventario',
            'movimiento_vacacion',
            'pago_contrato_instalacion_camara',
            'pago_planilla',
            'pago_servicio_tecnico',
            'pago_venta',
            'password_reset_tokens',
            'persona',
            'planilla',
            'producto',
            'producto_serie',
            'proveedor',
            'servicio',
            'servicio_tecnico',
            'servicio_tecnico_checklist',
            'servicio_tecnico_producto',
            'sessions',
            'tarifa_copia',
            'tasa_cambio',
            'trabajador',
            'usuario',
            'vacaciones',
            'venta',
        ] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function crearCatalogosBase(): void
    {
        DB::table('cargo')->insert([
            ['Id_Cargo' => 1, 'Cargo_Asignado' => 'Administrador'],
            ['Id_Cargo' => 2, 'Cargo_Asignado' => 'Gerente'],
            ['Id_Cargo' => 3, 'Cargo_Asignado' => 'Cajera'],
            ['Id_Cargo' => 4, 'Cargo_Asignado' => 'Técnico'],
            ['Id_Cargo' => 5, 'Cargo_Asignado' => 'Super usuario'],
        ]);

        $categorias = [
            'Laptops', 'Impresoras', 'Toner', 'Cámaras', 'Monitores', 'Redes', 'Almacenamiento', 'Periféricos',
            'Seguridad', 'Energía', 'Accesorios', 'Componentes', 'Cables', 'Software', 'Repuestos', 'Audio',
        ];

        $rows = [];
        foreach ($categorias as $index => $nombre) {
            $rows[] = ['Id_Categoria' => $index + 1, 'Nombre_Categoria' => $nombre];
        }
        DB::table('categoria_producto')->insert($rows);

        $marcas = [
            'HP', 'Dell', 'Lenovo', 'Asus', 'Acer', 'Epson', 'Canon', 'Brother', 'Dahua', 'Hikvision',
            'TP-Link', 'Ubiquiti', 'Samsung', 'LG', 'Kingston', 'Logitech', 'APC', 'Seagate', 'Western Digital',
            'Intel', 'AMD', 'Xiaomi', 'Sony', 'Steren', 'Mercusys', 'Nexxt', 'ADATA', 'Corsair', 'Gigabyte', 'MSI',
        ];

        $rows = [];
        foreach ($marcas as $index => $nombre) {
            $rows[] = ['Id_Marca' => $index + 1, 'Nombre_Marca' => $nombre, 'Estado' => 1];
        }
        DB::table('marca')->insert($rows);

        DB::table('servicio')->insert([
            [
                'Id_Servicio' => 1,
                'Nombre_Servicio' => 'Fotocopias',
                'Descripcion' => 'Servicio de fotocopias e impresiones por cantidad.',
                'Precio_Base' => 0,
                'Requiere_Contrato' => 0,
                'Requiere_Anticipo' => 0,
                'Porcentaje_Anticipo' => 0,
                'Garantia' => 0,
                'Estado' => 1,
                'Tipo_Servicio' => 'COPIA',
                'Unidad_Medida' => 'COPIA',
                'Permite_Credito' => 1,
            ],
            [
                'Id_Servicio' => 2,
                'Nombre_Servicio' => 'Servicio técnico',
                'Descripcion' => 'Diagnóstico, reparación y mantenimiento de equipos.',
                'Precio_Base' => 0,
                'Requiere_Contrato' => 0,
                'Requiere_Anticipo' => 0,
                'Porcentaje_Anticipo' => 0,
                'Garantia' => 1,
                'Estado' => 1,
                'Tipo_Servicio' => 'TECNICO',
                'Unidad_Medida' => 'SERVICIO',
                'Permite_Credito' => 1,
            ],
            [
                'Id_Servicio' => 3,
                'Nombre_Servicio' => 'Instalación de cámaras',
                'Descripcion' => 'Instalación, configuración y pruebas de sistemas de cámaras.',
                'Precio_Base' => 0,
                'Requiere_Contrato' => 1,
                'Requiere_Anticipo' => 1,
                'Porcentaje_Anticipo' => 30,
                'Garantia' => 1,
                'Estado' => 1,
                'Tipo_Servicio' => 'INSTALACION',
                'Unidad_Medida' => 'CONTRATO',
                'Permite_Credito' => 1,
            ],
        ]);

        DB::table('tarifa_copia')->insert([
            ['Id_Tarifa_Copia' => 1, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia B/N Carta una cara', 'Tipo_Color' => 'BN', 'Formato' => 'CARTA', 'Lados' => 'UNA_CARA', 'Precio_Unitario' => 2, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 2, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia B/N Carta doble cara', 'Tipo_Color' => 'BN', 'Formato' => 'CARTA', 'Lados' => 'DOBLE_CARA', 'Precio_Unitario' => 3, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 3, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia Color Carta una cara', 'Tipo_Color' => 'COLOR', 'Formato' => 'CARTA', 'Lados' => 'UNA_CARA', 'Precio_Unitario' => 10, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 4, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia Color Carta doble cara', 'Tipo_Color' => 'COLOR', 'Formato' => 'CARTA', 'Lados' => 'DOBLE_CARA', 'Precio_Unitario' => 18, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 5, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia B/N Oficio una cara', 'Tipo_Color' => 'BN', 'Formato' => 'OFICIO', 'Lados' => 'UNA_CARA', 'Precio_Unitario' => 3, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 6, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia B/N Oficio doble cara', 'Tipo_Color' => 'BN', 'Formato' => 'OFICIO', 'Lados' => 'DOBLE_CARA', 'Precio_Unitario' => 5, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 7, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia Color Oficio una cara', 'Tipo_Color' => 'COLOR', 'Formato' => 'OFICIO', 'Lados' => 'UNA_CARA', 'Precio_Unitario' => 12, 'Estado' => 1, 'Fecha_Registro' => now()],
            ['Id_Tarifa_Copia' => 8, 'Id_Servicio' => 1, 'Nombre_Tarifa' => 'Fotocopia Color Oficio doble cara', 'Tipo_Color' => 'COLOR', 'Formato' => 'OFICIO', 'Lados' => 'DOBLE_CARA', 'Precio_Unitario' => 22, 'Estado' => 1, 'Fecha_Registro' => now()],
        ]);

        DB::table('banco')->insert([
            ['Id_Banco' => 1, 'Nombre_Banco' => 'Lafise', 'Estado' => 1],
            ['Id_Banco' => 2, 'Nombre_Banco' => 'BAC', 'Estado' => 1],
            ['Id_Banco' => 3, 'Nombre_Banco' => 'Banpro', 'Estado' => 1],
            ['Id_Banco' => 4, 'Nombre_Banco' => 'BDF', 'Estado' => 1],
            ['Id_Banco' => 5, 'Nombre_Banco' => 'Ficohsa', 'Estado' => 1],
        ]);

        DB::table('cuenta_bancaria')->insert([
            ['Id_Cuenta_Bancaria' => 1, 'Id_Banco' => 1, 'Nombre_Titular' => 'GNET System Demo', 'Ultimos_Digitos' => '6710', 'Tipo_Cuenta' => 'CUENTA_AHORRO', 'Moneda' => 'DOLARES', 'Estado' => 1],
            ['Id_Cuenta_Bancaria' => 2, 'Id_Banco' => 2, 'Nombre_Titular' => 'GNET System Demo', 'Ultimos_Digitos' => '8452', 'Tipo_Cuenta' => 'CUENTA_CORRIENTE', 'Moneda' => 'CORDOBAS', 'Estado' => 1],
            ['Id_Cuenta_Bancaria' => 3, 'Id_Banco' => 3, 'Nombre_Titular' => 'GNET System Demo', 'Ultimos_Digitos' => '1930', 'Tipo_Cuenta' => 'CUENTA_AHORRO', 'Moneda' => 'CORDOBAS', 'Estado' => 1],
        ]);
    }

    private function crearAdminAcceso(): void
    {
        DB::table('persona')->insert([
            'Id_Persona' => 1,
            'Primer_Nombre' => 'Admin',
            'Segundo_Nombre' => null,
            'Primer_Apellido' => 'Sistema',
            'Segundo_Apellido' => 'GNET',
            'Direccion' => 'Managua, Nicaragua',
            'Telefono' => '88888888',
        ]);

        DB::table('trabajador')->insert([
            'Id_Trabajador' => 1,
            'Id_Persona' => 1,
            'Fecha_Ingreso' => '2025-01-01',
            'Fecha_Salida' => null,
            'Motivo_Salida' => null,
            'Estado' => 1,
            'Id_Cargo' => 1,
            'Cedula' => '0010000000001A',
            'Salario' => 50000,
        ]);

        $password = Hash::make(self::PASSWORD_DEMO);

        DB::table('usuario')->insert([
            'Id_Usuario' => 1,
            'Id_Trabajador' => 1,
            'Nombre_Usuario' => 'admin',
            'Contraseña_Usuario' => $password,
            'Estado' => 1,
            'Token_Recuperacion' => null,
            'Fecha_Recuperacion' => null,
            'Intentos_Fallidos' => 0,
            'Correo' => 'admin@gnet.local',
        ]);
    }

    private function crearClientes(): void
    {
        $personas = [];
        $clientes = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $idPersona = $i + 1;
            $esInstitucion = $i >= self::CLIENTES_INSTITUCIONALES_DESDE;
            $municipio = $this->municipios[$i % count($this->municipios)];

            // REGLA BD:
            // Tipo_Cliente = 1 NATURAL      => Id_Persona obligatorio e Institucion NULL.
            // Tipo_Cliente = 2 INSTITUCION  => Id_Persona NULL e Institucion obligatoria.
            if (! $esInstitucion) {
                $personas[] = $this->personaRow($idPersona, $i, 'Cliente');
            }

            $clientes[] = [
                'Id_Cliente' => $i,
                'Id_Persona' => $esInstitucion ? null : $idPersona,
                'Tipo_Cliente' => $esInstitucion ? 2 : 1,
                'Institucion' => $esInstitucion ? 'Institución Demo ' . str_pad((string) $i, 5, '0', STR_PAD_LEFT) : null,
                'Telefono_Institucion' => $esInstitucion ? $this->telefono($i + 1000) : null,
                'Direccion_Institucion' => $esInstitucion ? 'Barrio central, ' . $municipio : null,
                'Correo_Institucion' => $esInstitucion ? 'institucion' . $i . '@demo.test' : null,
                'Municipio' => $municipio,
                'Estado' => $i % 37 === 0 ? 0 : 1,
                'Tipo_pago' => $esInstitucion ? ($i % 2 === 0 ? 2 : 1) : 1,
            ];

            if (count($clientes) >= self::CHUNK) {
                $this->flushInsert('persona', $personas);
                DB::table('cliente')->insert($clientes);
                $personas = [];
                $clientes = [];
            }
        }

        $this->flushInsert('persona', $personas);
        $this->flushInsert('cliente', $clientes);
    }

    private function crearProveedores(): void
    {
        $personas = [];
        $proveedores = [];
        $inicioPersona = self::CANTIDAD + 2;

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $idPersona = $inicioPersona + $i - 1;
            $esEmpresa = $i > 5000;
            $municipio = $this->municipios[($i + 5) % count($this->municipios)];

            // REGLA BD:
            // Tipo_Proveedor = 1 NATURAL => Id_Persona obligatorio y Empresa NULL.
            // Tipo_Proveedor = 2 EMPRESA => Id_Persona NULL y Empresa obligatoria.
            if (! $esEmpresa) {
                $personas[] = $this->personaRow($idPersona, $i, 'Proveedor');
            }

            $proveedores[] = [
                'Id_Proveedor' => $i,
                'Id_Persona' => $esEmpresa ? null : $idPersona,
                'Tipo_Proveedor' => $esEmpresa ? 2 : 1,
                'Empresa' => $esEmpresa ? 'Proveedor Empresa Demo ' . str_pad((string) $i, 5, '0', STR_PAD_LEFT) : null,
                'Telefono_Empresa' => $esEmpresa ? $this->telefono($i + 2000) : null,
                'Direccion_Empresa' => $esEmpresa ? 'Zona comercial, ' . $municipio : null,
                'Correo_Empresa' => $esEmpresa ? 'proveedor' . $i . '@empresa.test' : null,
                'Estado' => $i % 41 === 0 ? 0 : 1,
                'Nacionalidad' => $i % 3 === 0 ? 'Nicaragua' : ($i % 3 === 1 ? 'Costa Rica' : 'Honduras'),
                'Codigo_Ruc' => 'RUC-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
            ];

            if (count($proveedores) >= self::CHUNK) {
                $this->flushInsert('persona', $personas);
                DB::table('proveedor')->insert($proveedores);
                $personas = [];
                $proveedores = [];
            }
        }

        $this->flushInsert('persona', $personas);
        $this->flushInsert('proveedor', $proveedores);
    }

    private function crearTrabajadoresYUsuarios(): void
    {
        $personas = [];
        $trabajadores = [];
        $usuarios = [];
        $password = Hash::make(self::PASSWORD_DEMO);
        $inicioPersona = (self::CANTIDAD * 2) + 2;

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $idPersona = $inicioPersona + $i - 1;
            $idTrabajador = $i + 1;
            $idUsuario = $i + 1;

            $personas[] = $this->personaRow($idPersona, $i, 'Trabajador');

            $trabajadores[] = [
                'Id_Trabajador' => $idTrabajador,
                'Id_Persona' => $idPersona,
                'Fecha_Ingreso' => $this->fecha($i, '2023-01-01', false),
                'Fecha_Salida' => null,
                'Motivo_Salida' => null,
                'Estado' => 1,
                'Id_Cargo' => ($i % 4) + 1,
                'Cedula' => $this->cedula($i),
                'Salario' => mt_rand(9000, 50000),
            ];

            $usuarios[] = [
                'Id_Usuario' => $idUsuario,
                'Id_Trabajador' => $idTrabajador,
                'Nombre_Usuario' => 'user' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'Contraseña_Usuario' => $password,
                'Estado' => 1,
                'Token_Recuperacion' => null,
                'Fecha_Recuperacion' => null,
                'Intentos_Fallidos' => 0,
                'Correo' => 'user' . $i . '@gnet.local',
            ];

            if (count($personas) >= self::CHUNK) {
                DB::table('persona')->insert($personas);
                DB::table('trabajador')->insert($trabajadores);
                DB::table('usuario')->insert($usuarios);
                $personas = [];
                $trabajadores = [];
                $usuarios = [];
            }
        }

        $this->flushInsert('persona', $personas);
        $this->flushInsert('trabajador', $trabajadores);
        $this->flushInsert('usuario', $usuarios);
    }

    private function crearClientesCredito(): void
    {
        $rows = [];

        for ($i = 1; $i <= 5000; $i++) {
            $clienteId = 5000 + $i;

            $rows[] = [
                'Id_Cliente_Credito' => $i,
                'Id_Cliente' => $clienteId,
                'Saldo_Actual' => mt_rand(0, 95000),
                'Estado' => $i % 100 === 0 ? 'BLOQUEADO' : 'ACTIVO',
                'Fecha_Registro' => $this->fecha($i, '2025-01-01'),
            ];

            if (count($rows) >= self::CHUNK) {
                DB::table('cliente_credito')->insert($rows);
                $rows = [];
            }
        }

        $this->flushInsert('cliente_credito', $rows);
    }

    private function crearProductosYSeries(): void
    {
        $productos = [];
        $series = [];
        $serieId = 1;

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $categoriaId = (($i - 1) % 16) + 1;
            $marcaId = (($i - 1) % 30) + 1;
            $usaSerie = $i % 3 !== 0;
            $precio = mt_rand(250, 50000);
            $stock = $usaSerie ? 2 : mt_rand(10, 100);

            $productos[] = [
                'Id_Producto' => $i,
                'Id_Categoria' => $categoriaId,
                'Id_Marca' => $marcaId,
                'Nombre_Producto' => $this->productosBase[$i % count($this->productosBase)] . ' ' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'Modelo' => 'MDL-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'Stock_Actual' => $stock,
                'Stock_Minimo' => $usaSerie ? 1 : 5,
                'Precio_Venta' => $precio,
                'Meses_Garantia_Nuevo' => $usaSerie ? 12 : 3,
                'Meses_Garantia_Usado' => $usaSerie ? 4 : 1,
                'Estado' => $i % 97 === 0 ? 0 : 1,
                'Fecha_Vencimiento' => null,
            ];

            if ($usaSerie) {
                $this->productosConSerie[] = $i;
                $this->seriesPorProducto[$i] = [];

                for ($s = 1; $s <= 2; $s++) {
                    $estado = $s === 1 ? 'DISPONIBLE' : ($i % 5 === 0 ? 'VENDIDO' : 'RESERVADO');

                    $series[] = [
                        'id_producto_serie' => $serieId,
                        'Id_Producto' => $i,
                        'Numero_Serie' => 'SER-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT) . '-' . $s,
                        'Fecha_Ingreso' => $this->fecha($i + $s, '2025-01-01'),
                        'Estado' => $estado,
                        'Observacion' => $estado === 'DISPONIBLE' ? null : 'Serie generada para datos demo',
                    ];

                    $this->seriesPorProducto[$i][] = $serieId;
                    $serieId++;
                }
            } else {
                $this->productosSinSerie[] = $i;
            }

            if (count($productos) >= self::CHUNK) {
                DB::table('producto')->insert($productos);

                foreach (array_chunk($series, self::CHUNK) as $chunkSeries) {
                    DB::table('producto_serie')->insert($chunkSeries);
                }

                $productos = [];
                $series = [];
            }
        }

        $this->flushInsert('producto', $productos);

        foreach (array_chunk($series, self::CHUNK) as $chunkSeries) {
            DB::table('producto_serie')->insert($chunkSeries);
        }
    }

    private function crearTasasCambio(): void
    {
        $rows = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $rows[] = [
                'Id_Tasa_Cambio' => $i,
                'Valor_Cambio' => 36 + (($i % 80) / 100),
                'Fecha_Modificacion' => $this->fecha($i, '2024-01-01'),
            ];

            if (count($rows) >= self::CHUNK) {
                DB::table('tasa_cambio')->insert($rows);
                $rows = [];
            }
        }

        $this->flushInsert('tasa_cambio', $rows);
    }

    private function crearCompras(): void
    {
        $compras = [];
        $detalles = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $productoId = (($i - 1) % self::CANTIDAD) + 1;
            $cantidad = ($i % 7) + 1;
            $precioCompra = mt_rand(100, 30000);
            $subtotal = $cantidad * $precioCompra;
            $iva = round($subtotal * 0.15, 2);
            $retencion = $i % 5 === 0 ? round($subtotal * 0.02, 2) : 0;
            $total = $subtotal + $iva - $retencion;
            $medioPago = $i % 3 === 0 ? 'TRANSFERENCIA' : 'EFECTIVO';

            $compras[] = [
                'Id_Compra' => $i,
                'Numero_Compra' => 'COMP-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'Fecha_Compra' => $this->fecha($i, '2025-01-01'),
                'Id_Proveedor' => (($i - 1) % self::CANTIDAD) + 1,
                'Id_Usuario' => (($i - 1) % self::CANTIDAD) + 2,
                'Tipo_Compra' => $i % 4 === 0 ? 'CREDITO' : 'CONTADO',
                'Fecha_Limite_Credito' => $i % 4 === 0 ? $this->fecha($i + 30, '2025-01-01', false) : null,
                'Medio_Pago' => $medioPago,
                'Id_Cuenta_Bancaria' => $medioPago === 'TRANSFERENCIA' ? (($i - 1) % 3) + 1 : null,
                'Numero_Referencia_Transferencia' => $medioPago === 'TRANSFERENCIA' ? 'REF-COMP-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT) : null,
                'Total' => $total,
                'Observacion' => 'Compra demo generada automáticamente.',
                'Id_producto' => $productoId,
                'Retencion' => (int) $retencion,
                'Iva' => $iva,
            ];

            $detalles[] = [
                'Id_Detalle_Compra' => $i,
                'Id_Compra' => $i,
                'Id_Producto' => $productoId,
                'Cantidad' => $cantidad,
                'Subtotal' => $subtotal,
                'Precio_Compra' => $precioCompra,
                'Meses_Garantia_Proveedor' => ($i % 12) + 1,
            ];

            if (count($compras) >= self::CHUNK) {
                DB::table('compra')->insert($compras);
                DB::table('detalle_compra')->insert($detalles);
                $compras = [];
                $detalles = [];
            }
        }

        $this->flushInsert('compra', $compras);
        $this->flushInsert('detalle_compra', $detalles);
    }

    private function crearVentasCreditosYPagos(): void
    {
        $ventas = [];
        $detalles = [];
        $pagos = [];
        $creditos = [];
        $abonos = [];
        $movimientosCredito = [];
        $detalleId = 1;
        $abonoId = 1;
        $movimientoCreditoId = 1;

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $esCredito = $i % 2 === 0;
            $clienteId = $esCredito ? 5001 + (($i - 1) % 5000) : (($i - 1) % 5000) + 1;
            $usuarioId = (($i - 1) % self::CANTIDAD) + 2;
            $productoId = (($i - 1) % self::CANTIDAD) + 1;
            $precioProducto = mt_rand(300, 50000);
            $cantidadProducto = $this->productoTieneSerie($productoId) ? 1 : (($i % 5) + 1);
            $subtotalProducto = $cantidadProducto * $precioProducto;
            $tarifaId = (($i - 1) % 8) + 1;
            $cantidadCopia = (($i % 20) + 1) * 10;
            $precioCopia = $this->precioTarifa($tarifaId);
            $subtotalCopia = $cantidadCopia * $precioCopia;
            $descuento = $i % 10 === 0 ? 100 : 0;
            $total = $subtotalProducto + $subtotalCopia - $descuento;
            $fecha = $this->fecha($i, '2025-01-01');
            $serieId = $this->serieDisponibleParaProducto($productoId, $i);

            $ventas[] = [
                'Id_Venta' => $i,
                'Numero_Factura' => 'F-DEMO-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'Fecha_venta' => $fecha,
                'Id_Cliente' => $clienteId,
                'Id_Usuario' => $usuarioId,
                'Tipo_Venta' => $esCredito ? 'CREDITO' : 'CONTADO',
                'Estado' => 1,
                'Descuento' => $descuento,
                'Total' => $total,
                'Tipo_Cambio' => 36.5,
                'Cambio_Entregado_Cordobas' => 0,
            ];

            $detalles[] = [
                'Id_Detalle_Venta' => $detalleId++,
                'Id_Venta' => $i,
                'Tipo_Detalle' => 'PRODUCTO',
                'Id_Producto' => $productoId,
                'Id_Producto_serie' => $serieId,
                'Id_Servicio' => null,
                'Id_Tarifa_Copia' => null,
                'Nombre_Formato' => null,
                'Formato_Copia' => null,
                'Lados_Copia' => null,
                'Cantidad' => $cantidadProducto,
                'Precio_Unitario' => $precioProducto,
                'Subtotal' => $subtotalProducto,
                'Observacion' => 'Producto vendido desde seeder demo.',
                'Descuento' => 0,
            ];

            $detalles[] = [
                'Id_Detalle_Venta' => $detalleId++,
                'Id_Venta' => $i,
                'Tipo_Detalle' => 'COPIA',
                'Id_Producto' => null,
                'Id_Producto_serie' => null,
                'Id_Servicio' => 1,
                'Id_Tarifa_Copia' => $tarifaId,
                'Nombre_Formato' => $this->nombreTarifa($tarifaId),
                'Formato_Copia' => $tarifaId <= 4 ? 1 : 2,
                'Lados_Copia' => in_array($tarifaId, [2, 4, 6, 8], true) ? 2 : 1,
                'Cantidad' => $cantidadCopia,
                'Precio_Unitario' => $precioCopia,
                'Subtotal' => $subtotalCopia,
                'Observacion' => 'Copias generadas desde seeder demo.',
                'Descuento' => 0,
            ];

            $pagos[] = [
                'Id_Pago_Venta' => $i,
                'Id_Venta' => $i,
                'Fecha_Pago' => $fecha,
                'Moneda' => $i % 4 === 0 ? 1 : 0,
                'Tipo_Pago' => $i % 3 === 0 ? 'TRANSFERENCIA' : 'EFECTIVO',
                'Numero_Referencia' => $i % 3 === 0 ? 'PV-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT) : null,
                'Monto' => $esCredito ? round($total * 0.30, 2) : $total,
                'Tipo_Cambio' => $i % 4 === 0 ? 36.5 : 1,
                'Monto_Equivalente_Cordobas' => $esCredito ? round($total * 0.30, 2) : $total,
            ];

            if ($esCredito) {
                $creditoId = (int) ($i / 2);
                $clienteCreditoId = $clienteId - 5000;
                $abonoInicial = round($total * 0.30, 2);
                $saldo = $total - $abonoInicial;

                $creditos[] = [
                    'Id_Credito' => $creditoId,
                    'Id_Cliente_Credito' => $clienteCreditoId,
                    'Id_Venta' => $i,
                    'Fecha_Credito' => Carbon::parse($fecha)->format('Y-m-d'),
                    'Abono_Inicial' => $abonoInicial,
                    'Saldo_Actual' => $saldo,
                    'Firma_Recibido' => 'Cliente Institucional Demo',
                    'Estado' => $saldo <= 0 ? 'CANCELADO' : ($i % 8 === 0 ? 'PARCIAL' : 'PENDIENTE'),
                ];

                for ($a = 1; $a <= 2; $a++) {
                    $montoAbono = round($saldo * ($a === 1 ? 0.15 : 0.10), 2);
                    $abonos[] = [
                        'Id_Abono_Credito' => $abonoId++,
                        'Id_Credito' => $creditoId,
                        'Id_Usuario' => $usuarioId,
                        'Fecha_Abono' => $this->fecha($i + $a * 7, '2025-01-01'),
                        'Moneda' => $a === 1 ? 'NIO' : 'USD',
                        'Monto' => $a === 1 ? $montoAbono : round($montoAbono / 36.5, 2),
                        'Numero_Transferencia' => null,
                        'Observacion' => 'Abono demo generado automáticamente.',
                        'Tipo_Cambio' => $a === 1 ? 1 : 36.5,
                        'Monto_Equivalente_Cordobas' => $montoAbono,
                    ];
                }

                $movimientosCredito[] = [
                    'Id_Movimiento' => $movimientoCreditoId++,
                    'Id_Cliente_Credito' => $clienteCreditoId,
                    'Id_Cliente' => $clienteId,
                    'Id_Venta' => $i,
                    'Id_Credito' => $creditoId,
                    'Tipo_Movimiento' => 'CARGO',
                    'Monto' => $saldo,
                    'Saldo_Anterior' => 0,
                    'Saldo_Despues' => $saldo,
                    'Fecha_Movimiento' => $fecha,
                    'Observacion' => 'Cargo por venta a crédito demo.',
                ];

                $movimientosCredito[] = [
                    'Id_Movimiento' => $movimientoCreditoId++,
                    'Id_Cliente_Credito' => $clienteCreditoId,
                    'Id_Cliente' => $clienteId,
                    'Id_Venta' => $i,
                    'Id_Credito' => $creditoId,
                    'Tipo_Movimiento' => 'ABONO',
                    'Monto' => $abonoInicial,
                    'Saldo_Anterior' => $saldo,
                    'Saldo_Despues' => max(0, $saldo - $abonoInicial),
                    'Fecha_Movimiento' => $fecha,
                    'Observacion' => 'Abono inicial demo.',
                ];
            }

            if (count($ventas) >= self::CHUNK) {
                DB::table('venta')->insert($ventas);
                DB::table('detalle_venta')->insert($detalles);
                DB::table('pago_venta')->insert($pagos);
                $ventas = [];
                $detalles = [];
                $pagos = [];
            }

            if (count($creditos) >= self::CHUNK) {
                DB::table('credito')->insert($creditos);
                DB::table('abono_credito')->insert($abonos);
                DB::table('cliente_credito_movimiento')->insert($movimientosCredito);
                $creditos = [];
                $abonos = [];
                $movimientosCredito = [];
            }
        }

        $this->flushInsert('venta', $ventas);
        $this->flushInsert('detalle_venta', $detalles);
        $this->flushInsert('pago_venta', $pagos);
        $this->flushInsert('credito', $creditos);
        $this->flushInsert('abono_credito', $abonos);
        $this->flushInsert('cliente_credito_movimiento', $movimientosCredito);
    }

    private function crearServiciosTecnicos(): void
    {
        $servicios = [];
        $checklists = [];
        $productos = [];
        $pagos = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $productoId = (($i - 1) % self::CANTIDAD) + 1;
            $serieId = $this->serieDisponibleParaProducto($productoId, $i);
            $cantidad = $serieId ? 1 : (($i % 3) + 1);
            $precio = mt_rand(250, 12000);
            $repuestos = $cantidad * $precio;
            $servicioBase = mt_rand(400, 5000);
            $total = $repuestos + $servicioBase;
            $fecha = $this->fecha($i, '2025-02-01');

            $servicios[] = [
                'Id_Servicio_Tecnico' => $i,
                'Id_Venta' => null,
                'Numero_Orden' => 'ST-DEMO-' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'Fecha_Ingreso' => $fecha,
                'Id_Cliente' => (($i - 1) % self::CANTIDAD) + 1,
                'Id_Usuario' => (($i - 1) % self::CANTIDAD) + 2,
                'Id_Servicio' => 2,
                'Id_Trabajador' => (($i - 1) % self::CANTIDAD) + 2,
                'Tipo_Equipo' => $i % 2 === 0 ? 'Laptop' : 'Impresora',
                'Marca' => 'Marca Demo ' . (($i % 30) + 1),
                'Modelo' => 'EQ-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'Numero_Serie' => 'EQ-SER-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'Problema_Reportado' => 'Falla demo reportada por el cliente.',
                'Detalle_Descriptivo' => 'Diagnóstico generado automáticamente para pruebas.',
                'Estado_Servicio' => ['RECIBIDO', 'EN_REVISION', 'PENDIENTE_REPUESTO', 'REPARADO', 'ENTREGADO'][$i % 5],
                'Costo_Estimado' => $total,
                'Fecha_Estimada_Entrega' => $this->fecha($i + 7, '2025-02-01', false),
                'Observacion_Tecnica' => 'Observación técnica demo.',
                'Total_Repuestos' => $repuestos,
                'Total_Servicio' => $total,
                'Tipo_Venta' => $i % 4 === 0 ? 'CREDITO' : 'CONTADO',
                'Tipo_Cambio' => 36.5,
                'Monto_Pagado' => $i % 4 === 0 ? round($total * 0.25, 2) : $total,
                'Saldo_Pendiente' => $i % 4 === 0 ? round($total * 0.75, 2) : 0,
                'Cambio_Entregado_Cordobas' => 0,
            ];

            $checklists[] = [
                'Id_Servicio_Tecnico_Checklist' => $i,
                'Id_Servicio_Tecnico' => $i,
                'Enciende' => $i % 2,
                'Lleva_Cargador' => 1,
                'Lleva_Bateria' => $i % 3 !== 0 ? 1 : 0,
                'Pantalla_Sana' => $i % 5 !== 0 ? 1 : 0,
                'Teclado_Completo' => 1,
                'Touchpad_Funcional' => $i % 4 !== 0 ? 1 : 0,
                'Tiene_Golpes_Visibles' => $i % 6 === 0 ? 1 : 0,
                'Tiene_Humedad' => $i % 11 === 0 ? 1 : 0,
                'Tiene_Sello_Roto' => $i % 9 === 0 ? 1 : 0,
                'Lleva_Cable_Poder' => 1,
                'Lleva_Cartucho_Toner' => $i % 2 === 0 ? 1 : 0,
                'Lleva_Mouse_Accesorios' => $i % 3 === 0 ? 1 : 0,
                'Observacion_Checklist' => 'Checklist demo.',
            ];

            $productos[] = [
                'Id_Servicio_Tecnico_Producto' => $i,
                'Id_Servicio_Tecnico' => $i,
                'Id_Producto' => $productoId,
                'Id_Producto_Serie' => $serieId,
                'Cantidad' => $cantidad,
                'Precio_Unitario' => $precio,
                'Subtotal' => $repuestos,
                'Observacion' => 'Repuesto usado en servicio técnico demo.',
            ];

            $pagos[] = [
                'Id_Pago_Servicio_Tecnico' => $i,
                'Id_Servicio_Tecnico' => $i,
                'Fecha_Pago' => $fecha,
                'Moneda' => $i % 5 === 0 ? 'USD' : 'NIO',
                'Tipo_Pago' => $i % 3 === 0 ? 'TRANSFERENCIA' : 'EFECTIVO',
                'Numero_Referencia' => $i % 3 === 0 ? 'PST-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT) : null,
                'Monto' => $i % 4 === 0 ? round($total * 0.25, 2) : $total,
                'Tipo_Cambio' => $i % 5 === 0 ? 36.5 : 1,
                'Monto_Equivalente_Cordobas' => $i % 4 === 0 ? round($total * 0.25, 2) : $total,
                'Observacion' => 'Pago demo de servicio técnico.',
            ];

            if (count($servicios) >= self::CHUNK) {
                DB::table('servicio_tecnico')->insert($servicios);
                DB::table('servicio_tecnico_checklist')->insert($checklists);
                DB::table('servicio_tecnico_producto')->insert($productos);
                DB::table('pago_servicio_tecnico')->insert($pagos);
                $servicios = [];
                $checklists = [];
                $productos = [];
                $pagos = [];
            }
        }

        $this->flushInsert('servicio_tecnico', $servicios);
        $this->flushInsert('servicio_tecnico_checklist', $checklists);
        $this->flushInsert('servicio_tecnico_producto', $productos);
        $this->flushInsert('pago_servicio_tecnico', $pagos);
    }

    private function crearContratosInstalacion(): void
    {
        $contratos = [];
        $checklists = [];
        $productos = [];
        $pagos = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $productoId = (($i + 300) % self::CANTIDAD) + 1;
            $serieId = $this->serieDisponibleParaProducto($productoId, $i);
            $cantidad = $serieId ? 1 : (($i % 6) + 1);
            $precio = mt_rand(500, 18000);
            $materiales = $cantidad * $precio;
            $manoObra = mt_rand(1500, 9000);
            $total = $materiales + $manoObra;
            $anticipo = round($total * 0.30, 2);
            $fecha = $this->fecha($i, '2025-03-01');

            $contratos[] = [
                'Id_Contrato_Instalacion_Camara' => $i,
                'Id_Venta' => null,
                'Numero_Contrato' => 'IC-DEMO-' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'Fecha_Contrato' => $fecha,
                'Id_Cliente' => (($i - 1) % self::CANTIDAD) + 1,
                'Id_Usuario' => (($i - 1) % self::CANTIDAD) + 2,
                'Id_Servicio' => 3,
                'Id_Trabajador' => (($i - 1) % self::CANTIDAD) + 2,
                'Municipio' => $this->municipios[$i % count($this->municipios)],
                'Direccion_Instalacion' => 'Dirección demo de instalación #' . $i,
                'Cantidad_Camaras' => ($i % 8) + 1,
                'Metros_Cableado' => ($i % 90) + 10,
                'Costo_Mano_Obra' => $manoObra,
                'Porcentaje_Anticipo' => 30,
                'Monto_Anticipo' => $anticipo,
                'Tipo_Cambio' => 36.5,
                'Monto_Pagado' => $anticipo,
                'Cambio_Entregado_Cordobas' => 0,
                'Fecha_Estimada' => $this->fecha($i + 10, '2025-03-01', false),
                'Detalle_Contrato' => 'Contrato demo generado para pruebas de instalación.',
                'Estado_Contrato' => ['PENDIENTE', 'EN_PROCESO', 'FINALIZADO', 'CANCELADO'][$i % 4],
                'Total_Materiales' => $materiales,
                'Total_Contrato' => $total,
                'Saldo_Pendiente' => $total - $anticipo,
                'Tipo_Venta' => $i % 4 === 0 ? 'CREDITO' : 'CONTADO',
            ];

            $checklists[] = [
                'Id_Contrato_Instalacion_Camara_Checklist' => $i,
                'Id_Contrato_Instalacion_Camara' => $i,
                'Incluye_Instalacion_Fisica' => 1,
                'Incluye_Configuracion_App' => 1,
                'Incluye_Pruebas_Sistema' => 1,
                'Incluye_Capacitacion_Basica' => $i % 3 === 0 ? 1 : 0,
                'Incluye_Garantia' => 1,
                'Anticipo_Recibido' => 1,
                'Contrato_Firmado' => $i % 5 !== 0 ? 1 : 0,
                'Cliente_Aprueba_Recorrido' => $i % 4 !== 0 ? 1 : 0,
                'Sistema_Energizado' => $i % 6 !== 0 ? 1 : 0,
                'Observacion_Checklist' => 'Checklist demo de instalación.',
            ];

            $productos[] = [
                'Id_Contrato_Instalacion_Camara_Producto' => $i,
                'Id_Contrato_Instalacion_Camara' => $i,
                'Id_Producto' => $productoId,
                'Id_Producto_Serie' => $serieId,
                'Cantidad' => $cantidad,
                'Precio_Unitario' => $precio,
                'Subtotal' => $materiales,
                'Observacion' => 'Material demo usado en instalación.',
            ];

            $pagos[] = [
                'Id_Pago_Contrato_Instalacion_Camara' => $i,
                'Id_Contrato_Instalacion_Camara' => $i,
                'Fecha_Pago' => $fecha,
                'Moneda' => $i % 5 === 0 ? 'USD' : 'NIO',
                'Tipo_Pago' => $i % 3 === 0 ? 'TRANSFERENCIA' : 'EFECTIVO',
                'Numero_Referencia' => $i % 3 === 0 ? 'PCI-' . str_pad((string) $i, 8, '0', STR_PAD_LEFT) : null,
                'Monto' => $anticipo,
                'Tipo_Cambio' => $i % 5 === 0 ? 36.5 : 1,
                'Monto_Equivalente_Cordobas' => $anticipo,
                'Observacion' => 'Pago demo de instalación.',
            ];

            if (count($contratos) >= self::CHUNK) {
                DB::table('contrato_instalacion_camara')->insert($contratos);
                DB::table('contrato_instalacion_camara_checklist')->insert($checklists);
                DB::table('contrato_instalacion_camara_producto')->insert($productos);
                DB::table('pago_contrato_instalacion_camara')->insert($pagos);
                $contratos = [];
                $checklists = [];
                $productos = [];
                $pagos = [];
            }
        }

        $this->flushInsert('contrato_instalacion_camara', $contratos);
        $this->flushInsert('contrato_instalacion_camara_checklist', $checklists);
        $this->flushInsert('contrato_instalacion_camara_producto', $productos);
        $this->flushInsert('pago_contrato_instalacion_camara', $pagos);
    }

    private function crearCajaArqueosYEgresos(): void
    {
        $aperturas = [];
        $arqueos = [];
        $detalles = [];
        $egresos = [];
        $movimientos = [];
        $tiposMovimientoCaja = [
            'INGRESO_VENTA',
            'ABONO_CREDITO',
            'EGRESO',
            'APERTURA',
            'CIERRE',
        ];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $fecha = $this->fecha($i, '2025-01-01');
            $usuarioId = (($i - 1) % self::CANTIDAD) + 2;
            $cordobas = mt_rand(1000, 120000);
            $dolares = mt_rand(0, 2500);

            $aperturas[] = [
                'Id_Apertura_Caja' => $i,
                'Id_Usuario' => $usuarioId,
                'Monto_Apertura' => mt_rand(1000, 10000),
                'Fecha_Apertura' => $fecha,
                'Estado_Apertura' => $i % 2 === 0 ? 'CERRADA' : 'ABIERTA',
                'Numero_Caja' => ($i % 5) + 1,
            ];

            $arqueos[] = [
                'Id_Arqueo' => $i,
                'Id_Usuario' => $usuarioId,
                'Id_Apertura_Caja' => $i,
                'Total_Caja_Cordoba' => $cordobas,
                'Total_Caja_Dolar' => $dolares,
                'Fecha_Arqueo' => $fecha,
            ];

            $detalles[] = [
                'Id_Detalle_Arqueo' => $i,
                'Id_Arqueo' => $i,
                'Faltante_Cordoba' => $i % 4 === 0 ? mt_rand(10, 500) : 0,
                'Faltante_Dolar' => $i % 5 === 0 ? mt_rand(1, 20) : 0,
                'Sobrante_Cordoba' => $i % 6 === 0 ? mt_rand(10, 800) : 0,
                'Sobrante_Dolar' => $i % 7 === 0 ? mt_rand(1, 30) : 0,
                'Cantidad_Egresada_Cordoba' => $i % 3 === 0 ? mt_rand(100, 3000) : 0,
                'Cantidad_Egresada_Dolar' => $i % 8 === 0 ? mt_rand(1, 80) : 0,
                'Estado_Arqueo' => $i % 2 === 0 ? 'CUADRADO' : 'CON_DIFERENCIA',
            ];

            $egresos[] = [
                'Id_Egreso' => $i,
                'Id_Apertura_Caja' => $i,
                'Id_Usuario' => $usuarioId,
                'Monto_Egresado_Cordoba' => $i % 2 === 0 ? mt_rand(100, 2500) : null,
                'Monto_Egresado_Dolar' => $i % 2 !== 0 ? mt_rand(1, 100) : null,
                'Motivo_Egreso' => ['Compra menor', 'Transporte', 'Papelería', 'Mantenimiento'][$i % 4],
                'Descripcion_Egreso' => 'Egreso demo generado automáticamente.',
                'Fecha_Egreso' => $fecha,
            ];

            $movimientos[] = [
                'Id_Movimiento_caja' => $i,
                'Fecha_Movimiento' => $fecha,
                'Id_Usuario' => $usuarioId,
                'Tipo_Movimiento' => $tiposMovimientoCaja[$i % count($tiposMovimientoCaja)],
                'Moneda' => $i % 2 === 0 ? 'NIO' : 'USD',
                'Monto' => mt_rand(100, 25000),
                'Motivo' => 'Movimiento de caja demo.',
            ];

            if (count($aperturas) >= self::CHUNK) {
                DB::table('apertura_caja')->insert($aperturas);
                DB::table('arqueo_caja')->insert($arqueos);
                DB::table('detalle_arqueo')->insert($detalles);
                DB::table('egreso')->insert($egresos);
                DB::table('movimiento_caja')->insert($movimientos);
                $aperturas = [];
                $arqueos = [];
                $detalles = [];
                $egresos = [];
                $movimientos = [];
            }
        }

        $this->flushInsert('apertura_caja', $aperturas);
        $this->flushInsert('arqueo_caja', $arqueos);
        $this->flushInsert('detalle_arqueo', $detalles);
        $this->flushInsert('egreso', $egresos);
        $this->flushInsert('movimiento_caja', $movimientos);
    }

    private function crearPlanillasPagosVacaciones(): void
    {
        $planillas = [];
        $detalles = [];
        $pagos = [];
        $incentivos = [];
        $deducciones = [];
        $vacaciones = [];
        $movimientosVacacion = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $trabajadorId = (($i - 1) % self::CANTIDAD) + 2;
            $inicio = Carbon::create(2025, 1, 1)->addDays(($i % 24) * 15);
            $fin = (clone $inicio)->addDays(14)->endOfDay();
            $salario = mt_rand(9000, 50000);
            $incentivo = $i % 4 === 0 ? mt_rand(500, 4000) : 0;
            $deduccion = $i % 5 === 0 ? mt_rand(300, 2500) : 0;
            $vacacionMonto = $i % 9 === 0 ? round($salario / 30 * 5, 2) : 0;
            $aguinaldo = $i % 12 === 0 ? round($salario / 12, 2) : 0;
            $bruto = $salario + $incentivo + $vacacionMonto + $aguinaldo;
            $neto = $bruto - $deduccion;

            $planillas[] = [
                'Id_Planilla' => $i,
                'Fecha_Inicio_Corte' => $inicio->format('Y-m-d H:i:s'),
                'Fecha_Fin_Corte' => $fin->format('Y-m-d H:i:s'),
                'Fecha_Generacion' => $this->fecha($i, '2025-01-15'),
                'Tipo_Planilla' => ['NORMAL', 'AGUINALDO', 'VACACIONES', 'LIQUIDACION'][$i % 4],
                'Estado' => ['BORRADOR', 'CALCULADA', 'PAGADA', 'ANULADA'][$i % 4],
                'Total_Bruto' => $bruto,
                'Total_Incentivos' => $incentivo,
                'Total_Vacaciones' => $vacacionMonto,
                'Total_Aguinaldo' => $aguinaldo,
                'Total_Indemnizacion' => 0,
                'Total_Deducciones' => $deduccion,
                'Total_Neto' => $neto,
                'Observacion' => 'Planilla demo generada automáticamente.',
            ];

            $detalles[] = [
                'Id_Detalle_Planilla' => $i,
                'Id_Planilla' => $i,
                'Id_Trabajador' => $trabajadorId,
                'Salario_Base' => $salario,
                'Dias_Trabajados' => 15,
                'Dias_Vacaciones' => $vacacionMonto > 0 ? 5 : 0,
                'Monto_Vacaciones' => $vacacionMonto,
                'Monto_Incentivo' => $incentivo,
                'Monto_Aguinaldo' => $aguinaldo,
                'Monto_Indemnizacion' => 0,
                'Monto_Deduccion' => $deduccion,
                'Total_Bruto' => $bruto,
                'Total_Neto' => $neto,
                'Estado_Pago' => $i % 3 === 0 ? 'PAGADO' : 'PENDIENTE',
                'Fecha_Pago' => $i % 3 === 0 ? $this->fecha($i + 1, '2025-01-15') : null,
                'Observacion' => 'Detalle demo.',
            ];

            $pagos[] = [
                'Id_Pago_Planilla' => $i,
                'Id_Detalle_Planilla' => $i,
                'Fecha_Pago' => $this->fecha($i + 1, '2025-01-15'),
                'Monto_Pagado' => $neto,
                'Metodo_Pago' => $i % 2 === 0 ? 'TRANSFERENCIA' : 'EFECTIVO',
                'Observacion' => 'Pago planilla demo.',
            ];

            $incentivos[] = [
                'Id_Incentivo' => $i,
                'Id_Trabajador' => $trabajadorId,
                'Id_Detalle_Planilla' => $i,
                'Fecha_Incentivo' => $this->fecha($i, '2025-01-01'),
                'Concepto' => 'Bono productividad demo',
                'Monto' => $incentivo,
                'Estado' => $incentivo > 0 ? 'APLICADO' : 'PENDIENTE',
                'Observacion' => 'Incentivo demo.',
            ];

            $deducciones[] = [
                'Id_Deduccion' => $i,
                'Id_Trabajador' => $trabajadorId,
                'Id_Detalle_Planilla' => $i,
                'Fecha_Deduccion' => $this->fecha($i, '2025-01-01'),
                'Concepto' => 'Deducción demo',
                'Monto' => $deduccion,
                'Estado' => $deduccion > 0 ? 'APLICADA' : 'PENDIENTE',
                'Observacion' => 'Deducción demo.',
            ];

            $fechaInicioVac = Carbon::create(2025, 1, 1)->addDays($i % 365);
            $vacaciones[] = [
                'Id_Vacacion' => $i,
                'Id_Trabajador' => $trabajadorId,
                'Id_Detalle_Planilla' => $i,
                'Fecha_Inicio' => $fechaInicioVac->format('Y-m-d'),
                'Fecha_Fin' => (clone $fechaInicioVac)->addDays(4)->format('Y-m-d'),
                'Dias_Tomados' => 5,
                'Estado' => ['PENDIENTE', 'APROBADA', 'PAGADA', 'ANULADA', 'RECHAZADA'][$i % 5],
                'Observacion' => 'Vacación demo.',
            ];

            $movimientosVacacion[] = [
                'Id_Movimiento_Vacacion' => $i,
                'Id_Trabajador' => $trabajadorId,
                'Id_Vacacion' => $i,
                'Id_Detalle_Planilla' => $i,
                'Fecha_Movimiento' => $this->fecha($i, '2025-01-01', false),
                'Tipo_Movimiento' => ['ACUMULACION', 'TOMADA', 'PAGADA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'][$i % 5],
                'Dias' => 5,
                'Observacion' => 'Movimiento vacación demo.',
            ];

            if (count($planillas) >= self::CHUNK) {
                DB::table('planilla')->insert($planillas);
                DB::table('detalle_planilla')->insert($detalles);
                DB::table('pago_planilla')->insert($pagos);
                DB::table('incentivo_trabajador')->insert($incentivos);
                DB::table('deduccion_trabajador')->insert($deducciones);
                DB::table('vacaciones')->insert($vacaciones);
                DB::table('movimiento_vacacion')->insert($movimientosVacacion);
                $planillas = [];
                $detalles = [];
                $pagos = [];
                $incentivos = [];
                $deducciones = [];
                $vacaciones = [];
                $movimientosVacacion = [];
            }
        }

        $this->flushInsert('planilla', $planillas);
        $this->flushInsert('detalle_planilla', $detalles);
        $this->flushInsert('pago_planilla', $pagos);
        $this->flushInsert('incentivo_trabajador', $incentivos);
        $this->flushInsert('deduccion_trabajador', $deducciones);
        $this->flushInsert('vacaciones', $vacaciones);
        $this->flushInsert('movimiento_vacacion', $movimientosVacacion);
    }

    private function crearDevoluciones(): void
    {
        $devoluciones = [];
        $detalles = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $monto = mt_rand(200, 50000);
            $productoCambio = (($i + 900) % self::CANTIDAD) + 1;

            $devoluciones[] = [
                'Id_Devolucion' => $i,
                'Id_Venta' => (($i - 1) % self::CANTIDAD) + 1,
                'Id_Cliente' => (($i - 1) % self::CANTIDAD) + 1,
                'Id_Usuario' => (($i - 1) % self::CANTIDAD) + 2,
                'Fecha_Devolucion' => $this->fecha($i, '2025-04-01'),
                'Con_Factura' => 1,
                'Observacion' => 'Devolución demo generada automáticamente.',
                'Estado_Devolucion' => 1,
                'Tipo_Devolucion' => $i % 2 === 0 ? 2 : 1,
                'Total_Devolucion' => $monto,
            ];

            $detalles[] = [
                'Id_Detalle_Devolucion' => $i,
                'Id_Devolucion' => $i,
                'Id_Detalle_Venta' => (($i - 1) * 2) + 1,
                'Cantidad' => 1,
                'Monto_Devuelto' => $monto,
                'Motivo_Devolucion' => 'Cambio/devolución demo.',
                'Estado_Producto_Devolucion' => $i % 4,
                'Reintegra_Inventario' => $i % 2,
                'Id_Producto_Cambio' => $productoCambio,
                'Id_Producto_Serie_Cambio' => $this->serieDisponibleParaProducto($productoCambio, $i),
                'Cantidad_Cambio' => 1,
                'Monto_Cambio' => mt_rand(200, 50000),
            ];

            if (count($devoluciones) >= self::CHUNK) {
                DB::table('devolucion')->insert($devoluciones);
                DB::table('detalle_devolucion')->insert($detalles);
                $devoluciones = [];
                $detalles = [];
            }
        }

        $this->flushInsert('devolucion', $devoluciones);
        $this->flushInsert('detalle_devolucion', $detalles);
    }

    private function crearMovimientosInventario(): void
    {
        $rows = [];
        $tipos = [
            'ENTRADA_COMPRA',
            'SALIDA_VENTA',
            'SALIDA_INSTALACION',
            'SALIDA_SERVICIO_TECNICO',
            'SALIDA_CAMBIO_PRODUCTO',
            'SALIDA_AJUSTE',
            'SALIDA_DANO',
            'SALIDA_DEFECTO',
            'SALIDA_USO_PERSONAL',
            'SALIDA_PERDIDA',
            'SALIDA_MERMA',
            'SALIDA_PROMOCION',
            'SALIDA_DONACION',
        ];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            $productoId = (($i - 1) % self::CANTIDAD) + 1;

            $rows[] = [
                'Id_Movimiento_inventario' => $i,
                'Id_Producto' => $productoId,
                'Id_Producto_Serie' => $this->serieDisponibleParaProducto($productoId, $i),
                'Fecha_Movimiento' => $this->fecha($i, '2025-01-01'),
                'Tipo_Movimiento' => $tipos[$i % count($tipos)],
                'Cantidad' => $this->productoTieneSerie($productoId) ? 1 : (($i % 5) + 1),
                'Motivo_Movimiento' => 'Movimiento inventario demo.',
            ];

            if (count($rows) >= self::CHUNK) {
                DB::table('movimiento_inventario')->insert($rows);
                $rows = [];
            }
        }

        $this->flushInsert('movimiento_inventario', $rows);
    }

    private function ajustarAutoIncrementos(): void
    {
        foreach ([
            'persona' => 30002,
            'trabajador' => 10002,
            'usuario' => 10002,
            'cliente' => 10001,
            'proveedor' => 10001,
            'producto' => 10001,
            'producto_serie' => 30000,
            'venta' => 10001,
            'detalle_venta' => 20001,
            'credito' => 5001,
            'abono_credito' => 10001,
        ] as $table => $nextId) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$nextId}");
        }
    }

    private function personaRow(int $idPersona, int $i, string $grupo): array
    {
        return [
            'Id_Persona' => $idPersona,
            'Primer_Nombre' => $this->nombres[$i % count($this->nombres)],
            'Segundo_Nombre' => $i % 3 === 0 ? $this->nombres[($i + 3) % count($this->nombres)] : null,
            'Primer_Apellido' => $this->apellidos[$i % count($this->apellidos)],
            'Segundo_Apellido' => $i % 4 === 0 ? $this->apellidos[($i + 5) % count($this->apellidos)] : null,
            'Direccion' => $grupo . ' demo #' . $i . ', ' . $this->municipios[$i % count($this->municipios)],
            'Telefono' => $this->telefono($i),
        ];
    }

    private function telefono(int $i): string
    {
        return (string) (80000000 + ($i % 9999999));
    }

    private function cedula(int $i): string
    {
        return str_pad((string) ($i % 999), 3, '0', STR_PAD_LEFT)
            . str_pad((string) ($i % 999999), 6, '0', STR_PAD_LEFT)
            . str_pad((string) ($i % 9999), 4, '0', STR_PAD_LEFT)
            . chr(65 + ($i % 26));
    }

    private function fecha(int $i, string $base, bool $datetime = true): string
    {
        $date = Carbon::parse($base)->addDays($i % 540)->addMinutes(($i * 13) % 1440);

        return $datetime ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
    }

    private function productoTieneSerie(int $productoId): bool
    {
        return isset($this->seriesPorProducto[$productoId]);
    }

    private function serieDisponibleParaProducto(int $productoId, int $seed): ?int
    {
        if (! isset($this->seriesPorProducto[$productoId])) {
            return null;
        }

        $series = $this->seriesPorProducto[$productoId];

        return $series[$seed % count($series)] ?? null;
    }

    private function precioTarifa(int $tarifaId): float
    {
        return [1 => 2, 2 => 3, 3 => 10, 4 => 18, 5 => 3, 6 => 5, 7 => 12, 8 => 22][$tarifaId] ?? 2;
    }

    private function nombreTarifa(int $tarifaId): string
    {
        return [
            1 => 'Fotocopia B/N Carta una cara',
            2 => 'Fotocopia B/N Carta doble cara',
            3 => 'Fotocopia Color Carta una cara',
            4 => 'Fotocopia Color Carta doble cara',
            5 => 'Fotocopia B/N Oficio una cara',
            6 => 'Fotocopia B/N Oficio doble cara',
            7 => 'Fotocopia Color Oficio una cara',
            8 => 'Fotocopia Color Oficio doble cara',
        ][$tarifaId] ?? 'Fotocopia demo';
    }

    private function flushInsert(string $table, array $rows): void
    {
        if ($rows !== []) {
            DB::table($table)->insert($rows);
        }
    }
}
