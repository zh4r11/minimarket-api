<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductVariantAttributeValueRequest extends FormRequest
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
        $model = $this->route('product_variant_attribute_value');
        $id = is_object($model) ? $model->id : $model;

        return [
            'attribute_id' => ['sometimes', 'integer', 'exists:product_variant_attributes,id'],
            'value' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('product_variant_attribute_values')->where(function ($query) use ($id): void {
                    $attributeId = $this->input('attribute_id') ?? $this->route('product_variant_attribute_value')?->attribute_id;
                    $query->where('attribute_id', $attributeId);
                })->ignore($id),
            ],
        ];
    }
}
