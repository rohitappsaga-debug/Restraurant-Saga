<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessImageOptimization implements ShouldQueue
{
    use Queueable;

    public $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        $fullPath = Storage::disk('public')->path($this->path);

        if (!file_exists($fullPath)) {
            Log::warning("Image not found for optimization: {$fullPath}");
            return;
        }

        try {
            $info = getimagesize($fullPath);
            if (!$info) return;

            $mime = $info['mime'];

            switch ($mime) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($fullPath);
                    break;
                case 'image/webp':
                    // Already webp, just skip or compress
                    $image = imagecreatefromwebp($fullPath);
                    break;
                default:
                    return;
            }

            if (!$image) return;

            // Optional: Resize if width > 1000
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width > 1000) {
                $newWidth = 1000;
                $newHeight = (int) ($height * ($newWidth / $width));
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }

            // Convert to webp and save
            $newPath = preg_replace('/\.[^.]+$/', '.webp', $fullPath);
            imagewebp($image, $newPath, 80);
            imagedestroy($image);

            // Update database if necessary, but typically we could just keep the same extension and serve webp if we rewrite, 
            // or we update the path in the DB. We should update the DB.
            $relativePath = preg_replace('/\.[^.]+$/', '.webp', $this->path);

            if ($newPath !== $fullPath) {
                unlink($fullPath); // remove old file
                \App\Models\MenuItem::where('image', 'like', '%' . basename($this->path))->update([
                    'image' => $relativePath
                ]);
            }
            
            Log::info("Image optimized: {$relativePath}");

        } catch (\Exception $e) {
            Log::error("Failed to optimize image {$this->path}: " . $e->getMessage());
        }
    }
}
