<?php

namespace Pterodactyl\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class BulkServerFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:servers,id',
            'action' => ['required', Rule::in(['suspend', 'unsuspend'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'selected servers',
            'action' => 'bulk action',
        ];
    }
}
