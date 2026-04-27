<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MenuImageUploadService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function storeOptimized(UploadedFile $uploadedFile): string
    {
        $mimeType = (string) $uploadedFile->getMimeType();
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new \InvalidArgumentException('Format image non supporte.');
        }

        $relativeDir = '/uploads/menus/' . date('Y/m');
        $targetDir = $this->projectDir . '/public' . $relativeDir;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Impossible de creer le dossier de stockage des images.');
        }

        $baseName = bin2hex(random_bytes(12));

        if ($this->canUseGd()) {
            $optimizedName = $baseName . '.webp';
            $optimizedPath = $targetDir . '/' . $optimizedName;

            if ($this->optimizeWithGd($uploadedFile, $optimizedPath)) {
                return $relativeDir . '/' . $optimizedName;
            }
        }

        $extension = $uploadedFile->guessExtension() ?: 'bin';
        $fallbackName = $baseName . '.' . $extension;
        $uploadedFile->move($targetDir, $fallbackName);

        return $relativeDir . '/' . $fallbackName;
    }

    private function canUseGd(): bool
    {
        return function_exists('imagecreatefromstring')
            && function_exists('imagesx')
            && function_exists('imagesy')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagewebp')
            && function_exists('imagedestroy');
    }

    private function optimizeWithGd(UploadedFile $uploadedFile, string $outputPath): bool
    {
        $binary = @file_get_contents($uploadedFile->getPathname());
        if ($binary === false) {
            return false;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return false;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);
            return false;
        }

        $maxWidth = 1600;
        $maxHeight = 1600;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);

        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            return false;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        $ok = imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        if (!$ok) {
            imagedestroy($target);
            imagedestroy($source);
            return false;
        }

        $saved = imagewebp($target, $outputPath, 82);

        imagedestroy($target);
        imagedestroy($source);

        return $saved;
    }
}
