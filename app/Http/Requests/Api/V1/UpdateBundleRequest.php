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

            $productIds = collect($items)
                ->filter(fn ($item): bool => is_array($item) && isset($item['product_id']))
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $products = Product::query()
                ->withoutGlobalScopes()
                ->select(['id', 'type'])
                ->whereIn('id', $productIds->all())
                ->get()
                ->keyBy('id');

            $variantIds = collect($items)
                ->pluck('variant_id')
                ->filter(fn ($id): bool => $id !== null)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $variants = $variantIds->isNotEmpty()
                ? Product::query()
                    ->withoutGlobalScopes()
                    ->select(['id', 'type', 'parent_id'])
                    ->whereIn('id', $variantIds->all())
                    ->get()
                    ->keyBy('id')
                : collect();

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                $product = $products->get($productId);

                if ($product !== null && $product->type === 'parent' && ($item['variant_id'] ?? null) === null) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        'The variant_id is required when product_id is a parent product.'
                    );
                    continue;
                }

                if (($item['variant_id'] ?? null) === null) {
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
