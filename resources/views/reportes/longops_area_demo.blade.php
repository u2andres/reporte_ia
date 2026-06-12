@extends('layouts.longproc')

@section('title', 'Demo · Reporte por área')
@section('header', 'Demo — Reporte por área (longOps + merge)')

@section('content')
    <form id="lpa-form" class="bg-white border border-gray-200 rounded-lg p-4 mb-4 space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="lpa-area" class="block text-sm font-medium mb-1">Área</label>
                <select id="lpa-area" class="border border-gray-300 rounded px-3 py-2">
                    @foreach ($areas as $a)
                        <option value="{{ $a->c650_id }}" @selected($a->c650_id === 'F')>
                            {{ $a->c650_id }} — {{ $a->c650_descripcion }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="lpa-anio" class="block text-sm font-medium mb-1">Año</label>
                <input id="lpa-anio" type="number" value="2020"
                       class="border border-gray-300 rounded px-3 py-2 w-28">
            </div>
            <div>
                <label for="lpa-limit" class="block text-sm font-medium mb-1">
                    Límite <span class="text-gray-400">(0 = todos)</span>
                </label>
                <input id="lpa-limit" type="number" value="5" min="0"
                       class="border border-gray-300 rounded px-3 py-2 w-24">
            </div>
            <button id="lpa-run" type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                Generar reporte del área
            </button>
        </div>
        <p class="text-xs text-gray-500">
            Genera el <code>rpt_min02</code> de cada establecimiento del área y los combina en un PDF.
            El diálogo muestra el progreso; al terminar queda el link de descarga.
            Las áreas grandes (p. ej. Inicial) tienen cientos de establecimientos: usá el límite para probar.
        </p>
    </form>
@endsection

@push('scripts')
<script>
    // app.js (módulo diferido) ya dejó window.jQuery, jQuery UI y window.longOps.
    document.addEventListener('DOMContentLoaded', function () {
        const $ = window.jQuery;

        $('#lpa-form').on('submit', function (e) {
            e.preventDefault();
            const area  = $('#lpa-area').val();
            const anio  = $('#lpa-anio').val();
            const limit = $('#lpa-limit').val();

            longOps.start(
                // params: van como query string al backend (en 'start')
                { area: area, anio: anio, limit: limit },
                {
                    title: 'Generando reporte del área ' + area + '…',
                    comment: 'Procesando establecimientos y combinando los PDFs.',
                    backend: '{{ route('reporte.longops.backendArea') }}',
                    btnStop: 'Cancelar',
                    btnClose: 'Cerrar',
                    autoClose: 0, // queda abierto para ver/clickear el link de descarga
                }
            );
        });
    });
</script>
@endpush
