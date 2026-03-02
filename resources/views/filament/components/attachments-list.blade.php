<div x-data="{ pdfUrl: null, pdfName: '' }">

    {{-- =========================================================== --}}
    {{-- VISOR DE PDF (visible cuando el usuario clica "Ver") --}}
    {{-- =========================================================== --}}
    <div x-show="pdfUrl !== null" x-transition.opacity style="display:none;">

        {{-- Barra de navegación interna --}}
        <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
            <button
                type="button"
                @click="pdfUrl = null; pdfName = ''"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition font-medium"
            >
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                Volver a la lista
            </button>

            <div class="flex items-center gap-2 min-w-0">
                <x-heroicon-o-document class="w-4 h-4 text-red-500 shrink-0" />
                <span
                    class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-xs"
                    x-text="pdfName"
                ></span>
            </div>

            <a
                :href="pdfUrl"
                download
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition shrink-0"
            >
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Descargar
            </a>
        </div>

        {{-- Iframe con el PDF --}}
        <iframe
            :src="pdfUrl"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white"
            style="height: 72vh; min-height: 400px;"
        ></iframe>
    </div>

    {{-- =========================================================== --}}
    {{-- LISTA DE ARCHIVOS (visible por defecto) --}}
    {{-- =========================================================== --}}
    <div x-show="pdfUrl === null" x-transition.opacity class="space-y-2">

        {{-- Archivos regulares --}}
        @if(count($attachments ?? []))
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                Documentos
            </p>
            @foreach($attachments as $attachment)
                @php
                    $filename = is_string($attachment) ? basename($attachment) : ($attachment['name'] ?? 'archivo.pdf');
                    $url = asset('storage/' . $attachment);
                @endphp
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-heroicon-o-document class="w-5 h-5 text-red-500 shrink-0" />
                        <span class="text-sm font-medium truncate">{{ $filename }}</span>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button
                            type="button"
                            @click="pdfUrl = '{{ $url }}'; pdfName = '{{ $filename }}'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition"
                        >
                            <x-heroicon-o-eye class="w-4 h-4" />
                            Ver
                        </button>
                        <a
                            href="{{ $url }}"
                            download
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition"
                        >
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                            Descargar
                        </a>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Archivos confidenciales (solo panel_user) --}}
        @if(count($confidential ?? []))
            <p class="text-xs font-semibold text-orange-500 uppercase tracking-wide mt-4 mb-2">
                <x-heroicon-o-lock-closed class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" />
                Confidenciales
            </p>
            @foreach($confidential as $attachment)
                @php
                    $filename = is_string($attachment) ? basename($attachment) : ($attachment['name'] ?? 'archivo.pdf');
                    $url = asset('storage/' . $attachment);
                @endphp
                <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-orange-500 shrink-0" />
                        <span class="text-sm font-medium truncate">{{ $filename }}</span>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button
                            type="button"
                            @click="pdfUrl = '{{ $url }}'; pdfName = '{{ $filename }}'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition"
                        >
                            <x-heroicon-o-eye class="w-4 h-4" />
                            Ver
                        </button>
                        <a
                            href="{{ $url }}"
                            download
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition"
                        >
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                            Descargar
                        </a>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Aviso para admins si hay archivos confidenciales (acceso restringido) --}}
        @if(($isAdmin ?? false) && ($confidentialCount ?? 0) > 0)
            <div class="mt-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg flex items-start gap-2 text-sm text-orange-700 dark:text-orange-300">
                <x-heroicon-o-lock-closed class="w-4 h-4 shrink-0 mt-0.5" />
                <span>{{ $confidentialCount }} archivo(s) confidencial(es) — acceso restringido</span>
            </div>
        @endif

    </div>
</div>
