<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCategoryRequest extends FormRequest
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
        $model = $this->route('category');
        $id = is_object($model) ? $model->id : $model;

        return [            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],            'name' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
