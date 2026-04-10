<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;

class MinioService
{
    protected string $disk = 'minio';

    public function upload(string $path, string $contents, string $contentType = 'application/xml'): string
    {
        Storage::disk($this->disk)->put($path, $contents, [
            'ContentType' => $contentType,
        ]);

        return $path;
    }

    public function download(string $path): ?string
    {
        if (! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->get($path);
    }

    public function getUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function getTemporaryUrl(string $path, int $minutes = 60): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }
}
