# Integración de WrapTcpLib (TCPDF clásico) en Laravel

Guía de la migración de la clase legacy **`WrapTcpLib`** para que funcione en este proyecto Laravel 12, reutilizándola sobre la librería **TCPDF clásico** (`tecnickcom/tcpdf`).

`WrapTcpLib` es un "reporteador" genérico (cabecera/pie, grillas, grupos, marca de agua, salto de página automático, columnas calculadas) configurado por YAML. Fue escrito originalmente para un proyecto **Symfony 1 + Doctrine 1 + TCPDF clásico**, por lo que requirió adaptación.

> Para el contexto del diagnóstico inicial (por qué NO funcionaba con `tc-lib-pdf`) ver el historial; esta guía documenta la solución adoptada (**Opción A**: reutilizar con TCPDF clásico).

---

## Tabla de contenidos

- [Por qué TCPDF clásico y no tc-lib-pdf](#por-qué-tcpdf-clásico-y-no-tc-lib-pdf)
- [Cambios realizados en la migración](#cambios-realizados-en-la-migración)
  - [1. Instalación de TCPDF clásico](#1-instalación-de-tcpdf-clásico)
  - [2. Namespace / PSR-4](#2-namespace--psr-4)
  - [3. Clase base (extends)](#3-clase-base-extends)
  - [4. Dependencias de framework legacy](#4-dependencias-de-framework-legacy)
  - [5. Corrección de bug](#5-corrección-de-bug)
  - [6. Conflicto de fuentes (K_PATH_FONTS)](#6-conflicto-de-fuentes-k_path_fonts)
- [Harness de prueba](#harness-de-prueba)
- [Cómo funciona el reporte (flujo)](#cómo-funciona-el-reporte-flujo)
- [Estructura del YAML de configuración](#estructura-del-yaml-de-configuración)
- [Cómo probar](#cómo-probar)
- [Verificación realizada](#verificación-realizada)
- [Segundo reporte: rpt_min02 (orquestador)](#segundo-reporte-rpt_min02-orquestador)
- [Capa de datos: Eloquent, MySQL real y parametrización (A + B + C + D)](#capa-de-datos-eloquent-mysql-real-y-parametrización-a--b--c--d)
- [Pendientes y advertencias](#pendientes-y-advertencias)

---

## Por qué TCPDF clásico y no tc-lib-pdf

La clase `extends` la clase PDF base, pero por dentro llama a métodos del **TCPDF clásico** (`writeHTMLCell`, `Cell`, `MultiCell`, `SetFont`, `SetFillColor`, `startTransaction`, `rollbackTransaction`, `getMargins`, `SetMargins`, `Header`, `Footer`, etc.). **Ninguno de esos métodos existe en `tc-lib-pdf`** (que tiene una API distinta y orientada a objetos).

Por eso, hacer que la clase corra implicó volver a su librería original: **`tecnickcom/tcpdf`**. Las dos librerías conviven en el proyecto:

| Endpoint | Librería | Clase |
|----------|----------|-------|
| `/reporte/test` | tc-lib-pdf | `Com\Tecnick\Pdf\Tcpdf` |
| `/reporte/cursos` | TCPDF clásico | `App\Libraries\WrapTcpLib` (extiende `\TCPDF`) |

---

## Cambios realizados en la migración

### 1. Instalación de TCPDF clásico

```bash
composer require tecnickcom/tcpdf
```

Versión instalada: **6.11.3**. A diferencia de tc-lib-pdf, **TCPDF clásico trae sus fuentes incorporadas** (helvetica, times, courier, etc.), así que no hay que importar fuentes.

> ⚠️ Recordar usar el PHP 8.2 de XAMPP para composer:
> `c:\eid\Xampp_82\php\php.exe "<ruta>\composer.phar" require tecnickcom/tcpdf`

### 2. Namespace / PSR-4

El archivo está en `app/Libraries/WrapTcpLib.php` pero declaraba `namespace App\Http\Libraries;`. Con el PSR-4 de Laravel (`App\` → `app/`) eso no resuelve (composer avisaba: _"does not comply with psr-4 ... Skipping"_).

**Corregido a:**

```php
namespace App\Libraries;
```

### 3. Clase base (extends)

```php
// Antes
use Com\Tecnick\Pdf\Tcpdf;
class WrapTcpLib extends Tcpdf

// Después
use TCPDF;
use Symfony\Component\Yaml\Yaml;
class WrapTcpLib extends TCPDF
```

### 4. Dependencias de framework legacy

La clase usaba clases de Symfony 1 / Doctrine 1 que no existen en Laravel:

| Antes (Symfony 1 / Doctrine) | Después (Laravel / Symfony components) |
|------------------------------|----------------------------------------|
| `sfYaml::load($file)` | `Yaml::parseFile($file)` (`symfony/yaml`, ya presente) |
| `sfException` | `\RuntimeException` |
| `Doctrine_Core::HYDRATE_ARRAY` | _(no portado — ver pendientes)_ |

`symfony/yaml` ya venía como dependencia transitiva de Laravel, no hubo que instalar nada.

### 5. Corrección de bug

En `sc_grid()` y `sc_grp1query()` se referenciaba la variable inexistente `$callback4query` (la correcta es `$callback1query`):

```php
// Antes
$q = call_user_func($callback4query, $this);
// Después
$q = call_user_func($callback1query, $this);
```

### 6. Conflicto de fuentes (K_PATH_FONTS)

Este fue el punto más delicado. **tc-lib-pdf y el TCPDF clásico comparten la misma constante global `K_PATH_FONTS`**, pero buscan formatos de fuente distintos:

- tc-lib-pdf → `arial.json` / `arial.z` (en `storage/fonts/`)
- TCPDF clásico → `helvetica.php` (en su carpeta `vendor/.../tcpdf/fonts/`)

El TCPDF clásico **no tiene fallback**: si `K_PATH_FONTS` apunta a otro lado, no encuentra sus fuentes y falla.

**Solución:** no definir `K_PATH_FONTS` globalmente. Como las constantes `define()` son **por-request** (se limpian en cada request, tanto en `artisan serve` como en php-fpm), cada controlador la maneja según su librería:

```php
// app/Providers/AppServiceProvider.php -> boot()
// NO se define K_PATH_FONTS aquí.

// ReporteController::test()  (tc-lib-pdf)
if (! defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', storage_path('fonts'));
}

// ReporteController::cursos()  (TCPDF clásico / WrapTcpLib)
// No se define -> TCPDF se autoconfigura a sus fuentes bundled.
```

---

## Harness de prueba

Para poder testear el reporteador se crearon tres piezas:

| Archivo | Rol |
|---------|-----|
| [app/Libraries/config/report/cursos.yml](app/Libraries/config/report/cursos.yml) | Configuración del reporte (márgenes, estilos, columnas) |
| [app/Libraries/Reports/CursoReportData.php](app/Libraries/Reports/CursoReportData.php) | Datos de ejemplo (5 cursos) + columna calculada (% aprobación) |
| [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php) | Método `cursos()` que arma y devuelve el PDF |

> **Ubicación de los YAML:** los archivos de configuración viven en
> `app/Libraries/config/report/`. El método pasa ese directorio explícitamente
> a `ColoredTable()` (3er argumento) para no depender del default interno de
> `WrapTcpLib`.

Ruta: `GET /reporte/cursos` (nombre `reporte.cursos`).

El método del controlador es mínimo:

```php
public function cursos(): \Illuminate\Http\Response
{
    $pdf = new WrapTcpLib('P', 'mm', 'A4', true, 'UTF-8');

    $dirYaml = app_path('Libraries/config/report') . DIRECTORY_SEPARATOR;
    $pdf->ColoredTable('cursos.yml', false, $dirYaml);  // arma todo desde el YAML

    $raw = $pdf->Output('cursos.pdf', 'S');             // 'S' = devolver como string

    return response($raw, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="cursos.pdf"',
    ]);
}
```

---

## Cómo funciona el reporte (flujo)

`ColoredTable($yaml)` es el punto de entrada. El flujo interno:

1. **`get_cfg4yaml()`** — carga el YAML, fija metadatos, márgenes, fuentes y las "zonas" (header / footer / row / group / grid).
2. **`sc_grid()`** — recorre las grillas definidas en `grid:`.
3. Para cada grilla, **`adj_anycell()`** prepara celdas, grupos y parámetros de estilo, y luego:
4. **`sc_data1row()`** (fuente `callback1hash`) recorre las filas:
   - **`ck_eopage()` / `ck_row1page()`** calculan si la fila entra en la página; si no, hacen `AddPage()` y re-imprimen la cabecera de tabla (`mk_header1table()`).
   - Por cada celda escribe el dato con `writeHTMLCell()`; si la celda es **calculada** (su `key1data` no existe en la fila) invoca su `callback`.
5. **`Header()` / `Footer()`** se ejecutan automáticamente en cada página (con callback propio si se define, o el de TCPDF por defecto).

Las **columnas calculadas** se resuelven así: si `key1data` no está en la fila de datos, se llama `call_user_func($cell['callback'], $pdf, $row, true)`.

---

## Estructura del YAML de configuración

Esquema usado por `get_cfg4yaml()` (ver [cursos.yml](app/Libraries/config/report/cursos.yml) completo):

```yaml
config1default:   # metadatos y defaults del documento (formato, fuentes, márgenes)
margin:           # márgenes efectivos (obligatorio)
header1zone:      # estilo de la cabecera de tabla (fillcolor, font, l1cell, min1height...)
footer1zone: ~    # ~ = null -> usa el Footer() por defecto
row1zone:         # estilo de las filas (incluye l_fill para alternar fondo)
group1zone: ~     # estilo de cabeceras de grupo (si hay agrupamiento)
grid1zone: ~      # estilo de grilla
grid:             # una o más grillas
  cursos:
    data:
      callback1hash: [ 'App\Libraries\Reports\CursoReportData', getCursos ]
    cell:
      col_id:    { width: 18, key1data: id,         header: "ID",        align_data: C }
      col_nombre:{ width: 92, key1data: nombre,     header: "Curso",     align_data: L }
      col_insc:  { width: 30, key1data: inscriptos, header: "Inscriptos",align_data: C }
      col_aprob: { width: 30, key1data: aprobados,  header: "Aprobados", align_data: C }
      col_pct:   { width: 20, key1data: pct,        header: "%",
                   callback: [ 'App\Libraries\Reports\CursoReportData', calcPorcentaje ] }
```

Puntos clave:

- Los **callbacks** se expresan como `[ 'Clase\\Con\\Namespace', 'metodo' ]` (compatibles con `call_user_func`). Por eso `CursoReportData` usa métodos **estáticos**.
- La **suma de `width`** debe coincidir con el ancho útil de la página (A4 vertical con márgenes 10+10 = **190 mm**).
- `footer1zone: ~`, `group1zone: ~`, etc. usan `~` (null de YAML) cuando no se personalizan.

---

## Cómo probar

```powershell
c:\eid\Xampp_82\php\php.exe artisan serve
```

Abrir **http://localhost:8000/reporte/cursos** → se ve el PDF con la tabla de cursos.

Para forzar descarga en vez de mostrarlo, cambiar `inline` por `attachment` en el `Content-Disposition` del controlador.

---

## Verificación realizada

- **Generación directa** (bootstrap real de Laravel): PDF válido, `%PDF-1.7`, ~8.9 KB, 1 página, metadatos embebidos.
- **Handler estricto de Laravel:** `ColoredTable()` corre completo sin disparar excepciones por notices/warnings → el camino del reporte está limpio.
- **HTTP real:** `GET /reporte/cursos` responde **200 OK** con `Content-Type: application/pdf`.

---

## Segundo reporte: rpt_min02 (orquestador)

`rpt_min02` ("Planta Completa Valorizada", tag `min_pof_02`) es un reporte real, más complejo que `cursos`. A diferencia de `cursos` (que usa `WrapTcpLib` directo), introduce un **orquestador** que encapsula toda la configuración y los datos.

### Piezas

| Archivo | Rol |
|---------|-----|
| [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php) (`rpt_min02()`) | Arma el array `$h_genPdf` (área, establecimiento, header) y delega en el orquestador |
| [app/Libraries/Reports/ReportMin02.php](app/Libraries/Reports/ReportMin02.php) | Orquestador: crea `WrapTcpLib`, carga el YAML, define callbacks de header/footer y de cada grilla |
| [app/Libraries/config/report/multig_min02_a4p.yml](app/Libraries/config/report/multig_min02_a4p.yml) | Configuración: cabecera variable + grillas de Conducción / Ejecución / Horas Cátedra + subtotales y total |
| [resources/reports/images/logo_ciudad.png](resources/reports/images/logo_ciudad.png) | Logo de la cabecera |

Ruta: `GET /reporte/min_02` (nombre `reporte.rpt_min02`).

### Patrón orquestador

El controlador **no** usa `WrapTcpLib` directamente: construye un hash de configuración y se lo pasa a `ReportMin02`, cuyo `generarImpresion()` hace el trabajo:

```php
$genPdf = new ReportMin02($h_genPdf);
$raw    = $genPdf->generarImpresion();   // crea WrapTcpLib, carga YAML, devuelve string
```

Dentro de `generarImpresion()`:

```php
$pdf = new WrapTcpLib($orientation, 'mm', $pg_size, true, 'UTF-8', false);
$pdf->gst_huser($this->h_listado);                       // expone la config a los callbacks
$dir1yaml  = dirname(__FILE__) . '/../config/report/';   // -> app/Libraries/config/report/
$ycfg_file = 'multig_min02_a4p.yml';                     // se arma según pg_size+orientation
$pdf->ColoredTable($ycfg_file, $prelim, $dir1yaml, $watermark);
return $pdf->Output($output_file . '.pdf', 'S');
```

Los **callbacks** del YAML (`mk_header`, `mk_footer`, `cbk_conduccion`, `cbk_total`, etc.) son métodos estáticos de `ReportMin02`, referenciados como `[ 'App\Libraries\Reports\ReportMin02', 'metodo' ]`. Acceden a la configuración con `$pdf->gst_huser(null, 'clave')`.

### Datos: de mocks a Eloquent

Originalmente los `cbk_*` devolvían datos fijos vía `json_decode()` (mocks de las consultas Doctrine `PofPTable`). **Ahora consultan la base de datos** mediante métodos Eloquent en el modelo `PofP` — ver [Capa de datos](#capa-de-datos-eloquent-mysql-real-y-parametrización-a--b--c--d). Los `$json_mock` quedaron **comentados** como referencia del formato esperado.

### Logo y K_PATH_IMAGES

`mk_header()` imprime un logo con `$pdf->Image(K_PATH_IMAGES . $headerdata['logo'], ...)`. Para no depender de `vendor/` (que composer puede borrar), el logo se movió a `resources/reports/images/` y el controlador define la constante **antes** de instanciar el reporte:

```php
if (! defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', resource_path('reports/images') . DIRECTORY_SEPARATOR);
}
```

> Es importante que esta definición ocurra **antes** de crear `WrapTcpLib` (que carga TCPDF, el cual autoconfigura `K_PATH_IMAGES` a `vendor/.../tcpdf/` si no está definida). Como las constantes `define()` son por-request, no hay contaminación entre endpoints.

### Verificación

- Aislado (= request real): **49.189 bytes**, `%PDF-1.7`, con logo cargado desde `resources/reports/images/`.
- HTTP real `GET /reporte/min_02`: **200 OK**, `application/pdf`. Verificado que tras pegarle a `/reporte/cursos` en el mismo worker, `min_02` sigue trayendo el logo (constantes por-request, sin filtración).

---

## Capa de datos: Eloquent, MySQL real y parametrización (A + B + C + D)

Para que `rpt_min02` deje de usar mocks y consulte datos reales, se mapearon las tablas legacy (Doctrine 1.2) a modelos Eloquent (A), se portaron las consultas (B), se conectó a la base MySQL real vía una conexión dedicada (C) y se parametrizó el reporte por establecimiento/año (D).

### A) Modelos, migraciones y seeder

Mapeo fiel del esquema Doctrine (nombres físicos `cNNN_*`, PK, tipos, alias):

| Modelo | Tabla | PK | Doctrine origen |
|--------|-------|----|-----------------|
| [PofP](app/Models/PofP.php) | `680_POF_P` | `c680_id` | PofP |
| [CargoPofP](app/Models/CargoPofP.php) | `652_CARGO_POF_P` | `c652_id` | CargoPofP |
| [TurnoPofP](app/Models/TurnoPofP.php) | `686_TURNO_POF_P` | `c686_id` (char, no-auto) | TurnoPofP |
| [EstablecimientoPofP](app/Models/EstablecimientoPofP.php) | `658_ESTABLECIMIENTO_POF_P` | `c658_id` | EstablecimientoPofP |
| [HistoriaPofP](app/Models/HistoriaPofP.php) | `661_HISTORIA_POF_P` | `c661_id` | HistoriaPofP |

- `PofP` define los `belongsTo` (`establecimiento`, `turno`, `cargo`, `historia`). El **tipo de cargo** (C/E/H) es `c652_685_id` en `CargoPofP`.
- Las relaciones a modelos aún no creados (Area, TipoCargo, Modalidad, etc.) quedan comentadas.
- El seeder [PofReportSeeder](database/seeders/PofReportSeeder.php) reproduce los mocks: 4 turnos, 10 cargos de Conducción (tipo `C`), establecimiento 1400 e historia 2020, y 10 filas en `680_POF_P` cuyas cantidades **suman 90** (el mismo total que mockeaba el reporte).

```bash
php artisan migrate
php artisan db:seed --class=PofReportSeeder
```

> **Nota de nombres:** los modelos usan el sufijo `PofP` (igual que las clases Doctrine). El archivo debe respetar ese case exacto (`CargoPofP.php`, no `CargoPofp.php`) para que PSR-4 funcione en Linux.

### B) Consultas portadas (en el modelo `PofP`)

Reemplazan a los métodos de `PofPTable` (Doctrine). Los `cbk_*` de `ReportMin02` ahora las invocan en lugar de los `json_decode`:

| Método | Reemplaza | Devuelve |
|--------|-----------|----------|
| `PofP::get_sumt1cargo($cod, $anio, $tipo=null)` | `get_sumt1cargo` | suma de `cantidad` (tipo null = todos) |
| `PofP::get_hmix1cargo($cod, $anio, $tipo, &$subtotal)` | `get_hmix1cargo` | hash de cargos (cnt actual/previo, diff, valoriz) |
| `PofP::get_h1cargo1area($anio, $area, &$totales)` | `get_h1cargo1area` | cargos por área + totales PI mensual/anual |

Usan las relaciones (`with(['cargo','turno'])`, `whereHas('cargo', ...)`) para filtrar por tipo de cargo y traer `cargo1d`/`turno1d`.

### Verificación (datos reales)

- `get_sumt1cargo(1400, 2020, 'C')` = **90**; año previo (2019) = 0.
- `get_hmix1cargo(...)` = 10 cargos, `cnt_actual` suma **90**; `cargo1d` = "75 - SUPERVISOR …", `turno1d` = "Completo".
- Reporte `GET /reporte/min_02` con datos desde la base: **49.189 bytes** — idéntico byte a byte a la versión con mocks (mismos datos → mismo PDF).

### C) Conexión a la base MySQL real (`doctrine`)

En el esquema Doctrine original estas tablas vivían en una conexión aparte (`connection: doctrine`). Se replicó esa idea: una **conexión dedicada** para las tablas legacy de MySQL, dejando las tablas propias de Laravel (users, sessions, cache, jobs) en SQLite.

- **`config/database.php`** → nueva conexión `doctrine` (driver `mysql`), parametrizada por variables `DB_DOCTRINE_*`:

  ```env
  DB_DOCTRINE_HOST=127.0.0.1
  DB_DOCTRINE_PORT=3306
  DB_DOCTRINE_DATABASE=<base>
  DB_DOCTRINE_USERNAME=<usuario>
  DB_DOCTRINE_PASSWORD=<password>
  ```

- Los 5 modelos declaran **`protected $connection = 'doctrine';`** → leen de MySQL. El resto de la app sigue en SQLite (no se toca la base real).

- **Solo lectura:** las tablas ya existen con datos. Para que `db:seed`/`migrate --seed` no escriba sobre ellas:
  - `PofReportSeeder` se **desregistró** de `DatabaseSeeder`.
  - `PofReportSeeder::run()` aborta si detecta que los modelos usan la conexión `doctrine` (guard de seguridad).

#### Verificación con datos reales

Probado contra una base real (MariaDB 10.1): conexión OK, las 5 tablas existen (`680_POF_P` ~33.7k filas) y las columnas coinciden con el mapeo. El reporte `GET /reporte/min_02` para el establecimiento 1400 / año 2020 trae **11 cargos de Conducción reales** (suma 90) y genera el PDF (`200 OK`, ~49 KB).

> ⚠️ **MariaDB/MySQL viejo:** la introspección de esquema de Laravel (`getColumnListing`, `migrate`, `db:show` sobre la conexión `doctrine`) falla por la columna `generation_expression` (sólo existe en MySQL 5.7+). **No afecta las consultas de datos** del reporte (SELECT/WHERE/aggregates normales); simplemente no usar comandos de *schema* contra `doctrine`.

### D) Reporte parametrizado (`estab` y `anio`)

El reporte ya no está fijo a un establecimiento/año. La ruta `reporte.rpt_min02` declara dos **parámetros opcionales de ruta**, y el controlador `rpt_min02(Request $request, $estab = null, $anio = null)` también acepta los mismos por **query string** como fallback:

```php
// routes/web.php
Route::get('/reporte/min_02/{estab?}/{anio?}', [ReporteController::class, 'rpt_min02'])
    ->name('reporte.rpt_min02');
```

| Parámetro | Significado | Default |
|-----------|-------------|---------|
| `estab` | código de establecimiento (`680_POF_P.c680_658_id` / PK de `658_*`) | `1400` |
| `anio`  | año de la POF (`c680_anio`) | `2020` |

```
GET /reporte/min_02                        -> estab 1400, año 2020
GET /reporte/min_02/3510                    -> estab 3510, año 2020 (anio default)
GET /reporte/min_02/3510/2019               -> estab 3510, año 2019
GET /reporte/min_02?estab=3510&anio=2020    -> equivalente, por query string
```

En Blade/código: `route('reporte.rpt_min02', ['estab' => 3510, 'anio' => 2020])`.

Flujo:

1. Resuelve `estab`/`anio`: primero parámetro de ruta, luego query string, luego defaults (`$estab ?? $request->query('estab', 1400)`).
2. Busca el establecimiento real: `EstablecimientoPofP::find($estab)`. Si no existe → **HTTP 404**.
3. Arma `$h_estab` con los datos reales del registro (nombre, CUE, escuela, dirección, etc.) y deriva `cod_area`, `anio_previo = anio - 1`, `anio_header = anio`, `cod_last1estab = estab`, `h_dt1area = [estab]`.
4. El archivo se nombra `rpt_min02_<estab>_<anio>.pdf`.

Verificado por HTTP: `/reporte/min_02` y `/reporte/min_02/1400/2020` → 200 (~49 KB); `/reporte/min_02/3510` y `/reporte/min_02/3510/2020` (156 filas, C/E/H) → 200 (~62 KB); query string equivalente → 200; `/reporte/min_02/999999999` → **404**.

> **Limitación (cosmética, no de datos):** los **datos numéricos** son correctos para cualquier estab/año, pero algunos campos **descriptivos** del encabezado no tienen tabla de nombres modelada: **Área** usa un mapa best-effort (`A`→Adultos, `D`→Gestión Privada; resto muestra "Área &lt;letra&gt;"), **Modalidad** muestra el código (falta catálogo 664) y **D.E.** muestra el `distrito_escolar_id`. Para nombres completos habría que modelar las tablas de catálogo (Área 650, Modalidad 664, Distrito 657) y resolverlos por join.

---

## Pendientes y advertencias

- **Path `callback1query` de WrapTcpLib (Doctrine) sin portar.** El reporteador `WrapTcpLib` aún tiene un camino de datos por `callback1query` (con `Doctrine_Core::HYDRATE_ARRAY`, en `sc_callback1row()` / `sc_grp1query()`) que no se migró. No se usa: tanto `cursos` como `rpt_min02` van por **`callback1hash`** (array de filas). Si en el futuro se necesita paginación por query, portar ese branch a Eloquent.

- **Cargos con `puntaje = null`** en el seeder → la columna **Valorización** sale 0. Para ver valorizaciones, cargar `c652_puntaje` en los cargos.

- **Ejecución / Horas Cátedra vacías:** el seeder solo carga Conducción (tipo `C`). Agregar filas tipo `E`/`H` para poblar esas secciones.

- **`ins_line2report()`** contiene una llamada a `writeHTMLCell()` con argumentos en posiciones de una API de TCPDF aún más vieja (pasa `$data` en la posición de `$x`). No está en el camino del reporte de cursos, pero si se usa ese método habrá que corregir el orden de parámetros.

- **Avisos del IDE** sobre "Property ... has no type information": son informativos (la clase legacy no declara tipos), no son errores.

- **Marca de agua / preliminar:** `ColoredTable($yaml, $l_prelim = true)` activa la marca de agua "NO VALIDO". No se probó en este harness.

- **Refs legacy en código muerto de `ReportMin02`:** `mylongprocActions::gst_hget1tag()` (solo en el branch `in_longproc = true`, que está en `false`) y los métodos `get_cnt1data()` / `cbk_firma()` (no referenciados desde el YAML). No afectan la ejecución actual, pero romperían si se activan. Habría que portarlos antes de usar "procesos largos" (multi-establecimiento) o las firmas.

---

_Documentación de la migración de WrapTcpLib — proyecto Lrvl_curso_reporte._
