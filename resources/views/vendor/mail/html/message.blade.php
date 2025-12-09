@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

@if(View::hasSection('top_footer'))
@yield('top_footer')
@else
Une question ? Contactez-nous par <a href="mailto:fitnessclic@gmail.com">e-mail</a>.

À bientôt, <br />
L'équipe FitnessClic
@endif

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
@endcomponent
@endslot
@endcomponent
