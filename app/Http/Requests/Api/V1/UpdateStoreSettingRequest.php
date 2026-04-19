<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateStoreSettingRequest extends FormRequest
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
            'store_name'        => ['sometimes', 'required', 'string', 'max:255'],
            'store_tagline'     => ['nullable', 'string', 'max:255'],
            'store_address'     => ['nullable', 'string', 'max:500'],
            'store_city'        => ['nullable', 'string', 'max:100'],
            'store_province'    => ['nullable', 'string', 'max:100'],
            'store_postal_code' => ['nullable', 'string', 'max:20'],
            'store_phone'       => ['nullable', 'string', 'max:30'],
            'store_email'       => ['nullable', 'email', 'max:255'],
            'currency_code'     => ['nullable', 'string', 'max:10'],
            'currency_symbol'   => ['nullable', 'string', 'max:10'],
            'tax_enabled'       => ['nullable', 'boolean'],
            'tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'receipt_footer'    => ['nullable', 'string', 'max:500'],
        ];
    }
}
