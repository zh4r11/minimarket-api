<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSaleRequest extends FormRequest
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
            'sale_date' => ['sometimes', 'date'],
            'discount_amount' => ['numeric', 'min:0'],
            'tax_amount' => ['numeric', 'min:0'],
            'paid_amount' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['in:cash,card,transfer'],
            'status' => ['in:draft,completed,cancelled'],
        ];
    }
}
