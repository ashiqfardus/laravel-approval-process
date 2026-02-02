<?php

namespace AshiqFardus\ApprovalProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'delegated_to_user_id' => 'required|exists:users,id',
            'approval_step_id' => 'nullable|exists:approval_steps,id',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'delegation_type' => 'required|in:temporary,permanent,emergency',
            'module_type' => 'nullable|string',
            'role_type' => 'nullable|string',
            'reason' => 'nullable|string',
        ];
    }
}
