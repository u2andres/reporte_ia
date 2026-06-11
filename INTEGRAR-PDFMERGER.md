# Integración de PDFMerger (combinar PDFs)

Guía de la librería **`App\Libraries\PDFMerger`** para combinar varios PDFs (o páginas sueltas) en uno solo, y del endpoint de prueba.

`PDFMerger` usa **TCPDI** (Paul Nicholls) sobre el TCPDF de Composer. Se eligió TCPDI (y no FPDI) porque importa páginas de PDFs con cross-reference / object streams comprimidos — aunque, como se ve más abajo, hay un límite con los PDFs de tc-lib-pdf.

---

## Tabla de contenidos

- [Archivos y dependencias](#archivos-y-dependencias)
- [Cómo se completó el bundle](#cómo-se-completó-el-bundle)
- [API de PDFMerger](#api-de-pdfmerger)
- [Endpoint de prueba](#endpoint-de-prueba)
- [Compatibilidad de formatos (importante)](#compatibilidad-de-formatos-importante)
- [Por qué TCPDI y no FPDI](#por-qué-tcpdi-y-no-fpdi)
- [Notas y pendientes](#notas-y-pendientes)

---

## Archivos y dependencias

- [app/Libraries/PDFMerger.php](app/Libraries/PDFMerger.php) — la clase (namespace `App\Libraries`).
- `app/Libraries/tcpdf/` — TCPDI bundleado:
  - `tcpdi.php` (define `FPDF extends TCPDF`, `TCPDI extends FPDF_TPL`)
  - `tcpdi_parser.php` (parser de PDFs)
  - `fpdf_tpl.php` (extensión de templates, `FPDF_TPL`)
  - `include/tcpdf_filters.php` (+ otros archivos del bundle pauln, no todos usados)
- `tecnickcom/tcpdf` (Composer) — TCPDF base sobre el que extiende TCPDI.

> No se usa ningún paquete Composer de merge. TCPDI es código bundleado en `app/Libraries/tcpdf/`.

---

## Cómo se completó el bundle

El bundle llegó **incompleto** y hubo que completarlo:

1. Faltaba **`app/Libraries/tcpdf/fpdf_tpl.php`** (lo requiere `tcpdi.php`; `TCPDI extends FPDF_TPL`). → agregado desde el paquete pauln/tcpdi.
2. Faltaba toda la carpeta **`app/Libraries/tcpdf/include/`** (la requiere `tcpdi_parser.php` → `include/tcpdf_filters.php`). → se copió el `include/` del paquete pauln/tcpdi.

> Detalle: usar el `TCPDF_FILTERS` de Composer (TCPDF 6.11) en vez del de pauln **no** funciona (el `FlateDecode` falla). Hay que usar el `include/tcpdf_filters.php` que viene con pauln/tcpdi.

Los requires internos de `tcpdi.php` son relativos pero PHP los resuelve contra el directorio del archivo que hace el `require` (`app/Libraries/tcpdf/`), así que con los archivos presentes carga bien. `PDFMerger` carga TCPDI con `require_once __DIR__ . '/tcpdf/tcpdi.php'`.

---

## API de PDFMerger

```php
use App\Libraries\PDFMerger;

$merger = new PDFMerger();
$pdf = $merger
    ->addPDF($rutaAbsoluta1, 'all')      // todas las páginas
    ->addPDF($rutaAbsoluta2, '1,3,4')    // páginas sueltas
    ->addPDF($rutaAbsoluta3, '1-2')      // rango
    ->merge('string', 'salida.pdf');     // modo de salida
```

- **`addPDF($filepath, $pages)`** — `$pages`: `'all'` | `'1,3,6'` | `'12-16'` (combinables: `'1,3,6,12-16'`). Lanza excepción si el archivo no existe.
- **`merge($modo, $nombre)`** — modos: `'string'` (devuelve el PDF como texto), `'file'` (escribe a disco), `'browser'`, `'download'`.

---

## Endpoint de prueba

| Pieza | Detalle |
|-------|---------|
| Método | [ReporteController::mergeTest()](app/Http/Controllers/ReporteController.php) |
| Ruta | `GET /reporte/merge-test` (nombre `reporte.merge.test`) |

Genera 3 PDFs con **TCPDF clásico** y los combina con `PDFMerger`, devolviendo el PDF resultante (`inline`). Verificado: **200 OK**, 3 páginas combinadas.

```bash
php artisan serve   # http://localhost:8000/reporte/merge-test
```

---

## Compatibilidad de formatos (importante)

⚠️ **No todos los PDFs se pueden mergear con estas librerías PHP.**

| Origen del PDF | Estructura | ¿PDFMerger (tcpdi) lo combina? | ¿FPDI libre? |
|----------------|-----------|-------------------------------|--------------|
| **TCPDF clásico** (`cursos`, `rpt_min02`, WrapTcpLib) | xref tradicional | ✅ **Sí** | ✅ Sí |
| **tc-lib-pdf** (`reporte/test`, los `test_0X.pdf`) | PDF-1.7 con XRef/Object streams comprimidos | ❌ No (`gzuncompress: invalid code`) | ❌ No |

Conclusión: para el caso real del proyecto (combinar los `rpt_min02` por establecimiento en el **reporte por área**) **PDFMerger sirve**, porque esos reportes son TCPDF clásico. Lo que **no** se puede mergear con PHP son las salidas de tc-lib-pdf (incluidos los `app/Libraries/test/test_0X.pdf` que vinieron de ejemplo).

Para mergear PDFs de tc-lib-pdf haría falta una **herramienta externa** (Ghostscript / qpdf / pdftk) o **regenerarlos** como TCPDF clásico.

---

## Por qué TCPDI y no FPDI

Se probó primero `setasign/fpdi` (Opción B): es el paquete moderno y mantenido, pero su **parser gratuito no soporta** la compresión de cross-reference (PDF 1.5+) — falla con `CrossReferenceException`. Como los `test_0X.pdf` son tc-lib-pdf comprimidos, FPDI no servía para ellos.

Se volvió a **TCPDI** (Opción A), que en teoría soporta esa compresión. En la práctica, **tampoco** logra inflar los object streams de tc-lib-pdf (`gzuncompress: invalid code`), pero **sí** funciona con TCPDF clásico. Para los reportes del proyecto (TCPDF clásico), tanto TCPDI como FPDI sirven; se dejó **TCPDI**. `setasign/fpdi` se **desinstaló** (`composer remove setasign/fpdi`).

---

## Notas y pendientes

- En `app/Libraries/tcpdf/include/` quedaron archivos del bundle pauln que TCPDI no usa para el merge (solo necesita `tcpdf_filters.php`); no molestan.
- Si se necesita mergear salidas de **tc-lib-pdf**, evaluar Ghostscript/qpdf/pdftk (binario externo).
- Caso de uso natural: armar el **reporte por área** (`min_02_area`) combinando los `rpt_min02` de cada establecimiento (TCPDF clásico → compatibles con PDFMerger).

---

_Documentación de la integración de PDFMerger — proyecto Lrvl_curso_reporte._
