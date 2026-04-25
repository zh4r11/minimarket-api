<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $bundleId = $this->route('bundle')?->id;

        return [
            'sku' => ['sometimes', 'string', 'max:100', "unique:products,sku,{$bundleId}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sell_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('type', '!=', 'bundle')),
            ],
            'items.*.variant_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('type', 'variant')),
            ],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $variantIds = collect($items)
                ->pluck('variant_id')
                ->filter(fn ($id): bool => $id !== null)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            if ($variantIds->isEmpty()) {
                return;
            }

            $variants = Product::query()
                ->withoutGlobalScopes()
                ->select(['id', 'type', 'parent_id'])
                ->whereIn('id', $variantIds->all())
                ->get()
                ->keyBy('id');

            foreach ($items as $index => $item) {
                if (! is_array($item) || ($item['variant_id'] ?? null) === null) {
                    continue;
                }

                $variantId = (int) $item['variant_id'];
                $variant = $variants->get($variantId);

                if ($variant === null || $variant->type !== 'variant') {
                    $validator->errors()->add("items.{$index}.variant_id", 'The selected variant_id is invalid.');

                    continue;
                }

                $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;

                if ((int) $variant->parent_id !== $productId) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        'The variant_id must belong to the selected product_id.'
                    );
                }
            }
        });
    }
}
