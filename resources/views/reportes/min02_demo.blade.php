@extends('layouts.longproc')

@section('title', 'Demo · Planta Completa Valorizada')
@section('header', 'Demo — Generación de reportes (rpt_min02)')

@section('content')
    <form id="lp-form" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 space-y-3">
        <div>
            <label for="lp-estabs" class="block text-sm font-medium mb-1">
                Establecimientos <span class="text-gray-400">(códigos separados por coma)</span>
            </label>
            <input id="lp-estabs" type="text" value="{{ implode(', ', $ejemplos) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2">
        </div>

        <div class="flex items-end gap-3">
            <div>
                <label for="lp-anio" class="block text-sm font-medium mb-1">Año</label>
                <input id="lp-anio" type="number" value="{{ $anio }}"
                       class="border border-gray-300 rounded px-3 py-2 w-32">
            </div>
            <button id="lp-run" type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                Generar
            </button>
        </div>
    </form>

    <ul id="lp-results" class="space-y-1 text-sm"></ul>
@endsection

@push('scripts')
<script>
    // app.js se carga como módulo (diferido): en DOMContentLoaded ya está
    // window.jQuery disponible. NO usar $(function(){}) en el inline porque
    // este <script> corre durante el parseo, antes de que cargue jQuery.
    document.addEventListener('DOMContentLoaded', function () {
        const $ = window.jQuery;
        const $form = $('#lp-form');
        const $results = $('#lp-results');

        // Construye la URL del reporte para un establecimiento/año.
        // (usa el helper de ruta para no hardcodear el path)
        const urlReporte = (estab, anio) =>
            "{{ url('reporte/min_02') }}/" + encodeURIComponent(estab) + "/" + encodeURIComponent(anio);

        $form.on('submit', async function (e) {
            e.preventDefault();

            const estabs = $('#lp-estabs').val()
                .split(',').map(s => s.trim()).filter(Boolean);
            const anio = $('#lp-anio').val();

            $results.empty();
            if (!estabs.length) {
                LongProc.start().fail('Ingresá al menos un establecimiento.');
                return;
            }

            $('#lp-run').prop('disabled', true);
            LongProc.start('Generando ' + estabs.length + ' reporte(s)…');

            let ok = 0;
            for (let i = 0; i < estabs.length; i++) {
                const estab = estabs[i];
                LongProc.set(
                    Math.round((i / estabs.length) * 100),
                    `Establecimiento ${estab} (${i + 1}/${estabs.length})`
                );
                try {
                    const resp = await fetch(urlReporte(estab, anio));
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const blob = await resp.blob();
                    const url = URL.createObjectURL(blob);
                    $results.append(
                        `<li>✅ Estab <strong>${estab}</strong>: ` +
                        `<a class="text-blue-600 underline" href="${url}" target="_blank">ver PDF</a> ` +
                        `<span class="text-gray-500">(${(blob.size / 1024).toFixed(0)} KB)</span></li>`
                    );
                    ok++;
                } catch (err) {
                    $results.append(
                        `<li class="text-red-600">❌ Estab <strong>${estab}</strong>: ${err.message}</li>`
                    );
                }
            }

            LongProc.set(100).done(`Listo: ${ok}/${estabs.length} generado(s)`);
            $('#lp-run').prop('disabled', false);
        });
    });
</script>
@endpush
