<?php

namespace App\Providers;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;

class CloudiNary
{
    public static function configure(): void
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    /** Sobe a imagem e retorna a secure_url (ou null) */
    public static function upload(UploadedFile $file, string $publicId, string $folder = 'tcc/projetos'): ?string
    {
        self::configure();

        $upload = (new UploadApi())->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'public_id' => $publicId,
                'overwrite' => true,
                'resource_type' => 'image',
                'transformation' => [['quality' => 'auto', 'fetch_format' => 'auto']],
            ]
        );

        return $upload['secure_url'] ?? null;
    }

    /** Apaga no Cloudinary a partir da URL completa (ignora se não conseguir extrair) */
    public static function destroyByUrl(?string $url): void
    {
        if (!$url)
            return;

        $publicId = self::getPublicIdFromUrl($url);
        if ($publicId) {
            self::configure();
            (new UploadApi())->destroy($publicId, ['invalidate' => true, 'resource_type' => 'image']);
        }
    }

    /** Extrai public_id de uma secure_url do Cloudinary */
    public static function getPublicIdFromUrl(?string $url): ?string
    {
        if (!$url || !str_contains($url, '/upload/'))
            return null;

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $parts = explode('/upload/', $path);
        if (count($parts) < 2)
            return null;

        $afterUpload = preg_replace('#^v\d+/#', '', ltrim($parts[1], '/')); // remove vNNN/
        $noExt = preg_replace('/\.[a-zA-Z0-9]+$/', '', $afterUpload);       // remove extensão

        return $noExt ?: null;
    }
}
