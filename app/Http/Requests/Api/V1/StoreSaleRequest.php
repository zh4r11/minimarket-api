<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSaleRequest extends FormRequest
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
        return [
            'sale_date' => ['required', 'date'],
            'discount_amount' => ['numeric', 'min:0'],
            'tax_amount' => ['numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['in:cash,card,transfer'],
            'status' => ['in:draft,completed,cancelled'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required_without:items.*.bundle_id', 'nullable', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.bundle_id' => ['required_without:items.*.product_id', 'nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.sell_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['numeric', 'min:0'],
        ];
    }
}
