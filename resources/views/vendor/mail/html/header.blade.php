@props(['url'])
@php
    $logoSrc = config('brand.logo') ?: url(config('brand.logo_path', '/branding/logo.png'));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img
    src="{{ $logoSrc }}"
    class="logo"
    alt="{{ config('brand.name') }}"
    style="max-height: 72px; max-width: 220px; width: auto; height: auto;"
>
</a>
</td>
</tr>
