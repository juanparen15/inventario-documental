<div class="space-y-2">
    @foreach($attachments as $attachment)
        @php
            $filename = is_string($attachment) ? basename($attachment) : ($attachment['name'] ?? 'archivo.pdf');
            $url = is_string($attachment) ? asset('storage/' . $attachment) : asset('storage/' . $attachment);
        @endphp
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center gap-2">
                <x-heroicon-o-document class="w-5 h-5 text-red-500" />
                <span class="text-sm font-medium truncate max-w-xs">{{ $filename }}</span>
            </div>
            <div class="flex gap-2">
                <a href="{{ $url }}" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition">
                    <x-heroicon-o-eye class="w-4 h-4" />
                    Ver
                </a>
                <a href="{{ $url }}" download
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    Descargar
                </a>
            </div>
        </div>
    @endforeach
</div>
