<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Support\Collection;

class AiProviderRouter
{
    /**
     * Candidate providers in priority order, limited to a handful so a
     * single chat request never tries to fail through the whole list.
     */
    public function candidates(int $limit = 3): Collection
    {
        return AiProvider::query()
            ->where('is_active', true)
            ->ordered()
            ->get()
            ->filter(fn (AiProvider $provider) => $provider->isAvailable())
            ->take($limit)
            ->values();
    }
}
