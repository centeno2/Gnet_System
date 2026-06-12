<?php

namespace App\Services\Ventas;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TCPDF;

class ContratoInstalacionCamaraPdfService
{
    private const COLOR_PRIMARIO = [46, 139, 192];
    private const COLOR_SECUNDARIO = [11, 111, 228];
    private const COLOR_TITULO = [26, 43, 66];
    private const COLOR_TEXTO = [95, 107, 122];
    private const COLOR_BORDE = [215, 228, 243];
    private const COLOR_FONDO = [240, 243, 247];
    private const COLOR_FILA = [247, 249, 252];

    public function generar(int $contratoId): string
    {
        $contrato = $this->contrato($contratoId);
        $materiales = $this->materiales($contratoId);
        $checklist = $this->checklist($contratoId);

        $numeroContrato = $this->texto($contrato->Numero_Contrato ?? 'IC-' . $contratoId);
        $nombreArchivo = 'contrato-instalacion-camaras-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $numeroContrato) . '.pdf';

        $pdf = new class('P', 'mm', 'LETTER', true, 'UTF-8', false) extends TCPDF {
            public function Footer(): void
            {
                $this->SetY(-9);
                $this->SetFont('helvetica', '', 7);
                $this->SetTextColor(95, 107, 122);
                $this->Cell(0, 5, 'Gnet System | Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Contrato de instalación de cámaras');
        $pdf->SetSubject('Contrato de instalación de cámaras');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);
        $pdf->setFontSubsetting(false);
        $pdf->setJPEGQuality(78);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $contrato);
        $this->bloqueDatos($pdf, $contrato);
        $this->resumenEconomico($pdf, $contrato);
        $this->tablaMateriales($pdf, $materiales);
        $this->condicionesServicio($pdf, $checklist);
        $this->clausulas($pdf, $contrato, $materiales);
        $this->firmas($pdf, $contrato);

        return $pdf->Output($nombreArchivo, 'S');
    }

    private function contrato(int $contratoId): object
    {
        $contrato = DB::table('contrato_instalacion_camara as ci')
            ->leftJoin('venta as v', 'v.Id_Venta', '=', 'ci.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'ci.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'ci.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->where('ci.Id_Contrato_Instalacion_Camara', $contratoId)
            ->select([
                'ci.*',
                'v.Numero_Factura',
                'v.Fecha_venta',
                'c.Institucion',
                'c.Tipo_Cliente',
                'c.Telefono_Institucion',
                'c.Municipio as Cliente_Municipio',
                'pc.Primer_Nombre as Cliente_Primer_Nombre',
                'pc.Segundo_Nombre as Cliente_Segundo_Nombre',
                'pc.Primer_Apellido as Cliente_Primer_Apellido',
                'pc.Segundo_Apellido as Cliente_Segundo_Apellido',
                'pc.Telefono as Cliente_Telefono',
                'pt.Primer_Nombre as Tecnico_Primer_Nombre',
                'pt.Segundo_Nombre as Tecnico_Segundo_Nombre',
                'pt.Primer_Apellido as Tecnico_Primer_Apellido',
                'pt.Segundo_Apellido as Tecnico_Segundo_Apellido',
            ])
            ->first();

        if (! $contrato) {
            throw new RuntimeException('No se encontró el contrato de instalación solicitado.');
        }

        return $contrato;
    }

    private function materiales(int $contratoId): Collection
    {
        return DB::table('contrato_instalacion_camara_producto as cp')
            ->join('producto as p', 'p.Id_Producto', '=', 'cp.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'cp.Id_Producto_Serie')
            ->where('cp.Id_Contrato_Instalacion_Camara', $contratoId)
            ->select([
                'cp.Cantidad',
                'cp.Precio_Unitario',
                'cp.Subtotal',
                'cp.Observacion',
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'm.Nombre_Marca',
                'ps.Numero_Serie',
            ])
            ->orderBy('cp.Id_Contrato_Instalacion_Camara_Producto')
            ->get();
    }

    private function checklist(int $contratoId): ?object
    {
        return DB::table('contrato_instalacion_camara_checklist')
            ->where('Id_Contrato_Instalacion_Camara', $contratoId)
            ->first();
    }

    private function encabezado(TCPDF $pdf, object $contrato): void
    {
        [$fr, $fg, $fb] = self::COLOR_FONDO;
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$pr, $pg, $pb] = self::COLOR_PRIMARIO;

        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->Rect(10, 8, 196, 25, 'DF');

        $logo = $this->logoParaPdf();

        if ($logo) {
            $pdf->Image($logo, 14, 11, 18, 18, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetXY($logo ? 38 : 14, 12);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(82, 7, 'GNET SYSTEM', 0, 0, 'L');

        $pdf->SetXY(118, 11);
        $pdf->SetTextColor($pr, $pg, $pb);
        $pdf->SetFont('helvetica', 'B', 12.5);
        $pdf->Cell(84, 7, 'CONTRATO DE INSTALACION', 0, 1, 'R');

        $pdf->SetXY(118, 20);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(84, 5, $this->texto($contrato->Numero_Contrato ?? '—'), 0, 1, 'R');

        $pdf->SetY(38);
    }

    private function bloqueDatos(TCPDF $pdf, object $contrato): void
    {
        $cliente = $this->clienteNombre($contrato);
        $telefono = $this->texto($contrato->Telefono_Institucion ?? $contrato->Cliente_Telefono ?? '—');
        $tecnico = $this->tecnicoNombre($contrato);
        $fechaContrato = $this->fechaTexto($contrato->Fecha_Contrato ?? now());
        $fechaEstimada = $this->fechaTexto($contrato->Fecha_Estimada ?? null, '—');
        $factura = $this->texto($contrato->Numero_Factura ?? '—');
        $municipio = $this->texto($contrato->Municipio ?? $contrato->Cliente_Municipio ?? '—');
        $direccion = $this->texto($contrato->Direccion_Instalacion ?? '—');
        $tipoVenta = $this->texto($contrato->Tipo_Venta ?? 'CONTADO');

        $this->seccionTitulo($pdf, 'Datos principales del contrato');

        $y = $pdf->GetY();
        $this->datoCaja($pdf, 10, $y, 63, 'Cliente', $cliente);
        $this->datoCaja($pdf, 76, $y, 40, 'Teléfono', $telefono);
        $this->datoCaja($pdf, 119, $y, 38, 'Fecha contrato', $fechaContrato);
        $this->datoCaja($pdf, 160, $y, 46, 'Factura', $factura);

        $y += 16;
        $this->datoCaja($pdf, 10, $y, 42, 'Municipio', $municipio);
        $this->datoCaja($pdf, 55, $y, 78, 'Dirección de instalación', $direccion);
        $this->datoCaja($pdf, 136, $y, 34, 'Fecha estimada', $fechaEstimada);
        $this->datoCaja($pdf, 173, $y, 33, 'Tipo', $tipoVenta);

        $y += 16;
        $this->datoCaja($pdf, 10, $y, 63, 'Técnico asignado', $tecnico);
        $this->datoCaja($pdf, 76, $y, 40, 'Cámaras', (string) (int) ($contrato->Cantidad_Camaras ?? 0));
        $this->datoCaja($pdf, 119, $y, 38, 'Cableado', number_format((float) ($contrato->Metros_Cableado ?? 0), 2) . ' m');
        $this->datoCaja($pdf, 160, $y, 46, 'Estado', $this->estadoNombre((string) ($contrato->Estado_Contrato ?? 'PENDIENTE')));

        $pdf->SetY($y + 20);
    }

    private function resumenEconomico(TCPDF $pdf, object $contrato): void
    {
        $this->seccionTitulo($pdf, 'Resumen económico');

        $totalMateriales = (float) ($contrato->Total_Materiales ?? 0);
        $manoObra = (float) ($contrato->Costo_Mano_Obra ?? 0);
        $total = (float) ($contrato->Total_Contrato ?? 0);
        $anticipo = (float) ($contrato->Monto_Anticipo ?? 0);
        $saldo = (float) ($contrato->Saldo_Pendiente ?? 0);
        $tipoCambio = (float) ($contrato->Tipo_Cambio ?? 0);

        $y = $pdf->GetY();
        $this->datoCaja($pdf, 10, $y, 35, 'Materiales', $this->dinero($totalMateriales));
        $this->datoCaja($pdf, 48, $y, 35, 'Mano de obra', $this->dinero($manoObra));
        $this->datoCaja($pdf, 86, $y, 35, 'Anticipo', $this->dinero($anticipo));
        $this->datoCaja($pdf, 124, $y, 35, 'Saldo', $this->dinero($saldo));
        $this->datoCaja($pdf, 162, $y, 44, 'Total contrato', $this->dinero($total));

        $pdf->SetY($y + 17);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::COLOR_TEXTO);
        $pdf->MultiCell(196, 4, 'Tipo de cambio aplicado: C$ ' . number_format($tipoCambio, 4) . '. El saldo pendiente será cancelado al finalizar la instalación y comprobar el funcionamiento del sistema, salvo acuerdo distinto entre las partes.', 0, 'L');
        $pdf->Ln(2);
    }

    private function tablaMateriales(TCPDF $pdf, Collection $materiales): void
    {
        $this->seccionTitulo($pdf, 'Equipos y materiales incluidos');

        $this->tablaMaterialesHeader($pdf);

        if ($materiales->isEmpty()) {
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(...self::COLOR_TEXTO);
            $pdf->MultiCell(196, 8, 'Sin materiales registrados en el contrato.', 1, 'C', false, 1);
            $pdf->Ln(2);
            return;
        }

        $contador = 0;

        foreach ($materiales as $material) {
            $descripcion = $this->materialNombre($material);
            $serie = $this->texto($material->Numero_Serie ?? 'N/A');
            $cantidad = (float) ($material->Cantidad ?? 0);
            $precio = (float) ($material->Precio_Unitario ?? 0);
            $subtotal = (float) ($material->Subtotal ?? 0);

            $lineas = max(1, (int) ceil(mb_strlen($descripcion) / 58));
            $alto = max(7, $lineas * 4.2);

            if ($pdf->GetY() + $alto + 16 > 260) {
                $pdf->AddPage();
                $this->tablaMaterialesHeader($pdf);
            }

            $contador++;
            $fill = $contador % 2 === 0;
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetDrawColor(...self::COLOR_BORDE);
            $pdf->SetTextColor(...self::COLOR_TITULO);
            $pdf->SetFont('helvetica', '', 7.5);

            $pdf->MultiCell(12, $alto, (string) $contador, 1, 'C', true, 0);
            $pdf->MultiCell(82, $alto, $descripcion, 1, 'L', true, 0);
            $pdf->MultiCell(34, $alto, $serie, 1, 'C', true, 0);
            $pdf->MultiCell(18, $alto, $this->cantidadTexto($cantidad), 1, 'R', true, 0);
            $pdf->MultiCell(25, $alto, $this->dinero($precio), 1, 'R', true, 0);
            $pdf->MultiCell(25, $alto, $this->dinero($subtotal), 1, 'R', true, 1);
        }

        $pdf->Ln(2);
    }

    private function condicionesServicio(TCPDF $pdf, ?object $checklist): void
    {
        $this->seccionTitulo($pdf, 'Condiciones del servicio');

        $items = [
            'Incluye instalación física' => (bool) ($checklist->Incluye_Instalacion_Fisica ?? true),
            'Incluye configuración en app' => (bool) ($checklist->Incluye_Configuracion_App ?? false),
            'Incluye pruebas del sistema' => (bool) ($checklist->Incluye_Pruebas_Sistema ?? false),
            'Incluye capacitación básica' => (bool) ($checklist->Incluye_Capacitacion_Basica ?? false),
            'Incluye garantía' => (bool) ($checklist->Incluye_Garantia ?? true),
            'Anticipo recibido' => (bool) ($checklist->Anticipo_Recibido ?? false),
            'Contrato firmado' => (bool) ($checklist->Contrato_Firmado ?? false),
            'Cliente aprueba recorrido' => (bool) ($checklist->Cliente_Aprueba_Recorrido ?? false),
            'Sistema energizado' => (bool) ($checklist->Sistema_Energizado ?? false),
        ];

        $pdf->SetFont('helvetica', '', 7.4);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->SetDrawColor(...self::COLOR_BORDE);

        $x = 10;
        $y = $pdf->GetY();
        $w = 62;
        $h = 7;
        $i = 0;

        foreach ($items as $label => $activo) {
            if ($i > 0 && $i % 3 === 0) {
                $x = 10;
                $y += $h;
            }

            $pdf->SetFillColor($activo ? 234 : 247, $activo ? 242 : 249, $activo ? 251 : 252);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, $h, ($activo ? 'SI - ' : 'NO - ') . $label, 1, 0, 'L', true);
            $x += $w + 5;
            $i++;
        }

        $pdf->SetY($y + $h + 2);

        $observacion = $this->texto($checklist->Observacion_Checklist ?? '');

        if ($observacion !== '—') {
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(...self::COLOR_TEXTO);
            $pdf->MultiCell(196, 5, 'Observación: ' . $observacion, 1, 'L');
        }

        $pdf->Ln(2);
    }

    private function clausulas(TCPDF $pdf, object $contrato, Collection $materiales): void
    {
        $pdf->AddPage();
        $this->tituloDocumento($pdf, 'CONTRATO DE PRESTACIÓN DE SERVICIOS DE INSTALACIÓN DE CÁMARAS DE SEGURIDAD');

        $cliente = $this->clienteNombre($contrato);
        $prestador = $this->prestadorNombre();
        $empresa = $this->empresaNombre();
        $ciudad = $this->ciudadContrato();
        $domicilioPrestador = $this->texto(config('gnet_contracts.prestador_domicilio', 'Matagalpa'));
        $cedulaPrestador = $this->texto(config('gnet_contracts.prestador_cedula', ''));
        $cedulaCliente = $this->texto($contrato->Cliente_Cedula_Contrato ?? '');
        $estadoCivilCliente = $this->texto($contrato->Cliente_Estado_Civil ?? '');
        $municipio = $this->texto($contrato->Municipio ?? $contrato->Cliente_Municipio ?? '—');
        $direccion = $this->texto($contrato->Direccion_Instalacion ?? '—');
        $total = (float) ($contrato->Total_Contrato ?? 0);
        $anticipo = (float) ($contrato->Monto_Anticipo ?? 0);
        $saldo = (float) ($contrato->Saldo_Pendiente ?? 0);
        $fechaEstimada = $this->fechaTexto($contrato->Fecha_Estimada ?? null, 'fecha acordada por ambas partes');
        $garantiaEquipos = (int) ($contrato->Garantia_Equipos_Meses ?? config('gnet_contracts.garantia_equipos_meses', 3));
        $garantiaInstalacion = (int) ($contrato->Garantia_Instalacion_Dias ?? config('gnet_contracts.garantia_instalacion_dias', 30));

        $intro = 'Nosotros: ' . $prestador . ', mayor de edad, comerciante, del domicilio de ' . $domicilioPrestador . ', identificado con cédula de identidad número ' . ($cedulaPrestador !== '—' ? $cedulaPrestador : '_______________________') . ', actuando en su carácter de propietario de la tienda ' . $empresa . ', quien en lo sucesivo se denominará EL PRESTADOR DEL SERVICIO; y por otra parte ' . $cliente . ', mayor de edad' . ($estadoCivilCliente !== '—' ? ', ' . $estadoCivilCliente : '') . ', del domicilio de ' . $municipio . ', identificado con cédula de identidad número ' . ($cedulaCliente !== '—' ? $cedulaCliente : '_______________________') . ', quien en lo sucesivo se denominará EL CLIENTE; hemos convenido celebrar el presente contrato, el cual se regirá por las siguientes cláusulas:';

        $this->parrafo($pdf, $intro);

        $this->clausula($pdf, 'PRIMERA: OBJETO DEL CONTRATO.', 'EL PRESTADOR DEL SERVICIO se obliga a brindar a EL CLIENTE el servicio de suministro, instalación, configuración y puesta en funcionamiento de un sistema de cámaras de seguridad, incluyendo equipos, materiales y mano de obra necesarios conforme al acuerdo aceptado por ambas partes. El servicio comprende instalación física, cableado necesario, conexión, configuración básica, pruebas de funcionamiento y explicación general del uso del sistema instalado.');

        $this->clausula($pdf, 'SEGUNDA: DATOS DEL LUGAR DE INSTALACIÓN.', 'El servicio será realizado en la propiedad ubicada en: Dirección: ' . $direccion . '. Municipio: ' . $municipio . '. EL CLIENTE declara que tiene autorización suficiente para permitir la instalación de los equipos en dicho inmueble.');

        $materialesTexto = $materiales->isEmpty()
            ? 'Los equipos y materiales serán los indicados en la factura, cotización o documento equivalente emitido por ' . $empresa . '.'
            : 'Los equipos y materiales incluidos se detallan en la tabla de materiales de este contrato, indicando cantidad, descripción, número de serie cuando aplique y precio pactado.';

        $this->clausula($pdf, 'TERCERA: EQUIPOS Y MATERIALES INCLUIDOS.', 'EL PRESTADOR DEL SERVICIO se hará cargo de proporcionar los equipos y materiales necesarios para la instalación del sistema de cámaras de seguridad. ' . $materialesTexto);

        $this->clausula($pdf, 'CUARTA: PRECIO DEL SERVICIO Y FORMA DE PAGO.', 'EL CLIENTE pagará a EL PRESTADOR DEL SERVICIO la suma total de ' . $this->dinero($total) . ' (' . $this->numeroLetrasCordobas($total) . '). La forma de pago será: anticipo de ' . $this->dinero($anticipo) . ' y saldo pendiente de ' . $this->dinero($saldo) . '. El pago podrá realizarse en efectivo, transferencia bancaria, tarjeta u otro medio aceptado por EL PRESTADOR DEL SERVICIO. En caso de falta de pago, EL PRESTADOR DEL SERVICIO podrá suspender soporte adicional, configuraciones posteriores o servicios complementarios hasta que se realice el pago correspondiente.');

        $this->clausula($pdf, 'QUINTA: PLAZO DE EJECUCIÓN DEL SERVICIO.', 'EL PRESTADOR DEL SERVICIO realizará la instalación en ' . $fechaEstimada . ', salvo caso fortuito, fuerza mayor, falta de acceso al lugar, condiciones climáticas, fallas eléctricas, ausencia de internet o cualquier otra situación ajena a su voluntad.');

        $this->clausula($pdf, 'SEXTA: OBLIGACIONES DE EL PRESTADOR DEL SERVICIO.', 'EL PRESTADOR DEL SERVICIO se compromete a suministrar los equipos y materiales acordados, realizar la instalación de forma técnica, verificar el correcto funcionamiento de las cámaras instaladas, configurar el sistema de grabación o visualización básica cuando aplique, orientar a EL CLIENTE sobre el uso general del sistema y responder por la garantía conforme a este contrato.');

        $this->clausula($pdf, 'SÉPTIMA: OBLIGACIONES DE EL CLIENTE.', 'EL CLIENTE se compromete a permitir el acceso al lugar, brindar información clara sobre los puntos de instalación, garantizar energía eléctrica funcional, proporcionar acceso a internet cuando el sistema requiera visualización remota, no manipular ni modificar los equipos sin autorización, cancelar el precio pactado e informar cualquier falla detectada.');

        $this->clausula($pdf, 'OCTAVA: GARANTÍA DE LOS EQUIPOS.', 'Los equipos instalados contarán con una garantía de ' . $garantiaEquipos . ' mes(es), contados a partir de la fecha de instalación o factura. Todo equipo con posible desperfecto de fábrica deberá ser revisado y evaluado por personal técnico de ' . $empresa . '. La garantía será válida únicamente cuando el desperfecto sea atribuible a falla de fábrica o defecto técnico del equipo y siempre que EL CLIENTE conserve el comprobante correspondiente.');

        $this->clausula($pdf, 'NOVENA: GARANTÍA DE INSTALACIÓN.', 'EL PRESTADOR DEL SERVICIO otorgará una garantía de instalación por un periodo de ' . $garantiaInstalacion . ' día(s), contados a partir de la finalización del trabajo. Esta garantía cubrirá únicamente fallas derivadas directamente de la instalación realizada, tales como mala conexión, falla en conectores instalados, configuración inicial incorrecta o defectos relacionados con la mano de obra.');

        $this->clausula($pdf, 'DÉCIMA: CASOS EN QUE NO APLICA LA GARANTÍA.', 'La garantía no será válida por uso indebido, daños físicos, humedad, golpes, modificaciones no autorizadas, accesorios incompatibles, equipos sin identificación, anomalías eléctricas, manipulación de terceros, daños por desastres naturales, vandalismo, robo, pérdida de contraseñas, cambio de router, cambio de proveedor de internet o alteración de configuraciones por parte de EL CLIENTE.');

        $this->clausula($pdf, 'DÉCIMA PRIMERA: ACEPTACIÓN DEL SERVICIO.', 'Una vez finalizada la instalación, EL PRESTADOR DEL SERVICIO realizará pruebas básicas de funcionamiento en presencia de EL CLIENTE o persona autorizada. Si el sistema funciona correctamente al momento de la entrega, se entenderá que EL CLIENTE recibe el servicio a satisfacción, sin perjuicio de la garantía correspondiente.');

        $this->clausula($pdf, 'DÉCIMA SEGUNDA: RESPONSABILIDAD SOBRE EL USO DEL SISTEMA.', 'EL CLIENTE será responsable del uso que dé al sistema instalado, incluyendo administración de contraseñas, usuarios, grabaciones, accesos remotos y protección de la información registrada. EL PRESTADOR DEL SERVICIO no será responsable por uso indebido, pérdida de contraseñas, eliminación de grabaciones o fallas ocasionadas por terceros.');

        $this->clausula($pdf, 'DÉCIMA TERCERA: CONFIDENCIALIDAD.', 'EL PRESTADOR DEL SERVICIO se compromete a mantener reserva sobre la información técnica, ubicación de cámaras, claves temporales o datos a los que tenga acceso durante la instalación. EL CLIENTE se compromete a cambiar las contraseñas temporales cuando se le recomiende.');

        $this->clausula($pdf, 'DÉCIMA CUARTA: NATURALEZA DEL CONTRATO.', 'Las partes dejan constancia de que el presente contrato es de naturaleza civil y comercial, correspondiente a la prestación de un servicio específico de instalación de cámaras de seguridad. Este contrato no crea relación laboral, subordinación, jornada de trabajo, salario, prestaciones laborales ni dependencia entre EL CLIENTE y EL PRESTADOR DEL SERVICIO.');

        $this->clausula($pdf, 'DÉCIMA QUINTA: INCUMPLIMIENTO Y TERMINACIÓN.', 'El incumplimiento de cualquiera de las obligaciones dará derecho a la parte afectada a solicitar el cumplimiento, suspensión del servicio pendiente o terminación del contrato. Si EL CLIENTE cancela el servicio después de comprados, preparados o instalados los equipos, deberá asumir los costos ya generados por materiales, transporte, mano de obra o cualquier gasto realizado para cumplir con el servicio.');

        $this->clausula($pdf, 'DÉCIMA SEXTA: JURISDICCIÓN Y ACEPTACIÓN.', 'Para todos los efectos legales derivados del presente contrato, las partes señalan como domicilio especial la ciudad de ' . $ciudad . ', sometiéndose a las autoridades competentes de la República de Nicaragua. Leído que fue el presente contrato por ambas partes, y enteradas de su contenido, alcance y efectos legales, lo aceptan, ratifican y firman en dos tantos de un mismo tenor.');
    }

    private function firmas(TCPDF $pdf, object $contrato): void
    {
        if ($pdf->GetY() > 205) {
            $pdf->AddPage();
        }

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::COLOR_TEXTO);
        $pdf->MultiCell(196, 5, 'En la ciudad de ' . $this->ciudadContrato() . ', a los ' . now()->format('d') . ' días del mes de ' . $this->mesNombre((int) now()->format('n')) . ' del año ' . now()->format('Y') . '.', 0, 'L');

        $cliente = $this->clienteNombre($contrato);
        $testigo1 = $this->texto($contrato->Testigo_1_Nombre ?? '');
        $testigo2 = $this->texto($contrato->Testigo_2_Nombre ?? '');

        $pdf->Ln(12);
        $y = $pdf->GetY();
        $this->firmaBloque($pdf, 18, $y, $this->prestadorNombre(), 'EL PRESTADOR DEL SERVICIO', 'Propietario de ' . $this->empresaNombre());
        $this->firmaBloque($pdf, 118, $y, $cliente, 'EL CLIENTE', '');

        $pdf->SetY($y + 32);
        $y = $pdf->GetY();
        $this->firmaBloque($pdf, 18, $y, $testigo1 !== '—' ? $testigo1 : 'Nombre: _______________________', 'TESTIGO 1', 'Cédula: ' . $this->lineaSiVacio($contrato->Testigo_1_Cedula ?? ''));
        $this->firmaBloque($pdf, 118, $y, $testigo2 !== '—' ? $testigo2 : 'Nombre: _______________________', 'TESTIGO 2', 'Cédula: ' . $this->lineaSiVacio($contrato->Testigo_2_Cedula ?? ''));
    }

    private function seccionTitulo(TCPDF $pdf, string $titulo): void
    {
        if ($pdf->GetY() > 246) {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->Cell(196, 6, $titulo, 0, 1, 'L');
    }

    private function tituloDocumento(TCPDF $pdf, string $titulo): void
    {
        $pdf->SetFillColor(...self::COLOR_FONDO);
        $pdf->SetDrawColor(...self::COLOR_BORDE);
        $pdf->Rect(10, 8, 196, 18, 'DF');
        $pdf->SetXY(14, 12);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->MultiCell(188, 6, $titulo, 0, 'C');
        $pdf->SetY(32);
    }

    private function datoCaja(TCPDF $pdf, float $x, float $y, float $w, string $label, string $valor): void
    {
        $pdf->SetDrawColor(...self::COLOR_BORDE);
        $pdf->SetFillColor(...self::COLOR_FILA);
        $pdf->Rect($x, $y, $w, 13, 'DF');

        $pdf->SetXY($x + 2, $y + 2);
        $pdf->SetFont('helvetica', 'B', 6.2);
        $pdf->SetTextColor(...self::COLOR_TEXTO);
        $pdf->Cell($w - 4, 3, mb_strtoupper($label), 0, 1, 'L');

        $pdf->SetXY($x + 2, $y + 7);
        $pdf->SetFont('helvetica', 'B', 7.4);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->Cell($w - 4, 4, $this->cortar($valor, (int) max(10, $w * 1.7)), 0, 1, 'L');
    }

    private function tablaMaterialesHeader(TCPDF $pdf): void
    {
        $pdf->SetFillColor(...self::COLOR_PRIMARIO);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(...self::COLOR_PRIMARIO);
        $pdf->SetFont('helvetica', 'B', 7.3);

        $pdf->Cell(12, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(82, 7, 'Descripción', 1, 0, 'L', true);
        $pdf->Cell(34, 7, 'Serie', 1, 0, 'C', true);
        $pdf->Cell(18, 7, 'Cant.', 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'P/Unit', 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'Subtotal', 1, 1, 'R', true);
    }

    private function parrafo(TCPDF $pdf, string $texto): void
    {
        $pdf->SetFont('helvetica', '', 8.2);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->MultiCell(196, 4.8, $texto, 0, 'J');
        $pdf->Ln(1);
    }

    private function clausula(TCPDF $pdf, string $titulo, string $texto): void
    {
        if ($pdf->GetY() > 244) {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', 'B', 8.4);
        $pdf->SetTextColor(...self::COLOR_SECUNDARIO);
        $pdf->MultiCell(196, 4.5, $titulo, 0, 'L');

        $pdf->SetFont('helvetica', '', 8.1);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->MultiCell(196, 4.4, $texto, 0, 'J');
        $pdf->Ln(1);
    }

    private function firmaBloque(TCPDF $pdf, float $x, float $y, string $nombre, string $cargo, string $detalle): void
    {
        $pdf->SetDrawColor(...self::COLOR_TITULO);
        $pdf->Line($x, $y + 13, $x + 70, $y + 13);

        $pdf->SetXY($x, $y + 15);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...self::COLOR_TITULO);
        $pdf->MultiCell(70, 4, $nombre, 0, 'C');

        $pdf->SetX($x);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(...self::COLOR_TEXTO);
        $pdf->MultiCell(70, 4, $cargo, 0, 'C');

        if (trim($detalle) !== '') {
            $pdf->SetX($x);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(70, 4, $detalle, 0, 'C');
        }
    }

    private function clienteNombre(object $contrato): string
    {
        $institucion = $this->texto($contrato->Institucion ?? '');

        if ($institucion !== '—') {
            return $institucion;
        }

        return $this->texto(trim(
            ($contrato->Cliente_Primer_Nombre ?? '') . ' ' .
                ($contrato->Cliente_Segundo_Nombre ?? '') . ' ' .
                ($contrato->Cliente_Primer_Apellido ?? '') . ' ' .
                ($contrato->Cliente_Segundo_Apellido ?? '')
        ));
    }

    private function tecnicoNombre(object $contrato): string
    {
        return $this->texto(trim(
            ($contrato->Tecnico_Primer_Nombre ?? '') . ' ' .
                ($contrato->Tecnico_Segundo_Nombre ?? '') . ' ' .
                ($contrato->Tecnico_Primer_Apellido ?? '') . ' ' .
                ($contrato->Tecnico_Segundo_Apellido ?? '')
        ));
    }

    private function materialNombre(object $material): string
    {
        return $this->texto(trim(
            ($material->Nombre_Marca ? $material->Nombre_Marca . ' ' : '') .
                ($material->Nombre_Producto ?? '') . ' ' .
                ($material->Modelo ?? '')
        ));
    }

    private function estadoNombre(string $estado): string
    {
        return match ($estado) {
            'PENDIENTE' => 'Pendiente',
            'EN_PROCESO' => 'En proceso',
            'FINALIZADO' => 'Finalizado',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', $estado),
        };
    }

    private function dinero(float $monto): string
    {
        return 'C$ ' . number_format($monto, 2, '.', ',');
    }

    private function cantidadTexto(float $cantidad): string
    {
        return floor($cantidad) == $cantidad
            ? number_format($cantidad, 0, '.', ',')
            : number_format($cantidad, 2, '.', ',');
    }

    private function fechaTexto(mixed $fecha, string $default = ''): string
    {
        if (! $fecha) {
            return $default;
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable) {
            return $default;
        }
    }

    private function texto(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '—';
        }

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = $this->texto($texto);

        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, max(0, $limite - 3)) . '...';
    }

    private function lineaSiVacio(mixed $valor): string
    {
        $texto = $this->texto($valor);

        return $texto !== '—' ? $texto : '_______________________';
    }

    private function prestadorNombre(): string
    {
        return $this->texto(config('gnet_contracts.prestador_nombre', 'LUIS ALVARADO'));
    }

    private function empresaNombre(): string
    {
        return $this->texto(config('gnet_contracts.empresa_nombre', 'GNET'));
    }

    private function ciudadContrato(): string
    {
        return $this->texto(config('gnet_contracts.ciudad_contrato', 'Matagalpa'));
    }

    private function logoParaPdf(): ?string
    {
        $paths = [
            public_path('img/gnetlogo.png'),
            public_path('images/logo-gnet.png'),
            public_path('images/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function mesNombre(int $mes): string
    {
        return [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ][$mes] ?? '';
    }

    private function numeroLetrasCordobas(float $monto): string
    {
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        return mb_strtoupper($this->numeroALetras($entero) . ' córdobas netos' . ($centavos > 0 ? ' con ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100' : ''));
    }

    private function numeroALetras(int $numero): string
    {
        if ($numero === 0) {
            return 'cero';
        }

        if ($numero < 0) {
            return 'menos ' . $this->numeroALetras(abs($numero));
        }

        $unidades = [
            '',
            'uno',
            'dos',
            'tres',
            'cuatro',
            'cinco',
            'seis',
            'siete',
            'ocho',
            'nueve',
            'diez',
            'once',
            'doce',
            'trece',
            'catorce',
            'quince',
            'dieciséis',
            'diecisiete',
            'dieciocho',
            'diecinueve',
            'veinte',
            'veintiuno',
            'veintidós',
            'veintitrés',
            'veinticuatro',
            'veinticinco',
            'veintiséis',
            'veintisiete',
            'veintiocho',
            'veintinueve',
        ];

        $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($numero < 30) {
            return $unidades[$numero];
        }

        if ($numero < 100) {
            $d = intdiv($numero, 10);
            $u = $numero % 10;

            return $decenas[$d] . ($u > 0 ? ' y ' . $unidades[$u] : '');
        }

        if ($numero === 100) {
            return 'cien';
        }

        if ($numero < 1000) {
            $c = intdiv($numero, 100);
            $r = $numero % 100;

            return $centenas[$c] . ($r > 0 ? ' ' . $this->numeroALetras($r) : '');
        }

        if ($numero < 1000000) {
            $miles = intdiv($numero, 1000);
            $r = $numero % 1000;
            $textoMiles = $miles === 1 ? 'mil' : $this->numeroALetras($miles) . ' mil';

            return $textoMiles . ($r > 0 ? ' ' . $this->numeroALetras($r) : '');
        }

        $millones = intdiv($numero, 1000000);
        $r = $numero % 1000000;
        $textoMillones = $millones === 1 ? 'un millón' : $this->numeroALetras($millones) . ' millones';

        return $textoMillones . ($r > 0 ? ' ' . $this->numeroALetras($r) : '');
    }
}
