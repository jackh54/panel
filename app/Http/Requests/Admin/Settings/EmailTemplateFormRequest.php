<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EmailTemplateFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'enabled' => 'sometimes|boolean',
            'subject' => 'required|string|max:255',
            'greeting' => 'nullable|string|max:255',
            'lines' => 'nullable|string|max:10000',
            'action_text' => 'nullable|string|max:255',
            'action_url' => 'nullable|string|max:2048',
            'level' => ['required', 'string', Rule::in(['info', 'success', 'error'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalized(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'subject' => $this->input('subject'),
            'greeting' => $this->input('greeting', ''),
            'lines' => $this->input('lines', ''),
            'action_text' => $this->input('action_text', ''),
            'action_url' => $this->input('action_url', ''),
            'level' => $this->input('level', 'info'),
        ];
    }
}
