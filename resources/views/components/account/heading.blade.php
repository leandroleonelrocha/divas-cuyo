@props(['name', 'visible' => false, 'showStatus' => true])
<section class="dashboard-welcome">
    <div><p class="account-eyebrow">Mi cuenta</p><h1>¡Hola, {{ $name }}!</h1>@if ($showStatus)<p>{{ $visible ? 'Tu publicación está visible' : 'Tu publicación no está visible' }}</p>@endif</div>
</section>
