<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'want_time' => ['nullable', 'date'],
            'finish_time' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
