<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Redimensionne et compresse une image avant stockage, puis la sauvegarde sur le disque donné.
     * Retourne le chemin relatif stocké.
     */
    public function storeCompressed(UploadedFile $file, string $directory, string $disk = 'public', int $maxWidth = 1600, int $quality = 75): string
    {
        $image = $this->manager->read($file->getRealPath());

        if ($image->width() > $maxWidth) {
            $image->scale(width: $maxWidth);
        }

        $filename = $directory.'/'.Str::uuid()->toString().'.jpg';

        Storage::disk($disk)->put($filename, (string) $image->toJpeg($quality));

        return $filename;
    }

    public function delete(string $path, string $disk = 'public'): void
    {
        Storage::disk($disk)->delete($path);
    }
}
