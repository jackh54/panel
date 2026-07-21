<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Filters\AdminServerFilter;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Http\Requests\Admin\BulkServerFormRequest;

class ServerController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SuspensionService $suspensionService,
    ) {
    }

    /**
     * Returns all the servers that exist on the system using a paginated result set. If
     * a query is passed along in the request it is also passed to the repository function.
     */
    public function index(Request $request): View
    {
        $servers = QueryBuilder::for(Server::query()->with('node', 'user', 'allocation'))
            ->allowedFilters([
                AllowedFilter::exact('owner_id'),
                AllowedFilter::custom('*', new AdminServerFilter()),
            ])
            ->paginate(config()->get('pterodactyl.paginate.admin.servers'));

        return view('admin.servers.index', ['servers' => $servers]);
    }

    /**
     * Apply a bulk suspend/unsuspend action to selected servers.
     */
    public function bulk(BulkServerFormRequest $request): RedirectResponse
    {
        $action = $request->input('action') === 'unsuspend'
            ? SuspensionService::ACTION_UNSUSPEND
            : SuspensionService::ACTION_SUSPEND;

        $ids = array_map('intval', $request->input('ids', []));
        $servers = Server::query()->whereIn('id', $ids)->get();

        $ok = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($servers as $server) {
            if (!is_null($server->transfer)) {
                $skipped++;
                continue;
            }

            try {
                $this->suspensionService->toggle($server, $action);
                $ok++;
            } catch (\Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $label = $action === SuspensionService::ACTION_SUSPEND ? 'suspended' : 'unsuspended';
        $parts = ["{$ok} server(s) {$label}."];
        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped (transferring).";
        }
        if ($failed > 0) {
            $parts[] = "{$failed} failed.";
        }

        if ($failed > 0 && $ok === 0) {
            $this->alert->danger(implode(' ', $parts))->flash();
        } elseif ($failed > 0 || $skipped > 0) {
            $this->alert->warning(implode(' ', $parts))->flash();
        } else {
            $this->alert->success(implode(' ', $parts))->flash();
        }

        return redirect()->route('admin.servers');
    }
}
