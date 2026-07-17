@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'email-templates'])

@section('title')
    Email Templates
@endsection

@section('content-header')
    <h1>Email Templates<small>Edit the subject and body of every panel email.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-info">
                Use placeholders like <code>{{ '{{user_name}}' }}</code> in any field. Leave button text or URL blank to hide the button.
                Disable a template to stop sending that email entirely.
            </div>
        </div>
    </div>

    @foreach($templates as $type => $template)
        <div class="row" id="template-{{ $type }}">
            <div class="col-xs-12">
                <div class="box box-{{ ($template['level'] ?? 'info') === 'error' ? 'danger' : (($template['level'] ?? 'info') === 'success' ? 'success' : 'primary') }}">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $template['label'] ?? $type }}
                            @if(!($template['enabled'] ?? true))
                                <span class="label label-default">Disabled</span>
                            @endif
                        </h3>
                    </div>
                    <form action="{{ route('admin.settings.email-templates.update', $type) }}" method="POST">
                        <div class="box-body">
                            {!! csrf_field() !!}
                            <input type="hidden" name="_method" value="PATCH">

                            <p class="text-muted">{{ $template['description'] ?? '' }}</p>
                            <p class="text-muted" style="margin-bottom: 15px;">
                                Available placeholders:
                                @foreach(($template['placeholders'] ?? []) as $placeholder)
                                    <code>{{ '{{' . $placeholder . '}}' }}</code>@if(!$loop->last), @endif
                                @endforeach
                                , <code>{{ '{{app_name}}' }}</code>, <code>{{ '{{brand_name}}' }}</code>
                            </p>

                            <div class="row">
                                <div class="form-group col-md-3">
                                    <div class="checkbox checkbox-primary" style="margin-top: 0;">
                                        <input type="checkbox" id="enabled_{{ $type }}" name="enabled" value="1" @if($template['enabled'] ?? true) checked @endif>
                                        <label for="enabled_{{ $type }}">Send this email</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="control-label" for="level_{{ $type }}">Tone</label>
                                    <select name="level" id="level_{{ $type }}" class="form-control">
                                        <option value="info" @if(($template['level'] ?? 'info') === 'info') selected @endif>Info</option>
                                        <option value="success" @if(($template['level'] ?? '') === 'success') selected @endif>Success</option>
                                        <option value="error" @if(($template['level'] ?? '') === 'error') selected @endif>Error / Warning</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label" for="subject_{{ $type }}">Subject</label>
                                    <input type="text" name="subject" id="subject_{{ $type }}" class="form-control" value="{{ old('subject', $template['subject'] ?? '') }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="control-label" for="greeting_{{ $type }}">Greeting</label>
                                    <input type="text" name="greeting" id="greeting_{{ $type }}" class="form-control" value="{{ old('greeting', $template['greeting'] ?? '') }}" placeholder="Optional greeting line">
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="control-label" for="lines_{{ $type }}">Body lines</label>
                                    <textarea name="lines" id="lines_{{ $type }}" class="form-control" rows="5" placeholder="One paragraph per line">{{ old('lines', implode("\n", $template['lines'] ?? [])) }}</textarea>
                                    <p class="text-muted small">Each line becomes its own paragraph in the email.</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="control-label" for="action_text_{{ $type }}">Button text</label>
                                    <input type="text" name="action_text" id="action_text_{{ $type }}" class="form-control" value="{{ old('action_text', $template['action_text'] ?? '') }}">
                                </div>
                                <div class="form-group col-md-8">
                                    <label class="control-label" for="action_url_{{ $type }}">Button URL</label>
                                    <input type="text" name="action_url" id="action_url_{{ $type }}" class="form-control" value="{{ old('action_url', $template['action_url'] ?? '') }}" placeholder="{{ '{{action_url}}' }} or https://...">
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-sm btn-primary">Save {{ $template['label'] ?? $type }}</button>
                        </div>
                    </form>
                    <div class="box-footer" style="border-top: 0; padding-top: 0;">
                        <form action="{{ route('admin.settings.email-templates.reset', $type) }}" method="POST" onsubmit="return confirm('Reset this template to the built-in defaults?');">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-sm btn-default">Reset to defaults</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
