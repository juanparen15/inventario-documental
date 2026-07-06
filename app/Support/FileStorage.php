<?php

namespace App\Support;

use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileStorage
{
    /**
     * Genera un nombre de almacenamiento seguro y único para cualquier archivo.
     * Neutraliza nombres con tildes, comas, puntos, espacios o excesivamente largos,
     * de modo que el archivo siempre pueda guardarse sin importar su nombre original.
     */
    public static function safeName(TemporaryUploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');

        // Nombre sin extensión, transliterado a ASCII (á→a, ñ→n, etc.)
        $name = Str::ascii(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        // Reemplaza cualquier caracter no alfanumérico (comas, puntos, espacios…) por guion
        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-');

        if ($name === '') {
            $name = 'documento';
        }

        // Acota la longitud para no superar el límite del sistema de archivos
        $name = Str::limit($name, 80, '');

        return $name . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;
    }
}
