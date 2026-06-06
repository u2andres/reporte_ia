# Integración de tc-lib-pdf en Laravel

Guía de cómo se integró la librería **[tecnickcom/tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf)** (v8.33) en este proyecto para la generación de reportes en PDF.

`tc-lib-pdf` es la sucesora moderna y orientada a objetos de **TCPDF** (mismo autor, Nicola Asuni). Su API **no es compatible** con la del TCPDF clásico.

---

## Tabla de contenidos

- [Instalación](#instalación)
- [El escollo: las fuentes](#el-escollo-las-fuentes)
- [Componentes de la integración](#componentes-de-la-integración)
  - [1. Constante K_PATH_FONTS](#1-constante-k_path_fonts)
  - [2. Comando para importar fuentes](#2-comando-para-importar-fuentes)
  - [3. Controlador de reportes](#3-controlador-de-reportes)
  - [4. Ruta](#4-ruta)
- [Cómo probar](#cómo-probar)
- [Anatomía de la generación de un PDF](#anatomía-de-la-generación-de-un-pdf)
- [Receta: mostrar vs. descargar](#receta-mostrar-vs-descargar)
- [Usar otras fuentes](#usar-otras-fuentes)
- [Ejemplos oficiales útiles](#ejemplos-oficiales-útiles)
- [Problemas frecuentes](#problemas-frecuentes)

---

## Instalación

La librería ya está declarada en `composer.json`:

```json
"require": {
    "tecnickcom/tc-lib-pdf": "^8.33"
}
```

Al instalarla, Composer trae también todas sus dependencias modulares: `tc-lib-pdf-font`, `tc-lib-pdf-image`, `tc-lib-pdf-page`, `tc-lib-color`, `tc-lib-barcode`, `tc-lib-unicode`, etc.

```bash
composer require tecnickcom/tc-lib-pdf
```

> ⚠️ **PHP 8.2 obligatorio.** El binario `php` del PATH de este equipo es PHP 5.6. Hay que usar siempre el de XAMPP 8.2:
> `c:\eid\Xampp_82\php\php.exe`

---

## El escollo: las fuentes

El mayor obstáculo al integrar esta librería en Windows es que **el paquete se instala sin fuentes precompiladas**. La carpeta `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/` queda **vacía**.

tc-lib-pdf necesita, para cada fuente, un par de archivos en un formato propio:

- `<fuente>.json` — métricas y descripción de la fuente
- `<fuente>.z` — datos de la fuente comprimidos (para embeber)
- `<fuente>.ctg.z` — mapa de caracteres (para fuentes Unicode)

El proceso oficial para generarlos (`make deps fonts`) está orientado a Linux (usa `make`, `fontforge`, descargas de mirrors) y no es práctico en Windows.

**Solución adoptada:** usar la clase `Com\Tecnick\Pdf\Font\Import` de la propia librería para convertir una fuente TrueType que ya existe en el sistema (`C:\Windows\Fonts\arial.ttf`) al formato que tc-lib-pdf entiende. Esto lo automatiza un comando artisan (ver abajo).

### Cómo resuelve la librería el nombre de fuente → archivo

Cuando se llama a `$pdf->font->insert($pdf->pon, 'arial', ...)`, la librería busca un archivo `arial.json` (nombre en minúsculas) dentro de:

1. La carpeta apuntada por la constante **`K_PATH_FONTS`** y sus subdirectorios inmediatos.
2. La carpeta `fonts/` interna del paquete.

Por eso definimos `K_PATH_FONTS` apuntando a `storage/fonts/`, donde el comando deja los archivos importados.

---

## Componentes de la integración

### 1. Constante K_PATH_FONTS

Se define en el arranque de la aplicación, en `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    // tc-lib-pdf busca las fuentes (.json/.z) en la carpeta apuntada por
    // esta constante. Importarlas con: php artisan pdf:import-font
    if (! defined('K_PATH_FONTS')) {
        define('K_PATH_FONTS', storage_path('fonts'));
    }
}
```

Ruta resultante: `storage/fonts/`.

### 2. Comando para importar fuentes

`app/Console/Commands/ImportPdfFont.php` — convierte una `.ttf` al formato de tc-lib-pdf y la deja en `storage/fonts/`.

```php
$import = new \Com\Tecnick\Pdf\Font\Import($ttf, storage_path('fonts') . DIRECTORY_SEPARATOR);
$name = $import->getFontName();   // p. ej. "arial"
```

Uso:

```bash
# Importa Arial por defecto (C:\Windows\Fonts\arial.ttf)
php artisan pdf:import-font

# O una fuente específica
php artisan pdf:import-font "C:\Windows\Fonts\times.ttf"
```

> El nombre final de la fuente lo genera `Import` a partir del nombre del archivo (minúsculas, sin extensión, mapeando `bold`→`b`, `italic`→`i`). `arial.ttf` ⇒ clave `arial`.
>
> Si la fuente ya fue importada, `Import` lanza una excepción; el comando la captura y avisa sin frenar.

### 3. Controlador de reportes

`app/Http/Controllers/ReporteController.php` — genera el PDF y lo devuelve como respuesta HTTP.

```php
use Com\Tecnick\Pdf\Tcpdf;

public function test(): \Illuminate\Http\Response
{
    // Guarda contra el error más común: fuente no importada
    if (! is_readable(storage_path('fonts/arial.json'))) {
        abort(500, "Falta la fuente. Ejecutá: php artisan pdf:import-font");
    }

    $pdf = new Tcpdf();
    $pdf->setTitle('Reporte de prueba');
    $pdf->setPDFFilename('reporte_prueba.pdf');

    // Cargar la fuente y agregarla a la página
    $font = $pdf->font->insert($pdf->pon, 'arial', '', 12);
    $pdf->addPage();
    $pdf->page->addContent($font['out']);

    // Contenido como HTML
    $pdf->addHTMLCell(html: '<h1>Reporte</h1>...', posx: 15, posy: 15, width: 180);

    $raw = $pdf->getOutPDFString();

    return response($raw, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="reporte_prueba.pdf"',
    ]);
}
```

### 4. Ruta

En `routes/web.php`:

```php
use App\Http\Controllers\ReporteController;

Route::get('/reporte/test', [ReporteController::class, 'test'])->name('reporte.test');
```

---

## Cómo probar

```powershell
# 1. Importar la fuente (una sola vez, ya hecho en este proyecto)
c:\eid\Xampp_82\php\php.exe artisan pdf:import-font

# 2. Levantar el servidor con PHP 8.2
c:\eid\Xampp_82\php\php.exe artisan serve
```

Abrir en el navegador: **http://localhost:8000/reporte/test**

Deberías ver el PDF embebido. Verificado: genera un PDF válido (`%PDF-1.7`, ~598 KB con la fuente Arial embebida).

---

## Anatomía de la generación de un PDF

El flujo mínimo con tc-lib-pdf siempre es:

```php
// 1. Instanciar el documento
$pdf = new \Com\Tecnick\Pdf\Tcpdf();

// 2. Metadatos (opcional)
$pdf->setTitle('...');
$pdf->setAuthor('...');
$pdf->setPDFFilename('archivo.pdf');

// 3. Insertar fuente -> devuelve ['out' => '...'] con el comando PDF de la fuente
$font = $pdf->font->insert($pdf->pon, 'arial', '', 12);
//                          ^pon      ^nombre  ^estilo ^tamaño

// 4. Agregar una página
$pdf->addPage();

// 5. IMPORTANTE: inyectar la fuente en el contenido de la página
$pdf->page->addContent($font['out']);

// 6. Escribir contenido (HTML es lo más cómodo para reportes)
$pdf->addHTMLCell(html: $html, posx: 15, posy: 15, width: 180);

// 7. Obtener los bytes del PDF
$raw = $pdf->getOutPDFString();
```

Notas:

- `$pdf->pon` es el contador de objetos PDF; se pasa a `font->insert`.
- El segundo argumento de `insert` es el **estilo**: `''` (normal), `'B'` (bold), `'I'` (italic), `'BI'`.
- `addHTMLCell` acepta un subconjunto de HTML/CSS (encabezados, párrafos, tablas, listas, negrita, etc.).
- Para escribir texto sin HTML existen métodos como `addTextCell` / los de `Text.php`; ver ejemplos `E039_text_methods.php`.

---

## Receta: mostrar vs. descargar

La diferencia está solo en el header `Content-Disposition`:

```php
// Ver embebido en el navegador
'Content-Disposition' => 'inline; filename="reporte.pdf"',

// Forzar descarga
'Content-Disposition' => 'attachment; filename="reporte.pdf"',
```

> Evitá usar `$pdf->renderPDF()` dentro de Laravel: ese método emite headers y hace `echo` directo (estilo script suelto), lo que choca con el ciclo de respuesta de Laravel. Usá `getOutPDFString()` + `response()`.

---

## Usar otras fuentes

1. Importar la fuente:

   ```bash
   php artisan pdf:import-font "C:\Windows\Fonts\times.ttf"
   ```

2. Usar su clave (nombre de archivo en minúsculas) en el controlador:

   ```php
   $font = $pdf->font->insert($pdf->pon, 'times', '', 12);
   ```

Para negrita/itálica reales conviene importar también esos archivos TTF (`arialbd.ttf`, `ariali.ttf`, etc.); de lo contrario la librería simula el estilo ("fakestyle").

---

## Ejemplos oficiales útiles

El paquete trae **76 ejemplos** en `vendor/tecnickcom/tc-lib-pdf/examples/`. Los más relevantes para reportes:

| Ejemplo | Tema |
|---------|------|
| `E006_minimal.php` | Hola mundo (base de esta integración) |
| `E001_invoice.php` | Factura completa |
| `E043_html_tables.php` | Tablas en HTML |
| `E005_header_footer.php` | Encabezado y pie de página |
| `E020_barcodes.php` | Códigos de barras |
| `E050_shipping_label_barcodes.php` | Etiquetas con códigos de barras |
| `E037_image_methods.php` | Inserción de imágenes |
| `E044_toc_index.php` | Índice / tabla de contenidos |
| `E045_encryption_and_permissions.php` | PDF protegido con permisos |

---

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `Composer detected issues... You are running 5.6.35` | Se usó el `php` del PATH (PHP 5.6) | Usar `c:\eid\Xampp_82\php\php.exe` |
| Excepción al insertar la fuente / fuente no encontrada | Falta `arial.json` en `storage/fonts/` | `php artisan pdf:import-font` |
| `K_PATH_FONTS` no definida | No se cargó el `AppServiceProvider` (script suelto) | Definir la constante manualmente antes de usar la librería |
| El PDF se descarga en vez de verse (o viceversa) | Header `Content-Disposition` | Cambiar `inline` ⇄ `attachment` |
| Negrita/itálica se ven "simuladas" | Solo se importó la variante normal | Importar también `arialbd.ttf` / `ariali.ttf` |

---

_Documentación de la integración de tc-lib-pdf — proyecto Lrvl_curso_reporte._
