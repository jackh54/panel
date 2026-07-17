@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img
    src="{{ config('brand.logo') }}"
    class="logo"
    alt="{{ config('brand.name') }}"
    style="max-height: 72px; max-width: 220px; width: auto; height: auto;"
>
</a>
</td>
</tr>
