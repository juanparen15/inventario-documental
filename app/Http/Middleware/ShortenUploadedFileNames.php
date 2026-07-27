<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acorta el nombre de los archivos subidos cuando es demasiado largo.
 *
 * Livewire genera el nombre del archivo temporal embebiendo el nombre original
 * en base64 (generateHashNameWithOriginalNameEmbedded). Un nombre original largo
 * produce un nombre de temporal mayor a 255 bytes, que ext4 rechaza al escribir,
 * dejando el temporal inexistente. Luego la validación 'max' llama getSize() sobre
 * ese temporal y lanza League\Flysystem\UnableToRetrieveMetadata.
 *
 * Este middleware recorta el nombre original a un tamaño seguro ANTES de que
 * Livewire lo procese, conservando la extensión. El archivo subido real no se
 * toca (misma ruta temporal), solo se reemplaza su nombre visible.
 */
class ShortenUploadedFileNames
{
    /** Máximo de bytes del nombre original a partir del cual se recorta. */
    private const THRESHOLD = 120;

    /** Tamaño objetivo (bytes) del nombre ya recortado. */
    private const TARGET = 100;

    public function handle(Request $request, Closure $next): Response
    {
        $files = $request->files->all();

        if (! empty($files)) {
            foreach ($files as $key => $value) {
                $request->files->set($key, $this->shortenValue($value));
            }
        }

        return $next($request);
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private function shortenValue($value)
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->shortenValue($item), $value);
        }

        if ($value instanceof UploadedFile) {
            return $this->shortenFile($value);
        }

        return $value;
    }

    private function shortenFile(UploadedFile $file): UploadedFile
    {
        // No tocar archivos con error de subida (ya fallaron por otra razón).
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $file;
        }

        $original = $file->getClientOriginalName();

        if ($original === null || strlen($original) <= self::THRESHOLD) {
            return $file;
        }

        $extension = $file->getClientOriginalExtension();
        $suffix    = $extension !== '' ? '.' . $extension : '';
        $maxBase   = max(1, self::TARGET - strlen($suffix));

        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = mb_strcut($base, 0, $maxBase, 'UTF-8'); // recorte por bytes sin partir caracteres

        if ($base === '') {
            $base = 'documento';
        }

        return new UploadedFile(
            $file->getPathname(),
            $base . $suffix,
            $file->getClientMimeType(),
            $file->getError(),
            false // archivo subido real: is_uploaded_file() sigue siendo válido
        );
    }
}
