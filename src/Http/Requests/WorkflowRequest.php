<?php

namespace AshiqFardus\ApprovalProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model_type' => 'required|string',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
        ];
    }
}
