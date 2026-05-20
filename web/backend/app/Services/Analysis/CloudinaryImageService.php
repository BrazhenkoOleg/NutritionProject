<?php

namespace App\Services\Analysis;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryImageService
{
    public function upload(UploadedFile $image): array
    {
        $cloudinaryUrl = config('services.cloudinary.url');

        if (!$cloudinaryUrl) {
            throw new AnalysisServiceException(
                errorKey: 'Cloudinary is not configured',
                userMessage: 'Хранилище изображений не настроено. Запись не создана.',
                statusCode: 500,
            );
        }

        try {
            $cloudinary = new Cloudinary($cloudinaryUrl);

            $result = $cloudinary->uploadApi()->upload($image->getRealPath(), [
                'folder' => 'nutrivision/analyses',
                'resource_type' => 'image',
            ]);

            $secureUrl = $result['secure_url'] ?? null;
            $publicId = $result['public_id'] ?? null;

            if (!$secureUrl || !$publicId) {
                throw new AnalysisServiceException(
                    errorKey: 'Cloudinary upload error',
                    userMessage: 'Не удалось сохранить изображение. Запись не создана.',
                    statusCode: 500,
                );
            }

            return [
                'url' => $secureUrl,
                'public_id' => $publicId,
            ];
        } catch (AnalysisServiceException $error) {
            throw $error;
        } catch (\Throwable $error) {
            report($error);

            throw new AnalysisServiceException(
                errorKey: 'Cloudinary upload error',
                userMessage: 'Не удалось сохранить изображение. Запись не создана.',
                statusCode: 500,
            );
        }
    }

    public function delete(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        $cloudinaryUrl = config('services.cloudinary.url');

        if (!$cloudinaryUrl) {
            return;
        }

        try {
            $cloudinary = new Cloudinary($cloudinaryUrl);

            $cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
            ]);
        } catch (\Throwable $error) {
            report($error);
        }
    }
}