<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditorImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->level === 1;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }
}
