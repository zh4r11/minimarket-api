<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bundleId = $this->route('bundle')?->id;

        return [
            'sku' => ['sometimes', 'string', 'max:100', "unique:bundles,sku,{$bundleId}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sell_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
