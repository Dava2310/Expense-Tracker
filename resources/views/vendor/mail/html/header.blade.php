@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span class="mark"></span><span class="wordmark">{{ $slot }}</span>
</a>
</td>
</tr>
