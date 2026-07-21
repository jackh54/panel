@extends('layouts.admin')

@section('title')
    List Users
@endsection

@section('content-header')
    <h1>Users<small>All registered users on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Users</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <form id="bulk-users-form" action="{{ route('admin.users.bulk') }}" method="POST">
            {!! csrf_field() !!}
            <input type="hidden" name="action" id="bulk-users-action" value="">
            <input type="hidden" name="subject" id="bulk-users-subject" value="">
            <input type="hidden" name="body" id="bulk-users-body" value="">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">User List</h3>
                    <div class="box-tools search01">
                        <div class="input-group input-group-sm" style="width: 320px;">
                            <div class="input-group-btn" style="width: auto;">
                                <button type="button" id="mass_actions" class="btn btn-sm btn-default dropdown-toggle disabled" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Mass Actions <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" data-bulk-action="suspend">Suspend</a></li>
                                    <li><a href="#" data-bulk-action="unsuspend">Unsuspend</a></li>
                                    <li><a href="#" data-bulk-action="email">Send Email</a></li>
                                </ul>
                            </div>
                            <input type="text" name="filter[email]" form="users-search-form" class="form-control pull-right" value="{{ request()->input('filter.email') }}" placeholder="Search">
                            <div class="input-group-btn">
                                <button type="submit" form="users-search-form" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.users.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="select-all-users"></th>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Client Name</th>
                                <th>Username</th>
                                <th class="text-center">2FA</th>
                                <th class="text-center"><span data-toggle="tooltip" data-placement="top" title="Servers that this user is marked as the owner of.">Servers Owned</span></th>
                                <th class="text-center"><span data-toggle="tooltip" data-placement="top" title="Servers that this user can access because they are marked as a subuser.">Can Access</span></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="align-middle">
                                    <td><input type="checkbox" class="select-user" name="ids[]" value="{{ $user->id }}"></td>
                                    <td><code>{{ $user->id }}</code></td>
                                    <td><a href="{{ route('admin.users.view', $user->id) }}">{{ $user->email }}</a> @if($user->root_admin)<i class="fa fa-star text-yellow"></i>@endif @if($user->suspended)<span class="label label-danger">Suspended</span>@endif</td>
                                    <td>{{ $user->name_last }}, {{ $user->name_first }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td class="text-center">
                                        @if($user->use_totp)
                                            <i class="fa fa-lock text-green"></i>
                                        @else
                                            <i class="fa fa-unlock text-red"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.servers', ['filter[owner_id]' => $user->id]) }}">{{ $user->servers_count }}</a>
                                    </td>
                                    <td class="text-center">{{ $user->subuser_of_count }}</td>
                                    <td class="text-center"><img src="https://www.gravatar.com/avatar/{{ md5(strtolower($user->email)) }}?s=100" style="height:20px;" class="img-circle" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $users->appends(['query' => Request::input('query')])->render() !!}</div>
                    </div>
                @endif
            </div>
        </form>
        <form id="users-search-form" action="{{ route('admin.users') }}" method="GET" class="hidden"></form>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function updateMassActions() {
            var count = $('.select-user:checked').length;
            if (count > 0) {
                $('#mass_actions').removeClass('disabled');
            } else {
                $('#mass_actions').addClass('disabled');
            }
        }

        $('#select-all-users').on('change', function () {
            $('.select-user').prop('checked', $(this).is(':checked'));
            updateMassActions();
        });

        $(document).on('change', '.select-user', updateMassActions);

        $('[data-bulk-action]').on('click', function (e) {
            e.preventDefault();
            var action = $(this).data('bulk-action');
            var count = $('.select-user:checked').length;
            if (count < 1) {
                swal({ type: 'warning', title: '', text: 'Select at least one user.' });
                return;
            }

            if (action === 'email') {
                swal({
                    title: 'Email ' + count + ' user(s)',
                    text: 'Subject',
                    type: 'input',
                    showCancelButton: true,
                    closeOnConfirm: false,
                    inputPlaceholder: 'Email subject'
                }, function (subject) {
                    if (subject === false) return;
                    if (!subject || !String(subject).trim()) {
                        swal.showInputError('Subject is required.');
                        return false;
                    }
                    swal({
                        title: 'Message body',
                        text: 'Plain text email body',
                        type: 'input',
                        showCancelButton: true,
                        closeOnConfirm: true,
                        inputPlaceholder: 'Message...'
                    }, function (body) {
                        if (body === false) return;
                        if (!body || !String(body).trim()) {
                            swal({ type: 'error', title: '', text: 'Message body is required.' });
                            return;
                        }
                        $('#bulk-users-action').val('email');
                        $('#bulk-users-subject').val(subject);
                        $('#bulk-users-body').val(body);
                        $('#bulk-users-form').submit();
                    });
                });
                return;
            }

            var label = action === 'suspend' ? 'suspend' : 'unsuspend';
            swal({
                title: '',
                text: 'Are you sure you want to ' + label + ' ' + count + ' user(s)?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                confirmButtonText: 'Yes',
                closeOnConfirm: true
            }, function () {
                $('#bulk-users-action').val(action);
                $('#bulk-users-form').submit();
            });
        });
    </script>
@endsection
