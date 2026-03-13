<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBrandRequest extends FormRequest
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
        $model = $this->route('brand');
        $id = is_object($model) ? $model->id : $model;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
