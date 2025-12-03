<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SensorReadingHistoryRequest extends FormRequest
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
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'temperature' => ['required', 'numeric', 'between:-999.99,999.99'],
            'humidity' => ['required', 'numeric', 'between:-999.99,999.99'],
            'tvoc_ppm' => ['nullable', 'integer', 'gt:0'],
            'light' => ['required', 'integer', 'gt:0'],
            'noise' => ['required', 'integer', 'gt:0'],
            'reading_timestamp' => ['required'],
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'temperature' => ['required', 'numeric', 'between:-999.99,999.99'],
            'humidity' => ['required', 'numeric', 'between:-999.99,999.99'],
            'tvoc_ppm' => ['nullable', 'integer', 'gt:0'],
            'light' => ['required', 'integer', 'gt:0'],
            'noise' => ['required', 'integer', 'gt:0'],
            'reading_timestamp' => ['required'],
        ];
    }
}
