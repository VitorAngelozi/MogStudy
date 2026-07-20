<?php

namespace App\Http\Requests\StudyGroups;

use Illuminate\Foundation\Http\FormRequest;

class StartFocusStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'study_subject_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
