<?php

namespace App\Jobs;

use App\Models\MenuItem;
use App\Services\AIImageService;
use App\Events\MenuItemUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMenuItemImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $menuItem;
    public $tries = 3;
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(MenuItem $menuItem)
    {
        $this->menuItem = $menuItem;
    }

    /**
     * Execute the job.
     */
    public function handle(AIImageService $aiService): void
    {
        $imagePath = $aiService->generateMenuItemImage($this->menuItem);

        if ($imagePath) {
            $this->menuItem->update([
                'image' => $imagePath
            ]);

            // Broadcast the update via WebSockets
            broadcast(new MenuItemUpdated($this->menuItem));
        }
    }
}
