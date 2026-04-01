<tr>
<td class="header">
<table class="inner-header" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td width="50%" align="left">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('/assets/logo_fitnessclic.png') }}" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; position: relative; max-width: 100%; border: none; height: 80px; max-height: 80px; width: 80px;" class="logo" width="150px" alt="FitnessClic Logo">
</a>
</td>
<td width="50%" align="right">
<div><strong>{{ $category ?? 'Votre compte' }}</strong></div>
<div>{{ strtolower(\Carbon\Carbon::now()->translatedFormat('d M Y')) }}</div>
</td>
</tr>
</table>
</td>
</tr>
