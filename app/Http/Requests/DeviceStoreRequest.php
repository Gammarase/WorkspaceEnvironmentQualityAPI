<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:100', 'unique:devices,device_id'],
            'name' => ['required', 'string', 'max:255'],
            'longitude' => ['nullable', 'numeric', 'between:-999.99999999,999.99999999'],
            'latitude' => ['nullable', 'numeric', 'between:-99.99999999,99.99999999'],
            'description' => ['nullable', 'string'],
        ];
    }
}
