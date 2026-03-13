<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductVariantAttributeRequest extends FormRequest
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
        $model = $this->route('product_variant_attribute');
        $id = is_object($model) ? $model->id : $model;

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('product_variant_attributes', 'name')->ignore($id)],
        ];
    }
}
