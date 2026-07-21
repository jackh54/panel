<?php

namespace Pterodactyl\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class BulkUserFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:users,id',
            'action' => ['required', Rule::in(['suspend', 'unsuspend', 'email'])],
            'subject' => 'required_if:action,email|nullable|string|max:191',
            'body' => 'required_if:action,email|nullable|string|max:10000',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'selected users',
            'action' => 'bulk action',
            'subject' => 'email subject',
            'body' => 'email body',
        ];
    }
}
