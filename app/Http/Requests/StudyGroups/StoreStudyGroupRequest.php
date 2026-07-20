<?php

namespace App\Http\Requests\StudyGroups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', Rule::in(['public', 'friends', 'password'])],
            'password' => ['nullable', 'required_if:visibility,password', 'string', 'min:4', 'max:40'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => trim((string) $this->input('description')),
        ]);
    }
}
