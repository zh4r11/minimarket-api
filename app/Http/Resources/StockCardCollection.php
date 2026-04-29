<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Illuminate\Database\Eloquent\Collection<int, array<string, mixed>>
 */
final class StockCardCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_entries' => $this->collection->count(),
                'has_beginning_balance' => $this->collection->isNotEmpty() && $this->collection->first()['type'] === 'initial',
            ],
        ];
    }
}
