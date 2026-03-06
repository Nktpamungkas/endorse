<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndorsementRevisionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'revision_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'uploaded_to_drive' => ['sometimes', 'boolean'],
            'is_approved' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'uploaded_to_drive' => $this->boolean('uploaded_to_drive'),
            'is_approved' => $this->boolean('is_approved'),
        ]);
    }
}
