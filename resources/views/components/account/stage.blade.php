@props(['title', 'id'])
<section {{ $attributes->class(['dashboard-panel', 'profile-setup', 'profile-setup-full', 'account-stage']) }} aria-labelledby="{{ $id }}">
    <div class="profile-setup-heading"><h2 id="{{ $id }}">{{ $title }}</h2></div>
    {{ $slot }}
</section>
