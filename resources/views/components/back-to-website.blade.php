@props(['tone' => 'light'])
{{-- "Back to website" link for sign-in, sign-up and password pages. --}}
<a href="{{ route('home') }}" {{ $attributes->class(['eh-back-home', 'eh-back-home--'.$tone]) }}>
    <i class="fas fa-arrow-left" aria-hidden="true"></i>
    <span>Back to website</span>
</a>
