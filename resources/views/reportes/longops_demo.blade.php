@extends('layouts.longproc')

@section('title', 'Demo · longOps')
@section('header', 'Demo — Diálogo de progreso (longOps)')

@section('content')
    <p class="mb-4 text-sm text-gray-600">
        Abre un diálogo modal de jQuery UI con una barra de progreso que va
        consultando al backend (<code>longops_action=start → resume → finished</code>).
        Probá también el botón <strong>Cancelar</strong>.
    </p>

    <button id="btn-longop"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
        Iniciar operación larga
    </button>

    <p id="longop-result" class="mt-4 text-sm"></p>
@endsection

@push('scripts')
<script>
    // app.js (módulo diferido) ya dejó window.jQuery, jQuery UI y window.longOps.
    document.addEventListener('DOMContentLoaded', function () {
        const $ = window.jQuery;
        const $result = $('#longop-result');

        $('#btn-longop').on('click', function () {
            $result.text('');
            longOps.start(
                // params: viajan como query string al backend (en 'start')
                { total: 10 },
                {
                    title: 'Procesando…',
                    comment: 'Ejecutando una operación larga, por favor esperá.',
                    backend: '{{ route('reporte.longops.backend') }}',
                    btnStop: 'Cancelar',
                    autoClose: 2, // segundos para autocerrar al finalizar
                    onSuccess: function () { $result.html('✅ <strong>Operación finalizada.</strong>'); },
                    onCancel:  function () { $result.html('🟡 Operación cancelada.'); },
                    onError:   function () { $result.html('❌ Error en la operación.'); }
                }
            );
        });
    });
</script>
@endpush
