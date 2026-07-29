<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Model;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Notifications\AdminUserMessage;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Services\Users\UserSuspensionService;
use Pterodactyl\Http\Requests\Admin\NewUserFormRequest;
use Pterodactyl\Http\Requests\Admin\BulkUserFormRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserController extends Controller
{
    use AvailableLanguages;

    /**
     * UserController constructor.
     */
    public function __construct(
        protected AlertsMessageBag $alert,
        protected UserCreationService $creationService,
        protected UserDeletionService $deletionService,
        protected Translator $translator,
        protected UserUpdateService $updateService,
        protected UserSuspensionService $suspensionService,
        protected UserRepositoryInterface $repository,
        protected ViewFactory $view,
    ) {
    }

    /**
     * Display user index page.
     */
    public function index(Request $request): View
    {
        $users = QueryBuilder::for(
            User::query()->select('users.*')
                ->selectRaw('COUNT(DISTINCT(subusers.id)) as subuser_of_count')
                ->selectRaw('COUNT(DISTINCT(servers.id)) as servers_count')
                ->leftJoin('subusers', 'subusers.user_id', '=', 'users.id')
                ->leftJoin('servers', 'servers.owner_id', '=', 'users.id')
                ->groupBy('users.id')
        )
            ->allowedFilters(['username', 'email', 'uuid'])
            ->defaultSort('-root_admin')
            ->allowedSorts(['id', 'uuid'])
            ->paginate(50);

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Display new user page.
     */
    public function create(): View
    {
        return view('admin.users.new', [
            'languages' => $this->getAvailableLanguages(true),
        ]);
    }

    /**
     * Display user view page.
     */
    public function view(User $user): View
    {
        return view('admin.users.view', [
            'user' => $user,
            'languages' => $this->getAvailableLanguages(true),
        ]);
    }

    /**
     * Delete a user from the system.
     *
     * @throws \Exception
     * @throws DisplayException
     */
    public function delete(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw new DisplayException(__('admin/user.exceptions.delete_self'));
        }

        $this->deletionService->handle($user);

        return redirect()->route('admin.users');
    }

    /**
     * Create a user.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function store(NewUserFormRequest $request): RedirectResponse
    {
        $user = $this->creationService->handle($request->normalize());
        $this->alert->success($this->translator->get('admin/user.notices.account_created'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Update a user on the system.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UserFormRequest $request, User $user): RedirectResponse
    {
        $this->updateService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($user, $request->normalize());

        $this->alert->success(trans('admin/user.notices.account_updated'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Suspend a user account and all servers they own.
     *
     * @throws DisplayException
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw new DisplayException('You cannot suspend your own account.');
        }

        try {
            $this->suspensionService->suspend($user);
            $this->alert->success('Account has been suspended. All owned servers were suspended and active sessions were revoked.')->flash();
        } catch (\Throwable $exception) {
            report($exception);

            if ($user->refresh()->suspended) {
                $this->alert->warning('Account was suspended, but some follow-up work failed. Check the logs for details.')->flash();
            } else {
                $this->alert->danger('Failed to suspend this account. Check the logs for details.')->flash();
            }
        }

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Unsuspend a user account and restore servers suspended with the account.
     */
    public function unsuspend(User $user): RedirectResponse
    {
        try {
            $this->suspensionService->unsuspend($user);
            $this->alert->success('Account has been unsuspended. Servers suspended with the account were restored.')->flash();
        } catch (\Throwable $exception) {
            report($exception);

            if (!$user->refresh()->suspended) {
                $this->alert->warning('Account was unsuspended, but some follow-up work failed. Check the logs for details.')->flash();
            } else {
                $this->alert->danger('Failed to unsuspend this account. Check the logs for details.')->flash();
            }
        }

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Apply a bulk action to selected users (suspend, unsuspend, or email).
     */
    public function bulk(BulkUserFormRequest $request): RedirectResponse
    {
        $action = $request->input('action');
        $ids = array_map('intval', $request->input('ids', []));
        $users = User::query()->whereIn('id', $ids)->get();

        $ok = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            if ($action !== 'email' && $request->user()->is($user)) {
                ++$skipped;
                continue;
            }

            try {
                if ($action === 'suspend') {
                    $this->suspensionService->suspend($user);
                } elseif ($action === 'unsuspend') {
                    $this->suspensionService->unsuspend($user);
                } else {
                    $user->notify(new AdminUserMessage(
                        (string) $request->input('subject'),
                        (string) $request->input('body'),
                    ));
                }
                ++$ok;
            } catch (\Throwable $exception) {
                report($exception);
                ++$failed;
            }
        }

        $label = match ($action) {
            'suspend' => 'suspended',
            'unsuspend' => 'unsuspended',
            default => 'emailed',
        };

        $parts = ["{$ok} user(s) {$label}."];
        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped.";
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

        return redirect()->route('admin.users');
    }

    /**
     * Get a JSON response of users on the system.
     */
    public function json(Request $request): Model|Collection
    {
        $users = QueryBuilder::for(User::query())->allowedFilters(['email'])->paginate(25);

        // Handle single user requests.
        if ($request->query('user_id')) {
            $user = User::query()->findOrFail($request->input('user_id'));
            // @phpstan-ignore-next-line property.notFound
            $user->md5 = md5(strtolower($user->email));

            return $user;
        }

        return $users->map(function ($item) {
            // @phpstan-ignore-next-line property.notFound
            $item->md5 = md5(strtolower($item->email));

            return $item;
        });
    }
}
