/**
 * chart-config.js
 *
 * Filament serializa getOptions() a JSON con Js::from(), lo que convierte
 * las funciones JS definidas como strings PHP en literales de texto.
 * Chart.js los recibe como strings y no puede ejecutarlos, por lo que
 * tooltips y callbacks de ejes quedan inactivos.
 *
 * Este plugin global de Chart.js convierte esos strings de función en
 * funciones reales justo antes de que se inicialice cada gráfica.
 */
(function () {
    'use strict';

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    /** Convierte un string "function(...){...}" en una función real. */
    function toFn(str) {
        try {
            // new Function es más seguro que eval: crea en scope global
            // eslint-disable-next-line no-new-func
            return new Function('return (' + str + ')')();
        } catch {
            return null;
        }
    }

    function looksLikeFn(val) {
        return typeof val === 'string' && (
            val.trimStart().startsWith('function') ||
            val.trimStart().startsWith('(')
        );
    }

    /** Fallbacks para colores de tooltip según modo claro/oscuro. */
    const tooltipColorFallbacks = {
        backgroundColor: () => isDark() ? 'rgba(15,23,42,0.97)' : 'rgba(255,255,255,0.97)',
        titleColor:      () => isDark() ? '#f1f5f9'             : '#0f172a',
        bodyColor:       () => isDark() ? '#94a3b8'             : '#475569',
        borderColor:     () => isDark() ? 'rgba(51,65,85,0.8)'  : 'rgba(226,232,240,1)',
    };

    function patchTooltipOpts(opts) {
        if (!opts) return;

        // Reemplazar strings de función por funciones reales; si falla,
        // aplicar el fallback de color dinámico según tema.
        ['backgroundColor', 'titleColor', 'bodyColor', 'borderColor'].forEach(key => {
            if (looksLikeFn(opts[key])) {
                opts[key] = toFn(opts[key]) ?? tooltipColorFallbacks[key];
            }
        });

        // Callbacks del tooltip
        if (opts.callbacks && typeof opts.callbacks === 'object') {
            Object.keys(opts.callbacks).forEach(cb => {
                if (looksLikeFn(opts.callbacks[cb])) {
                    const fn = toFn(opts.callbacks[cb]);
                    if (fn) {
                        opts.callbacks[cb] = fn;
                    } else {
                        delete opts.callbacks[cb];
                    }
                }
            });
        }
    }

    function patchScales(scales) {
        if (!scales || typeof scales !== 'object') return;
        Object.values(scales).forEach(scale => {
            if (scale?.ticks?.callback && looksLikeFn(scale.ticks.callback)) {
                const fn = toFn(scale.ticks.callback);
                if (fn) {
                    scale.ticks.callback = fn;
                } else {
                    delete scale.ticks.callback;
                }
            }
        });
    }

    const filamentFnStringPlugin = {
        id: 'filamentFnStringFix',
        beforeInit(chart) {
            patchTooltipOpts(chart.options?.plugins?.tooltip);
            patchScales(chart.options?.scales);
        },
    };

    function register() {
        if (typeof Chart !== 'undefined' && Chart.register) {
            Chart.register(filamentFnStringPlugin);
        } else {
            setTimeout(register, 80);
        }
    }

    // Registrar inmediatamente o esperar a que Chart.js cargue
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', register);
    } else {
        register();
    }
})();
