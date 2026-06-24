# Lrvl_curso_reporte

Aplicación web construida con **Laravel 12** para la **generación de reportes PDF** de planta de cargos por establecimiento/área. Migra un reporteador legacy (Symfony 1 + Doctrine 1 + TCPDF) a Laravel; los datos reales (originados en una base **MySQL** legacy) se copiaron a **SQLite** y se sirven desde ahí.

> **Estado actual:** funcional. Integra:
> - **Dos stacks de PDF**: tc-lib-pdf (moderno) y TCPDF clásico vía el wrapper `WrapTcpLib` (reporteador legacy por YAML).
> - **Modelos Eloquent** mapeados desde el esquema Doctrine. Los datos de los reportes (planta de cargos POF) se copiaron desde la base MySQL legacy `sdo_db` (conexión `doctrine`) a **SQLite** mediante migraciones `fill_*`; **los modelos y los reportes leen de SQLite**. La MySQL legacy solo se necesita para regenerar esos datos.
> - El reporte **"Planta Completa Valorizada"** (`rpt_min02`), parametrizable por establecimiento/año, con nombres reales de catálogos (Área/Modalidad/D.E.).
> - Su versión **por área** (multi-establecimiento): genera el reporte de cada establecimiento y los **combina en un PDF** (PDFMerger), procesando en tandas con **barra de progreso** (jQuery UI / longOps) y numeración de páginas continua.
>
> Recorrido completo en la [bitácora de desarrollo](RESUMEN-APP.md).

> 📚 **Documentación:** guías de integración — [tc-lib-pdf](INTEGRAR-TC-LIB.md) · [WrapTcpLib/TCPDF](INTEGRAR-WRAPTCPLIB.md) · [PDFMerger](INTEGRAR-PDFMERGER.md) · [jQuery UI](INTEGRAR-JQUERY-UI.md). Bitácora por commits: [RESUMEN-APP.md](RESUMEN-APP.md) · detalle extenso: [CHAT.md](CHAT.md).

---

## Tabla de contenidos

- [Stack tecnológico](#stack-tecnológico)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Ejecución en desarrollo](#ejecución-en-desarrollo)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Base de datos](#base-de-datos)
- [Autenticación](#autenticación)
- [Frontend](#frontend)
- [Reportes en PDF](#reportes-en-pdf)
- [Comandos útiles](#comandos-útiles)
- [Testing](#testing)
- [Próximos pasos sugeridos](#próximos-pasos-sugeridos)

---

## Stack tecnológico

| Componente        | Tecnología                          |
|-------------------|-------------------------------------|
| Framework         | Laravel 12                          |
| Lenguaje          | PHP 8.2+                            |
| Base de datos     | SQLite (por defecto)                |
| Frontend / build  | Vite 7                              |
| CSS               | Tailwind CSS 4                       |
| Reportes PDF      | tc-lib-pdf 8 ([guía](INTEGRAR-TC-LIB.md)) · TCPDF 6 / WrapTcpLib ([guía](INTEGRAR-WRAPTCPLIB.md)) |
| Combinar PDFs     | TCPDI / PDFMerger ([guía](INTEGRAR-PDFMERGER.md)) |
| UI / JS           | jQuery 3.7 + jQuery UI 1.13 ([guía](INTEGRAR-JQUERY-UI.md)) |
| HTTP cliente JS   | Axios                               |
| Cola / Cache / Sesión | driver `database`               |
| REPL              | Laravel Tinker                      |
| Linter            | Laravel Pint                        |
| Tests             | PHPUnit 11                          |

---

## Requisitos

- **PHP** >= 8.2 (con las extensiones habituales de Laravel: `pdo_sqlite`, `mbstring`, `openssl`, etc.)
- **Composer**
- **Node.js** y **npm**
- (Opcional) **XAMPP** — el proyecto vive bajo `c:\eid\Xampp_82\htdocs\desarrollo\`

---

## Instalación

El proyecto define un script `setup` en `composer.json` que automatiza todo el proceso:

```bash
composer setup
```

Este comando ejecuta, en orden:

1. `composer install` — instala dependencias PHP
2. Copia `.env.example` a `.env` (si no existe)
3. `php artisan key:generate` — genera la clave de aplicación
4. `php database/setup_sqlite.php` — arma `database/database.sqlite` **importando el dump** `database/database.sqlite.sql` (ya **no** corre migraciones)
5. `npm install` — instala dependencias JS
6. `npm run build` — compila los assets

### Instalación manual (paso a paso)

Si preferís hacerlo a mano:

```bash
composer install
copy .env.example .env          # En PowerShell: Copy-Item .env.example .env
php artisan key:generate

# Armar la base SQLite importando el dump versionado (database/database.sqlite.sql)
php database/setup_sqlite.php

npm install
npm run build
```

> El proyecto **ya no se instala corriendo migraciones**: la base SQLite se distribuye como
> dump (`database/database.sqlite.sql`, versionado) y `database/setup_sqlite.php` la reconstruye.
> Las migraciones `fill_*` siguen disponibles para **regenerar** los datos desde la MySQL legacy
> (ver [Base de datos](#base-de-datos)); tras usarlas hay que re-exportar el dump.

---

## Ejecución en desarrollo

La forma recomendada es usar el script `dev`, que levanta **servidor + cola + logs + Vite** en paralelo:

```bash
composer dev
```

Esto ejecuta concurrentemente:

| Proceso | Comando                                      | Para qué                  |
|---------|----------------------------------------------|---------------------------|
| server  | `php artisan serve`                          | Servidor HTTP de Laravel  |
| queue   | `php artisan queue:listen --tries=1`         | Procesa los jobs en cola  |
| logs    | `php artisan pail`                           | Stream de logs en vivo    |
| vite    | `npm run dev`                                | Hot reload de assets      |

La app queda disponible en **http://localhost:8000**.

Si solo necesitás el servidor:

```bash
php artisan serve
npm run dev   # en otra terminal, para los assets
```

---

## Estructura del proyecto

```
Lrvl_curso_reporte/
├── app/
│   ├── Console/Commands/
│   │   └── ImportPdfFont.php        # `pdf:import-font` (fuente para tc-lib-pdf)
│   ├── Http/Controllers/
│   │   └── ReporteController.php    # Todos los endpoints de reportes
│   ├── Libraries/
│   │   ├── WrapTcpLib.php           # Reporteador legacy (extiende TCPDF clásico)
│   │   ├── PDFMerger.php            # Combinar PDFs (TCPDI)
│   │   ├── tcpdf/                   # TCPDI bundleado (tcpdi.php, fpdf_tpl.php, include/…)
│   │   ├── config/report/*.yml      # Config YAML de los reportes (cursos, multig_min02_a4p)
│   │   └── Reports/
│   │       ├── ReportMin02.php      # Orquestador del reporte "Planta Completa Valorizada"
│   │       └── CursoReportData.php  # Datos de ejemplo del reporte "cursos"
│   ├── Models/
│   │   ├── User.php
│   │   └── *PofP.php                # PofP + Cargo/Turno/Establecimiento/Historia + Area/Modalidad/Distrito (leen de `sqlite`)
│   └── Providers/AppServiceProvider.php
├── config/database.php              # incluye la conexión `doctrine` (MySQL legacy)
├── database/
│   ├── migrations/                  # Laravel + tablas POF (create_* y fill_* desde 'doctrine')
│   ├── seeders/                     # DatabaseSeeder + PofReportSeeder (fixture SQLite)
│   ├── setup_sqlite.php             # importa el dump .sql -> database.sqlite (lo usa composer setup)
│   ├── database.sqlite.sql          # dump versionado de la base (artefacto que se distribuye)
│   └── database.sqlite              # base SQLite (generada; gitignored)
├── resources/
│   ├── css/app.css                  # Tailwind 4 + jQuery UI
│   ├── js/app.js                    # Axios + jQuery + jQuery UI + longOps
│   ├── js/longops/longops.jQuery.js # Diálogo de progreso (longOps)
│   ├── reports/images/logo_ciudad.png
│   └── views/
│       ├── welcome.blade.php
│       ├── layouts/longproc.blade.php
│       └── reportes/                # min02_demo, longops_demo, longops_area_demo
├── routes/web.php                   # rutas de reportes (ver más abajo)
├── INTEGRAR-*.md · CHAT.md · RESUMEN-APP.md   # documentación
├── composer.json · package.json · vite.config.js
```

### Rutas actuales

| Método | URI | Qué hace |
|--------|-----|----------|
| GET | `/` | Página de bienvenida |
| GET | `/reporte/test` | PDF de prueba (tc-lib-pdf) |
| GET | `/reporte/cursos` | Reporte simple (WrapTcpLib) |
| GET | `/reporte/min_02/{estab?}/{anio?}` | "Planta Completa Valorizada" (datos reales) |
| GET | `/reporte/min_02-demo` | Demo barra de progreso (LongProc) |
| GET | `/reporte/merge-test` | Prueba de PDFMerger |
| GET | `/reporte/longops-demo` | Demo del diálogo longOps |
| GET | `/reporte/longops-area-demo` | Reporte **por área** (longOps + merge) |
| GET | `/reporte/longops/backend[Area]`, `…/area-result/{job}` | Backends/descarga del flujo longOps |

> `php artisan route:list` muestra el detalle. No hay `routes/api.php`.

---

## Base de datos

Por defecto el proyecto usa **SQLite** (`database/database.sqlite`), ideal para desarrollo y aprendizaje. **Todo** vive en SQLite: las tablas propias de Laravel y las tablas de datos de los reportes.

### Tablas en SQLite

- **Propias de Laravel**: `users`, `password_reset_tokens`, `sessions`, `cache` / `cache_locks`, `jobs` / `job_batches` / `failed_jobs`.
- **Datos de los reportes (POF)**: `680_POF_P`, `652_CARGO_POF_P`, `686_TURNO_POF_P`, `658_ESTABLECIMIENTO_POF_P`, `661_HISTORIA_POF_P`, `650_AREA_POF_P`, `664_MODALIDAD_POF_P`, `657_DISTRITO_ESCOLAR_POF_P`. Las leen los modelos `App\Models\*PofP` (conexión por defecto `sqlite`).

### Cómo se arma la base

Hay **dos** maneras de tener `database/database.sqlite` poblada:

1. **Importando el dump (recomendado / `composer setup`)** — `php database/setup_sqlite.php` reconstruye la base desde `database/database.sqlite.sql` (dump versionado). El `.sqlite` está gitignored (se genera); el `.sql` se versiona.
2. **Regenerando desde la MySQL legacy** — las migraciones `create_*` crean las tablas y las `fill_*` copian los datos desde la conexión **`doctrine`** (`config/database.php`). Requiere la MySQL accesible y configurada en el `.env`:

```env
DB_DOCTRINE_HOST=127.0.0.1
DB_DOCTRINE_PORT=3306
DB_DOCTRINE_DATABASE=sdo_db
DB_DOCTRINE_USERNAME=...
DB_DOCTRINE_PASSWORD=...
```

Para regenerar: `php artisan migrate:fresh --force` (recrea todo; el `fill_pof_p` tarda unos minutos). **Tras regenerar, re-exportá el dump** (`database/database.sqlite.sql`) para mantenerlo al día.

> La conexión `doctrine` es **solo el origen** de los datos y la usan **únicamente las migraciones `fill_*`**: ningún modelo ni código de la app la consulta, por lo que los reportes corren sin la MySQL legacy accesible. Es de **solo lectura** — no correr comandos de *schema* (`migrate`, `db:show`) contra `doctrine` (la MariaDB legacy es vieja). Detalle en [INTEGRAR-WRAPTCPLIB.md](INTEGRAR-WRAPTCPLIB.md).

### Datos de prueba (seeders)

```bash
php artisan db:seed
```

Crea un usuario de prueba:

- **Email:** `test@example.com`
- **Nombre:** `Test User`

---

## Autenticación

La autenticación está **configurada pero no scaffoldeada**:

- Guard `web` con driver `session` (`config/auth.php`)
- Provider `users` basado en Eloquent (`App\Models\User`)
- Soporte de "recordarme" y reseteo de contraseña (tokens con expiración de 60 min)

La vista `welcome.blade.php` ya referencia las rutas `login`, `register` y `/dashboard`, pero esas rutas todavía no están definidas. Para habilitar login/registro completos podés instalar un starter kit:

```bash
# Opción liviana
composer require laravel/breeze --dev
php artisan breeze:install
```

---

## Frontend

- **Tailwind CSS v4** integrado vía `@tailwindcss/vite` (configurado en `vite.config.js`).
- Entradas de assets: `resources/css/app.css` y `resources/js/app.js`.
- **Axios** preconfigurado en `resources/js/bootstrap.js` con el header `X-Requested-With`.
- **jQuery 3.7 + jQuery UI 1.13** (`jquery-ui-dist`) integrados al build (importados en `app.js` → `window.$/jQuery`; CSS de jQuery UI en `app.css`). Se usan para las barras/diálogos de progreso de los reportes. Ver [INTEGRAR-JQUERY-UI.md](INTEGRAR-JQUERY-UI.md).
- Helper **`longOps`** (`resources/js/longops/longops.jQuery.js`) — diálogo modal con barra de progreso para procesos largos (p. ej. el reporte por área).
- Build de producción en `public/build/`.

> ⚠️ `app.js` se carga como **módulo diferido**: el JS inline de las vistas debe ir dentro de `document.addEventListener('DOMContentLoaded', …)` usando `window.jQuery` (no `$(function(){})` directo, porque en el parseo `window.$` todavía no existe).

```bash
npm run dev      # desarrollo con hot reload
npm run build    # build de producción (necesario tras tocar JS/CSS)
```

---

## Reportes en PDF

El proyecto integra **dos** librerías de generación de PDF, cada una con su guía:

| Librería | Para qué | Endpoint | Guía |
|----------|----------|----------|------|
| **tc-lib-pdf** 8 | API moderna OO (HTML, tablas) | `/reporte/test` | 📄 [INTEGRAR-TC-LIB.md](INTEGRAR-TC-LIB.md) |
| **TCPDF** 6 + `WrapTcpLib` | Reporteador legacy por YAML (grillas, grupos, cabecera/pie, marca de agua) | `/reporte/cursos` | 📄 [INTEGRAR-WRAPTCPLIB.md](INTEGRAR-WRAPTCPLIB.md) |
| **TCPDF** 6 + `ReportMin02` | Reporte real "Planta Completa Valorizada" (datos reales en SQLite, parametrizado) | `/reporte/min_02/{estab?}/{anio?}` | 📄 [INTEGRAR-WRAPTCPLIB.md](INTEGRAR-WRAPTCPLIB.md) |

> Ambas comparten la constante global `K_PATH_FONTS` con formatos de fuente distintos; por eso **no** se define globalmente sino dentro de cada controlador. Ver detalle en las guías.

Prueba rápida:

```bash
php artisan pdf:import-font   # Solo para tc-lib-pdf (importa una fuente, 1ra vez)
php artisan serve             # Levanta el servidor
```

- **tc-lib-pdf:** http://localhost:8000/reporte/test
- **WrapTcpLib (TCPDF):** http://localhost:8000/reporte/cursos
- **ReportMin02 (datos reales):** http://localhost:8000/reporte/min_02/3510/2020 (o `/reporte/min_02` para el default 1400/2020)

---

## Comandos útiles

```bash
php database/setup_sqlite.php    # Armar database.sqlite desde el dump .sql (lo usa composer setup)
php artisan migrate:fresh --force # Regenerar la DB por migraciones (create_* + fill_* desde 'doctrine')
php artisan tinker               # REPL interactivo
php artisan route:list           # Listar todas las rutas
php artisan pail                 # Ver logs en tiempo real
./vendor/bin/pint                # Formatear código (linter)
```

---

## Testing

```bash
composer test
# o directamente:
php artisan test
```

El script `test` limpia la configuración cacheada antes de ejecutar los tests con PHPUnit.

---

## Próximos pasos sugeridos

1. **Áreas grandes** (cientos de establecimientos): el merge final del reporte por área carga todos los PDFs en memoria → evaluar merge incremental / subir memoria / limpieza por TTL de `storage/app/longops/`.
2. Portar el path de datos por `callback1query` (Doctrine) de `WrapTcpLib` a Eloquent (hoy se usa `callback1hash`).
3. Portar el código legacy en desuso de `ReportMin02` (`mylongprocActions`, `get_cnt1data`, `cbk_firma`) si se necesitan procesos largos avanzados o firmas.
4. (Opcional) Autenticación / autorización si los reportes deben quedar detrás de login.

---

_Generado a partir del análisis del código del proyecto._
