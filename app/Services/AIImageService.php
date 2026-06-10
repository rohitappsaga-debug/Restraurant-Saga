<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class AIImageService
{
    /**
     * Generate an AI image for a menu item using Hugging Face Router API with Pollinations fallback.
     *
     * @param MenuItem $item
     * @return string|null Path to the saved image or null on failure
     */
    public function generateMenuItemImage(MenuItem $item)
    {
        try {
            return $this->tryHuggingFace($item);
        } catch (Exception $e) {
            \Log::warning("Hugging Face Failed, falling back to Pollinations: " . $e->getMessage());
            return $this->tryPollinations($item);
        }
    }

    private function tryHuggingFace(MenuItem $item)
    {
        $apiKey = trim(config('services.huggingface.api_key'));
        $model = trim(config('services.huggingface.model'));
        $endpoint = "https://router.huggingface.co/hf-inference/models/{$model}";

        if (!$apiKey) {
            throw new Exception('Hugging Face API key is not configured.');
        }

        $categoryName = $item->categoryInfo?->name ?? 'Dish';
        $prompt = "High-end gourmet food photography of {$item->name}. {$item->description}. Styled as a premium {$categoryName}, exquisite restaurant plating, cinematic lighting, macro details, vibrant colors, 4k resolution, professional food styling.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'X-Wait-For-Model' => 'true',
        ])
        ->timeout(60) // Shorter timeout for HF to allow faster fallback
        ->post($endpoint, [
            'inputs' => $prompt,
            'parameters' => [
                'guidance_scale' => 3.5,
                'num_inference_steps' => 4,
            ]
        ]);

        if ($response->successful()) {
            $imageContent = $response->body();
            if (strlen($imageContent) > 2000) {
                $path = "menu-images/{$item->id}.png";
                Storage::disk('public')->put($path, $imageContent);
                return $path;
            }
        }

        throw new Exception($response->json()['error'] ?? 'HF returned invalid or empty response.');
    }

    private function tryPollinations(MenuItem $item)
    {
        $categoryName = $item->categoryInfo?->name ?? 'Dish';
        $prompt = urlencode("High-end gourmet food photography of {$item->name}. {$item->description}. Styled as a premium {$categoryName}, exquisite restaurant plating, cinematic lighting, 4k resolution, professional food styling.");
        
        // Use a random seed to ensure a new image each time
        $seed = rand(1000, 9999);
        $url = "https://image.pollinations.ai/prompt/{$prompt}?width=1024&height=1024&seed={$seed}&nologo=true&model=flux";

        $response = Http::timeout(60)->get($url);

        if ($response->successful()) {
            $imageContent = $response->body();
            if (strlen($imageContent) > 2000) {
                $path = "menu-images/{$item->id}.png";
                Storage::disk('public')->put($path, $imageContent);
                return $path;
            }
        }

        throw new Exception('Pollinations.ai fallback also failed.');
    }
}
