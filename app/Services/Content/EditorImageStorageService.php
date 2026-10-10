<?php

namespace App\Services\Content;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorImageStorageService
{
    private const DISK = 'public';
    private const TEMP_PREFIX = 'upload_files/questions/tmp/';
    private const FINAL_PREFIX = 'upload_files/questions/';

    public function storeTemporarily(UploadedFile $file, int|string $userId): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $path = self::TEMP_PREFIX.$userId.'/'.Str::uuid().'.'.$extension;
        Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

        return '/storage/'.$path;
    }

    public function persistHtml(string $html, int|string $userId): string
    {
        return preg_replace_callback(
            '#/storage/('.preg_quote(self::TEMP_PREFIX, '#').preg_quote((string) $userId, '#').'/[^"\' ]+)#',
            function (array $matches): string {
                $temporaryPath = $matches[1];
                $extension = pathinfo($temporaryPath, PATHINFO_EXTENSION);
                $finalPath = self::FINAL_PREFIX.Str::uuid().'.'.$extension;
                $disk = Storage::disk(self::DISK);

                if ($disk->exists($temporaryPath)) {
                    $disk->move($temporaryPath, $finalPath);
                    return '/storage/'.$finalPath;
                }

                return $matches[0];
            },
            $html,
        ) ?? $html;
    }
}
