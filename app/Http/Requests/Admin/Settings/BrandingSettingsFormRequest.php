<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class BrandingSettingsFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'brand:company' => 'required|string|max:191',
            'brand:url' => 'required|url|max:191',
            'brand:logo_path' => 'required|string|max:191',
            'brand:link_docs_label' => 'nullable|string|max:64',
            'brand:link_docs_url' => 'nullable|url|max:191',
            'brand:link_discord_label' => 'nullable|string|max:64',
            'brand:link_discord_url' => 'nullable|url|max:191',
            'brand:link_billing_label' => 'nullable|string|max:64',
            'brand:link_billing_url' => 'nullable|url|max:191',
            'brand:announcement_enabled' => 'required|in:true,false',
            'brand:announcement_message' => 'nullable|string|max:2000',
            'brand:announcement_type' => ['required', Rule::in(['info', 'warning', 'danger'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'brand:company' => 'Company Name',
            'brand:url' => 'Company URL',
            'brand:logo_path' => 'Logo Path',
            'brand:link_docs_label' => 'Docs Link Label',
            'brand:link_docs_url' => 'Docs Link URL',
            'brand:link_discord_label' => 'Discord Link Label',
            'brand:link_discord_url' => 'Discord Link URL',
            'brand:link_billing_label' => 'Billing Link Label',
            'brand:link_billing_url' => 'Billing Link URL',
            'brand:announcement_enabled' => 'Announcement Enabled',
            'brand:announcement_message' => 'Announcement Message',
            'brand:announcement_type' => 'Announcement Type',
        ];
    }
}
