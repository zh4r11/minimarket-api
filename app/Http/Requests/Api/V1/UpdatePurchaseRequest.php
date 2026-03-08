<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePurchaseRequest extends FormRequest
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
        $model = $this->route('purchase');
        $id = is_object($model) ? $model->id : $model;

        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'invoice_number' => ['sometimes', 'string', Rule::unique('purchases', 'invoice_number')->ignore($id)],
            'purchase_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['in:draft,confirmed,received,cancelled'],
        ];
    }
}
