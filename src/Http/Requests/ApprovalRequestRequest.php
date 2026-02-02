<?php

namespace AshiqFardus\ApprovalProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workflow_id' => 'required|exists:approval_workflows,id',
            'requestable_type' => 'required|string',
            'requestable_id' => 'required|integer',
            'requested_by_user_id' => 'required|exists:users,id',
            'status' => 'string|in:draft,submitted,in-review,pending,approved,rejected,cancelled,archived',
            'data_snapshot' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
