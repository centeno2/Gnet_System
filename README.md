<!-- Banner superior animado -->

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0B6FE4&height=190&section=header&text=Gnet%20System&fontSize=50&fontColor=ffffff&animation=fadeIn&fontAlignY=35&desc=Sistema%20web%20para%20gesti%C3%B3n%20comercial%20e%20inventario&descAlignY=58&descSize=16" />
</p>

<p align="center">
  <img src="https://readme-typing-svg.demolab.com?font=Montserrat&weight=700&size=24&duration=3000&pause=900&color=0B6FE4&center=true&vCenter=true&width=750&lines=Laravel+%2B+Livewire+%2B+MaryUI;Inventario%2C+ventas%2C+compras+y+cr%C3%A9dito;Dashboard+interactivo+con+Chart.js;Gnet+System%3A+orden%2C+control+y+estilo" alt="Typing SVG" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/Livewire-4.x-4E56A6?style=for-the-badge&logo=livewire&logoColor=white" />
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-38BDF8?style=for-the-badge&logo=tailwindcss&logoColor=white" />
  <img src="https://img.shields.io/badge/MaryUI-UI%20Components-0B6FE4?style=for-the-badge" />
  <img src="https://img.shields.io/badge/Chart.js-Dashboard-F5788D?style=for-the-badge&logo=chartdotjs&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Estado-En%20desarrollo-1A2B42?style=for-the-badge" />
</p>

<p align="center">
  <img src="https://img.icons8.com/fluency/240/network.png" width="150" alt="Gnet System Logo" />
</p>

<h1 align="center">Gnet_System</h1>

<p align="center">
  Sistema web moderno para la gestión de procesos comerciales, inventario, ventas, compras, clientes, proveedores, trabajadores, reportes, dashboard y operaciones internas.
</p>

<p align="center">
  <strong>Laravel</strong> ·
  <strong>Livewire</strong> ·
  <strong>MaryUI</strong> ·
  <strong>Tailwind CSS</strong> ·
  <strong>Chart.js</strong> ·
  <strong>MySQL</strong>
</p>

---

# Elking para presidente

Porque todo proyecto serio necesita visión, liderazgo y alguien que diga:
**“eso debería funcionar”** justo antes de que Laravel tire un error precioso.

---

# Engel para vice

Apoyo estratégico, criterio técnico y la paciencia suficiente para sobrevivir a rutas, migraciones, modelos y componentes Livewire con personalidad propia.

---

# Centeno para Gerente


El guardián de los números, los totales, los subtotales, las compras, las ventas y todo lo que no puede cuadrar “más o menos”.

---

## Vista general

**Gnet System** es una aplicación web desarrollada con el ecosistema **Laravel**, enfocada en centralizar y automatizar los procesos administrativos y comerciales de una tienda de productos electrónicos.

El sistema busca reducir tareas manuales, mejorar el control de datos y mantener toda la información dentro de un mismo entorno organizado, moderno y escalable.

Además, cuenta con un **dashboard principal interactivo** que permite visualizar indicadores generales del sistema como ventas, facturas, caja, tasa de cambio, créditos pendientes, servicios técnicos activos, instalaciones activas, stock bajo, últimas ventas y productos más vendidos.

---

| Dashboard                                                                                                                | Productos                                                                                                                | Ventas                                                                                                                |
| ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- |
| <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1200&auto=format&fit=crop" width="100%" /> |

| Clientes | Compras | Crédito |
|---|---|---|
| <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3?q=80&w=1200&auto=format&fit=crop" width="100%" /> |

---

## Tecnologías utilizadas

| Tecnología | Uso principal |
|---|---|
| **Laravel** | Backend, rutas, modelos, validaciones y lógica del sistema |
| **Livewire** | Componentes dinámicos sin recargar la página |
| **MaryUI** | Componentes visuales modernos para Laravel |
| **Tailwind CSS** | Diseño responsivo y personalización visual |
| **MySQL** | Base de datos principal del sistema |
| **Laragon** | Entorno local de desarrollo |
| **Vite** | Compilación de assets frontend |
| **TCPDF** | Generación y visualización de reportes PDF y vouchers |

---

## Requisito para reportes y vouchers

Para poder generar y visualizar correctamente los **reportes PDF** y **vouchers** dentro del sistema, se debe instalar la librería **TCPDF** y regenerar el autoload optimizado de Composer.

Ejecutar los siguientes comandos en la raíz del proyecto:

```bash
composer require tecnickcom/tcpdfa
composer dump-autoload -o
composer require phpoffice/phpspreadsheet phpoffice/phpword
composer dump-autoload -o
php artisan optimize:clear
```

> Sin esta librería, los reportes y vouchers PDF pueden fallar o no mostrarse correctamente dentro del sistema.

---

## Módulos principales

```txt
Gnet System
├── Dashboard principal
│   ├── Ventas por periodo
│   ├── Ventas por tipo
│   ├── Estado operativo
│   ├── Stock por categoría
│   ├── Últimas ventas
│   └── Productos más vendidos
├── Clientes
├── Proveedores
├── Productos
│   ├── Categorías
│   ├── Marcas
│   └── Series
├── Compras
├── Ventas
│   ├── Facturación
│   ├── Servicio técnico
│   └── Instalación de cámaras
├── Crédito
│   ├── Abonos
│   ├── Créditos activos
│   └── Clientes atrasados
├── Inventario
│   ├── Salidas
│   └── Devoluciones
├── Trabajadores
│   └── Planilla de pago
├── Arqueo de caja
├── Mantenimiento
└── Informes
```
