<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductVariantRequest extends FormRequest
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
        $model = $this->route('product_variant');
        $id = is_object($model) ? $model->id : $model;

        return [
            'parent_id' => ['sometimes', 'integer', 'exists:products,id'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($id)],
            'name' => ['nullable', 'string', 'max:255'],
            'buy_price' => ['sometimes', 'numeric', 'min:0'],
            'sell_price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['integer', 'min:0'],
            'min_stock' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:product_variant_attribute_values,id'],
        ];
    }
}
