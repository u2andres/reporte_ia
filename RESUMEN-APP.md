# RESUMEN-APP — Bitácora de desarrollo

Bitácora del armado de **Lrvl_curso_reporte** (Laravel 12), reconstruida a partir de los commits (2026-06-05 → 2026-06-13). El objetivo del proyecto fue **migrar un reporteador legacy** (Symfony 1 + Doctrine 1 + TCPDF) a Laravel y, sobre eso, armar el reporte **"Planta Completa Valorizada" (min_pof_02)** con datos reales de MySQL.

> Detalle técnico fino: ver `CHAT.md` (bitácora extensa) y las guías `INTEGRAR-TC-LIB.md`, `INTEGRAR-WRAPTCPLIB.md`, `INTEGRAR-PDFMERGER.md`, `INTEGRAR-JQUERY-UI.md`.

---

## Línea de tiempo (commits)

| # | Commit | Fecha | Hito |
|---|--------|-------|------|
| 1 | `2dd0a67` | 06-05 | Sistema inicial (esqueleto Laravel 12) |
| 2 | `b3c1f8f` | 06-05 | Documentación inicial de la app (README) |
| 3 | `dd0ce15` | 06-05 | Instalación + test de **tc-lib-pdf** (`/reporte/test`) |
| 4 | `4d01705` | 06-06 | Instalación **TCPDF clásico** + wrapper `WrapTcpLib` (`/reporte/cursos`) |
| 5 | `5e07565` | 06-06 | Ajuste de configuración por defecto del reporteador |
| 6 | `aaf765a` | 06-06 | Inicio de **`rpt_min02`** (presentación, sin ruta aún) |
| 7 | `ebbd7d5` | 06-07 | `rpt_min02` testeable en `/reporte/min_02` |
| 8 | `e399691` | 06-07 | Datos **mockeados** (hashes) del reporte |
| 9 | `9ee7de0` | 06-07 | Ajuste de path de imágenes (logo) + contexto |
| 10 | `ea5e1cc` | 06-07 | **Modelos Eloquent** (`PofP`+`Cargo/Turno/Establecimiento/Historia`) desde esquemas Doctrine + seeders + consultas |
| 11 | `a1003a2` | 06-07 | Conexión **MySQL `doctrine`** para los modelos |
| 12 | `9edd672` | 06-08 | Parámetros **`estab`/`anio`** en ruta y controlador |
| 13 | `8515d9a` | 06-09 | **Catálogos** `650_AREA` / `657_DISTRITO` / `664_MODALIDAD` (nombres reales en el encabezado) |
| 14 | `be6ea69` | 06-09 | **jQuery + jQuery UI** + vista/JS con barra de progreso (`/reporte/min_02-demo`) |
| 15 | `228b82c` | 06-11 | Librería de **merge de PDFs** (`/reporte/merge-test`) |
| 16 | `295a799` | 06-11 | Diálogo **longOps** (jQuery UI) + test mínimo (`/reporte/longops-demo`) |
| 17 | `c351d3d` | 06-11 | Documentación del longOps |
| 18 | `4e95391` | 06-12 | **Reporte por área**: controlador/vista de testeo (`/reporte/longops-area-demo`) |
| 19 | `6d2edf5` | 06-13 | Reporte por área documentado + **paginación continua** multi-establecimiento |
| 20 | `f38f720` | 06-13 | Fix de la **numeración** (se reiniciaba entre tandas) + doc |

---

## Etapas

### 1. Base y primer stack PDF (06-05)
App Laravel 12 recién instalada (`2dd0a67`), README documentado (`b3c1f8f`). Se integró **tc-lib-pdf** (API moderna OO) con un endpoint de prueba `/reporte/test` (`dd0ce15`). Gotcha: necesita `K_PATH_FONTS`; se importa una fuente con `php artisan pdf:import-font`.

### 2. TCPDF clásico + WrapTcpLib (06-06)
Se incorporó el reporteador legacy: **TCPDF clásico** + el wrapper `App\Libraries\WrapTcpLib` (portado de Symfony1/Doctrine/TCPDF, configurado por YAML). Endpoint `/reporte/cursos` (`4d01705`, `5e07565`). Las dos librerías PDF conviven (comparten `K_PATH_FONTS` con formatos distintos → se define por-controlador, no global).

### 3. Reporte `rpt_min02` con mocks (06-06 → 06-07)
Se armó el reporte real "Planta Completa Valorizada" (orquestador `App\Libraries\Reports\ReportMin02`) con datos **mockeados** (`json_decode`) en lugar de las consultas Doctrine originales (`aaf765a` → `9ee7de0`). Endpoint `/reporte/min_02`. Se ajustó el logo de cabecera (`K_PATH_IMAGES` a un dir del proyecto).

### 4. Datos reales: Eloquent + MySQL (06-07)
Se mapearon las tablas legacy a **modelos Eloquent** desde los esquemas Doctrine 1.2 (`PofP`(680) + `CargoPofP`(652) `TurnoPofP`(686) `EstablecimientoPofP`(658) `HistoriaPofP`(661)), con seeders y las consultas portadas (`get_sumt1cargo`/`get_hmix1cargo`/`get_h1cargo1area`) — `ea5e1cc`. Luego se conectó a la **base MySQL real** (`sdo_db`) vía una conexión dedicada **`doctrine`** (`a1003a2`); los modelos pasan a solo lectura sobre datos reales.

### 5. Parametrización y catálogos (06-08 → 06-09)
El reporte dejó de estar fijo: parámetros **`estab`/`anio`** por ruta o query (`9edd672`). Se modelaron los **catálogos** Área(650)/Modalidad(664)/Distrito(657) para mostrar nombres reales en el encabezado (`8515d9a`). Hallazgo: el área se deriva vía la modalidad (`664.c664_650_id`), porque `658.area` viene vacía; el D.E. real es `657.c657_de`.

### 6. Frontend de progreso (06-09 → 06-11)
- **jQuery 3.7 + jQuery UI 1.13** integrados a Vite; layout `layouts.longproc` con barra de progreso (`LongProc`) y demo `/reporte/min_02-demo` (`be6ea69`).
- Librería **PDFMerger** (TCPDI) para combinar PDFs; `/reporte/merge-test` (`228b82c`).
- Helper **longOps** (diálogo modal jQuery UI con barra que consulta un backend por AJAX); `/reporte/longops-demo` (`295a799`, `c351d3d`).

### 7. Reporte por área + paginación continua (06-12 → 06-13)
El caso real: combinar el `rpt_min02` de **todos los establecimientos de un área** en un PDF, procesando en tandas (longOps) por la cantidad de establecimientos. Vista/backend `/reporte/longops-area-demo` + `longopsBackendArea` (`4e95391`). Se agregó la **numeración de páginas continua** entre establecimientos (`6d2edf5`) y se **corrigió** un bug por el que el contador se reiniciaba en cada tanda (`f38f720`, detectado con área **T** = 47 establecimientos → 2 tandas → 88 páginas continuas).

### 8. Tablas locales SQLite + link en la página de inicio (06-23)
Se sumaron **migraciones de relleno (`fill_*`)** que copian datos desde la conexión `doctrine` (origen legacy) a tablas propias en **SQLite**: `680_POF_P`, `652_CARGO_POF_P`, `650_AREA_POF_P`, `664_MODALIDAD_POF_P`. También se reemplazó el botón "Deploy now" de la página de inicio (`welcome.blade.php`) por un link **"Armado de los Reportes por Área"** que apunta a `route('reporte.longops.areaDemo')` → `/reporte/longops-area-demo`.

**Patrón de las migraciones `fill_*`** (copia `doctrine` → `sqlite`):
- El origen trae `NULL` en columnas declaradas NOT NULL → `SQLSTATE[23000]: NOT NULL constraint failed`. Según el caso:
  - **Booleano con `->default(...)`** (`c652_incrementa`, `c652_reduce`): coalescer en el insert con `?? true`.
  - **Código / nomenclador sin default lógico** (`c650_002_id`, `c664_012_id`): hacer la columna `->nullable()` en la migración de creación (inventar un valor falsearía el dato).
- Otros arreglos: imports `use Illuminate\Support\Facades\DB;` y `use ...\Collection;`; `DB::connection('sqlite')->table(...)` (no `::table`); `truncate()` al inicio del `up()` para idempotencia (evita `UNIQUE constraint failed` por corridas parciales).
- Tras cambiar el esquema de una creación ya aplicada: `php artisan migrate:fresh --force` (el `fill_pof_p` tarda ~4 min).

> **Entorno**: el `php` del PATH es 5.6.35; usar el de XAMPP `c:/eid/Xampp_82/php/php.exe` (PHP 8.2) para artisan/composer.

---

## Estado actual (endpoints)

| Endpoint | Qué hace |
|----------|----------|
| `GET /reporte/test` | PDF de prueba con **tc-lib-pdf** |
| `GET /reporte/cursos` | Reporte simple con **WrapTcpLib** (datos de ejemplo) |
| `GET /reporte/min_02/{estab?}/{anio?}` | **"Planta Completa Valorizada"** (datos reales MySQL, nombres de catálogo) |
| `GET /reporte/min_02-demo` | Demo barra de progreso (`LongProc`) generando varios `rpt_min02` |
| `GET /reporte/merge-test` | Prueba de **PDFMerger** (TCPDI) |
| `GET /reporte/longops-demo` | Demo del diálogo **longOps** |
| `GET /reporte/longops-area-demo` | **Reporte por área** (longOps): combina los `rpt_min02` del área en un PDF |

## Stack
Laravel 12 · PHP 8.2 (XAMPP) · MySQL/MariaDB (conexión `doctrine`, datos reales) + SQLite (tablas propias de Laravel) · tc-lib-pdf 8 · TCPDF 6 (clásico) + WrapTcpLib · TCPDI/PDFMerger · jQuery 3.7 + jQuery UI 1.13 · Vite 7 · Tailwind 4.

## Pendientes
- Áreas muy grandes (cientos de establec.): el merge final carga todo en memoria → evaluar merge incremental / TTL de limpieza de `storage/app/longops/`.
- Portar el path `callback1query` (Doctrine) de `WrapTcpLib` a Eloquent (hoy se usa `callback1hash`).
- Portar el código legacy en desuso de `ReportMin02` (`mylongprocActions`, `get_cnt1data`, `cbk_firma`).

---

_Bitácora generada a partir de los 20 commits del repo (2026-06-05 → 2026-06-13). Para el detalle de cada cambio ver `CHAT.md` y las guías `INTEGRAR-*.md`._
