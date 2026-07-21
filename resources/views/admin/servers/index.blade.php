@extends('layouts.admin')

@section('title')
    List Servers
@endsection

@section('content-header')
    <h1>Servers<small>All servers available on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Servers</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <form id="bulk-servers-form" action="{{ route('admin.servers.bulk') }}" method="POST">
            {!! csrf_field() !!}
            <input type="hidden" name="action" id="bulk-servers-action" value="">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Server List</h3>
                    <div class="box-tools search01">
                        <div class="input-group input-group-sm" style="width: 340px;">
                            <div class="input-group-btn" style="width: auto;">
                                <button type="button" id="mass_actions" class="btn btn-sm btn-default dropdown-toggle disabled" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Mass Actions <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" data-bulk-action="suspend">Suspend</a></li>
                                    <li><a href="#" data-bulk-action="unsuspend">Unsuspend</a></li>
                                </ul>
                            </div>
                            <input type="text" name="filter[*]" form="servers-search-form" class="form-control pull-right" value="{{ request()->input()['filter']['*'] ?? '' }}" placeholder="Search Servers">
                            <div class="input-group-btn">
                                <button type="submit" form="servers-search-form" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.servers.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="select-all-servers"></th>
                                <th>Server Name</th>
                                <th>UUID</th>
                                <th>Owner</th>
                                <th>Node</th>
                                <th>Connection</th>
                                <th></th>
                                <th></th>
                            </tr>
                            @foreach ($servers as $server)
                                <tr data-server="{{ $server->uuidShort }}">
                                    <td><input type="checkbox" class="select-server" name="ids[]" value="{{ $server->id }}"></td>
                                    <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                                    <td><code title="{{ $server->uuid }}">{{ $server->uuid }}</code></td>
                                    <td><a href="{{ route('admin.users.view', $server->user->id) }}">{{ $server->user->username }}</a></td>
                                    <td><a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a></td>
                                    <td>
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    </td>
                                    <td class="text-center">
                                        @if($server->isSuspended())
                                            <span class="label bg-maroon">Suspended</span>
                                        @elseif(! $server->isInstalled())
                                            <span class="label label-warning">Installing</span>
                                        @else
                                            <span class="label label-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-xs btn-default" href="/server/{{ $server->uuidShort }}"><i class="fa fa-wrench"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($servers->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">{!! $servers->appends(['filter' => Request::input('filter')])->render() !!}</div>
                    </div>
                @endif
            </div>
        </form>
        <form id="servers-search-form" action="{{ route('admin.servers') }}" method="GET" class="hidden"></form>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function updateMassActions() {
            var count = $('.select-server:checked').length;
            if (count > 0) {
                $('#mass_actions').removeClass('disabled');
            } else {
                $('#mass_actions').addClass('disabled');
            }
        }

        $('#select-all-servers').on('change', function () {
            $('.select-server').prop('checked', $(this).is(':checked'));
            updateMassActions();
        });

        $(document).on('change', '.select-server', updateMassActions);

        $('[data-bulk-action]').on('click', function (e) {
            e.preventDefault();
            var action = $(this).data('bulk-action');
            var count = $('.select-server:checked').length;
            if (count < 1) {
                swal({ type: 'warning', title: '', text: 'Select at least one server.' });
                return;
            }

            var label = action === 'suspend' ? 'suspend' : 'unsuspend';
            swal({
                title: '',
                text: 'Are you sure you want to ' + label + ' ' + count + ' server(s)?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                confirmButtonText: 'Yes',
                closeOnConfirm: true
            }, function () {
                $('#bulk-servers-action').val(action);
                $('#bulk-servers-form').submit();
            });
        });
    </script>
@endsection
