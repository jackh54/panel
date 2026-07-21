@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'branding'])

@section('title')
    Branding Settings
@endsection

@section('content-header')
    <h1>Branding<small>Company links, logo, and client announcement banner.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Company & Logo</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Company Name</label>
                                <input type="text" class="form-control" name="brand:company" value="{{ old('brand:company', config('brand.company')) }}">
                                <p class="text-muted small">Shown in login and panel footers.</p>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Company URL</label>
                                <input type="url" class="form-control" name="brand:url" value="{{ old('brand:url', config('brand.url')) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Logo Path</label>
                                <input type="text" class="form-control" name="brand:logo_path" value="{{ old('brand:logo_path', config('brand.logo_path')) }}">
                                <p class="text-muted small">Public path or absolute URL, e.g. <code>/branding/logo.png</code>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Login & Footer Links</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">Optional links on the login page and client panel footer. Leave a URL blank to hide that link.</p>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label class="control-label">Docs Label</label>
                                <input type="text" class="form-control" name="brand:link_docs_label" value="{{ old('brand:link_docs_label', config('brand.link_docs_label')) }}">
                            </div>
                            <div class="form-group col-md-9">
                                <label class="control-label">Docs URL</label>
                                <input type="url" class="form-control" name="brand:link_docs_url" value="{{ old('brand:link_docs_url', config('brand.link_docs_url')) }}" placeholder="https://...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label class="control-label">Discord Label</label>
                                <input type="text" class="form-control" name="brand:link_discord_label" value="{{ old('brand:link_discord_label', config('brand.link_discord_label')) }}">
                            </div>
                            <div class="form-group col-md-9">
                                <label class="control-label">Discord URL</label>
                                <input type="url" class="form-control" name="brand:link_discord_url" value="{{ old('brand:link_discord_url', config('brand.link_discord_url')) }}" placeholder="https://discord.gg/...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label class="control-label">Billing Label</label>
                                <input type="text" class="form-control" name="brand:link_billing_label" value="{{ old('brand:link_billing_label', config('brand.link_billing_label')) }}">
                            </div>
                            <div class="form-group col-md-9">
                                <label class="control-label">Billing URL</label>
                                <input type="url" class="form-control" name="brand:link_billing_url" value="{{ old('brand:link_billing_url', config('brand.link_billing_url')) }}" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Announcement Banner</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label class="control-label">Status</label>
                                <select class="form-control" name="brand:announcement_enabled">
                                    <option value="false" @if(old('brand:announcement_enabled', config('brand.announcement_enabled')) == false || old('brand:announcement_enabled', config('brand.announcement_enabled')) == '0') selected @endif>Disabled</option>
                                    <option value="true" @if(old('brand:announcement_enabled', config('brand.announcement_enabled')) == true || old('brand:announcement_enabled', config('brand.announcement_enabled')) == '1') selected @endif>Enabled</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="control-label">Type</label>
                                <select class="form-control" name="brand:announcement_type">
                                    @php($type = old('brand:announcement_type', config('brand.announcement_type', 'info')))
                                    <option value="info" @if($type === 'info') selected @endif>Info</option>
                                    <option value="warning" @if($type === 'warning') selected @endif>Warning</option>
                                    <option value="danger" @if($type === 'danger') selected @endif>Danger / Maintenance</option>
                                </select>
                            </div>
                            <div class="form-group col-md-12">
                                <label class="control-label">Message</label>
                                <textarea class="form-control" name="brand:announcement_message" rows="3" maxlength="2000">{{ old('brand:announcement_message', config('brand.announcement_message')) }}</textarea>
                                <p class="text-muted small">Shown below the navigation bar for all logged-in users. Plain text only.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {!! csrf_field() !!}
                <div class="box box-primary">
                    <div class="box-footer">
                        {{ method_field('PATCH') }}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
