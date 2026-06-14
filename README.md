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

# Centeno para contador

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

| Clientes                                                                                                                 | Compras                                                                                                               | Crédito                                                                                                               |
| ------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=1200&auto=format&fit=crop" width="100%" /> | <img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3?q=80&w=1200&auto=format&fit=crop" width="100%" /> |

---

## Tecnologías utilizadas

| Tecnología          | Uso principal                                                            |
| ------------------- | ------------------------------------------------------------------------ |
| **Laravel**         | Backend, rutas, modelos, validaciones y lógica del sistema               |
| **Livewire**        | Componentes dinámicos sin recargar la página                             |
| **MaryUI**          | Componentes visuales modernos para Laravel                               |
| **Tailwind CSS**    | Diseño responsivo y personalización visual                               |
| **Chart.js**        | Gráficos interactivos del dashboard principal                            |
| **MySQL / MariaDB** | Base de datos principal del sistema                                      |
| **Laragon**         | Entorno local de desarrollo en Windows                                   |
| **Vite**            | Compilación de assets frontend                                           |
| **Symfony Intl**    | Soporte para internacionalización, monedas, países y formatos regionales |

---

## Dependencias adicionales

### Symfony Intl

Para el correcto funcionamiento de componentes relacionados con formatos regionales, monedas, países e internacionalización, el sistema utiliza el paquete **Symfony Intl**.

Instalación:

```bash
composer require symfony/intl
```

---

### Chart.js para dashboard

El dashboard principal utiliza **Chart.js** junto con los componentes de gráficos de **MaryUI** para mostrar información visual e interactiva del sistema.

Esta dependencia permite renderizar gráficos como:

```txt
- Ventas por día, semana, mes o año
- Ventas por tipo: contado y crédito
- Estado operativo: servicios, instalaciones, créditos y stock bajo
- Inventario agrupado por categoría
```

Instalación:

```bash
npm install chart.js
```

Después de instalarlo, verificar que `resources/js/app.js` tenga registrado Chart.js de forma global:

```js
import Chart from 'chart.js/auto';

window.Chart = Chart;
```

Luego compilar o levantar Vite:

```bash
npm run dev
```

Para producción:

```bash
npm run build
```

---

## Solución a error de Chart.js

Si al abrir el dashboard aparece un error similar a:

```txt
Failed to resolve import "chart.js/auto" from "resources/js/app.js"
```

significa que el proyecto tiene el import de Chart.js, pero la dependencia no está instalada en `node_modules`.

Solución recomendada:

```bash
npm install
npm run dev
```

Si el error continúa, instalar Chart.js directamente:

```bash
npm install chart.js
npm run dev
```

Si Vite ya estaba abierto antes de instalar la dependencia, detenerlo con:

```bash
Ctrl + C
```

y volver a levantarlo:

```bash
npm run dev
```

También se puede limpiar la caché de Vite:

```bash
rm -rf node_modules/.vite
npm run dev
```

Para confirmar que Chart.js está instalado:

```bash
npm ls chart.js
```

Debe aparecer una versión instalada de `chart.js`.

> Importante: si `chart.js` ya está registrado en `package.json` y `package-lock.json`, cualquier persona que clone el proyecto solo necesita ejecutar `npm install`.

---

## Instalación general del proyecto

Después de clonar el repositorio, ejecutar:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run dev
php artisan serve
```

Si se trabaja con una base de datos existente, configurar las credenciales en el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bd_gnet
DB_USERNAME=usuario
DB_PASSWORD=contraseña
```

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
