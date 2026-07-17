<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Notifications\NotificationTemplateService;
use Pterodactyl\Http\Requests\Admin\Settings\EmailTemplateFormRequest;

class EmailTemplatesController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private NotificationTemplateService $templates,
    ) {
    }

    public function index(): View
    {
        return view('admin.settings.email-templates', [
            'templates' => $this->templates->all(),
        ]);
    }

    public function update(EmailTemplateFormRequest $request, string $type): RedirectResponse
    {
        $this->assertKnownType($type);
        $this->templates->save($type, $request->normalized());
        $this->alert->success('Email template has been updated successfully.')->flash();

        return redirect()->to(route('admin.settings.email-templates') . '#template-' . $type);
    }

    public function reset(string $type): RedirectResponse
    {
        $this->assertKnownType($type);
        $this->templates->reset($type);
        $this->alert->success('Email template has been reset to the default content.')->flash();

        return redirect()->to(route('admin.settings.email-templates') . '#template-' . $type);
    }

    private function assertKnownType(string $type): void
    {
        if (!array_key_exists($type, config('notifications.templates', []))) {
            abort(404);
        }
    }
}
