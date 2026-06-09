<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    {{-- Carga Tailwind + jQuery + jQuery UI (CSS y JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">

    {{-- Encabezado --}}
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">
                @yield('header', config('app.name', 'Laravel'))
            </h1>
            <div class="flex items-center gap-2">
                @yield('toolbar')
            </div>
        </div>
    </header>

    {{--
        Panel de progreso para "procesos largos".
        Controlable desde JS con la API global window.LongProc:
            LongProc.start('Generando reportes…');
            LongProc.set(40, 'Establecimiento 12 de 30');
            LongProc.done('Listo');
            LongProc.fail('Error al generar');
            LongProc.hide();
    --}}
    <div id="longproc-panel" class="hidden max-w-5xl mx-auto px-4 mt-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2 text-sm">
                <span id="longproc-label" class="font-medium">Procesando…</span>
                <span id="longproc-percent" class="text-gray-500">0%</span>
            </div>
            <div id="longproc-bar"></div>
            <p id="longproc-error" class="hidden mt-2 text-sm text-red-600"></p>
        </div>
    </div>

    {{-- Contenido de la página --}}
    <main class="max-w-5xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    {{-- API de la barra de progreso (jQuery UI). Se inicializa cuando el
         módulo de Vite ya dejó window.jQuery / jQuery UI disponibles. --}}
    <script>
        (function () {
            function init() {
                var $ = window.jQuery;
                if (!$ || !$.fn || !$.fn.progressbar) {
                    console.warn('LongProc: jQuery UI no está disponible.');
                    return;
                }

                var $panel = $('#longproc-panel'),
                    $bar   = $('#longproc-bar'),
                    $label = $('#longproc-label'),
                    $pct   = $('#longproc-percent'),
                    $err   = $('#longproc-error');

                $bar.progressbar({ value: 0 });

                var clamp = function (v) { return Math.max(0, Math.min(100, Math.round(v))); };

                window.LongProc = {
                    // Muestra el panel y reinicia el progreso.
                    start: function (label) {
                        $err.addClass('hidden').text('');
                        $label.text(label || 'Procesando…');
                        $pct.text('0%');
                        $bar.progressbar('value', 0);
                        $panel.removeClass('hidden');
                        return this;
                    },
                    // Actualiza el porcentaje (0..100) y opcionalmente la etiqueta.
                    set: function (value, label) {
                        var v = clamp(value);
                        $bar.progressbar('value', v);
                        $pct.text(v + '%');
                        if (label) { $label.text(label); }
                        return this;
                    },
                    // Modo indeterminado (sin porcentaje conocido).
                    indeterminate: function (label) {
                        $bar.progressbar('value', false);
                        $pct.text('');
                        if (label) { $label.text(label); }
                        $panel.removeClass('hidden');
                        return this;
                    },
                    // Marca como completado (100%).
                    done: function (label) {
                        $bar.progressbar('value', 100);
                        $pct.text('100%');
                        $label.text(label || 'Completado');
                        return this;
                    },
                    // Muestra un mensaje de error.
                    fail: function (msg) {
                        $err.text(msg || 'Ocurrió un error.').removeClass('hidden');
                        return this;
                    },
                    // Oculta el panel.
                    hide: function () {
                        $panel.addClass('hidden');
                        return this;
                    }
                };

                // Evento por si alguna vista quiere saber cuándo está lista la API.
                document.dispatchEvent(new CustomEvent('longproc:ready'));
            }

            // Los assets de Vite (type="module") corren antes de DOMContentLoaded,
            // así que para entonces window.jQuery / jQuery UI ya están cargados.
            document.addEventListener('DOMContentLoaded', init);
        })();
    </script>

    @stack('scripts')
</body>
</html>
