<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable'],
            'email' => ['nullable', 'email', 'max:254'],
            'email_verified_at' => ['nullable', 'date'],
            'password' => ['nullable'],
            'chat_id' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
