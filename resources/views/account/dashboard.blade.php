<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi perfil · Divas Cuyo</title>@vite('resources/css/styles.css')
</head>
<body class="account-page account-dashboard-page">
@php
    $identityState = match ($profile->identity_status) {
        'pending' => ['label'=>'En revisión','description'=>'Estamos revisando tu documentación.','class'=>'pending','action'=>'Consultar verificación'],
        'approved' => ['label'=>'Validada','description'=>'Tu identidad fue validada correctamente.','class'=>'approved','action'=>'Ver verificación'],
        'rejected' => ['label'=>'Requiere corrección','description'=>'Revisá el motivo y corregí la documentación.','class'=>'rejected','action'=>'Corregir documentación'],
        default => ['label'=>'Incompleta','description'=>'Completá la documentación para validar tu identidad.','class'=>'incomplete','action'=>'Completar verificación'],
    };
    $reviewState = match ($profile->review_status) {
        'approved' => ['label'=>'Aprobado','description'=>'Tu perfil fue aprobado.','class'=>'approved'],
        'rejected' => ['label'=>'Requiere atención','description'=>'Revisá las indicaciones de tu perfil.','class'=>'rejected'],
        default => ['label'=>'Pendiente','description'=>'Tu perfil todavía está pendiente de revisión.','class'=>'pending'],
    };
    $completionItems = [filled($profile->name), filled($profile->whatsapp), filled($profile->currentBio?->content), filled($profile->publication_type_id), $profile->identity_status === 'approved'];
    $completionPercent = (int) round((count(array_filter($completionItems)) / count($completionItems)) * 100);
    $paymentIsCurrent = $profile->currentPayment && ! $profile->currentPayment->expires_at->isPast();
    $nextSteps = [];
    if ($profile->identity_status !== 'approved') $nextSteps[] = ['label'=>'Completar identidad','href'=>route('identity.show',$user),'done'=>false];
    if (! $profile->currentBio) $nextSteps[] = ['label'=>'Completar información del perfil','href'=>route('account.profile.edit'),'done'=>false];
    if (! $paymentIsCurrent) $nextSteps[] = ['label'=>'Realizar el pago de publicación','href'=>'#instrucciones-pago','done'=>false];
    if ($user->hasVerifiedEmail()) $nextSteps[] = ['label'=>'Verificar tu email','href'=>'#estado-email','done'=>true];
    if ($profile->identity_status === 'approved') $nextSteps[] = ['label'=>'Identidad validada','href'=>route('identity.show',$user),'done'=>true];
    $nextSteps = array_slice($nextSteps, 0, 5);
@endphp
<div class="account-app-shell">
    <aside class="account-sidebar" aria-label="Navegación de mi cuenta">
        <a href="{{ route('account.dashboard') }}" class="account-brand"><img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="" class="account-logo"><span>Divas<span>Cuyo</span></span></a>
        <nav class="account-sidebar-nav">
            <a class="is-active" href="{{ route('account.dashboard') }}" aria-current="page"><span>⌂</span> Mi perfil</a><a href="{{ route('account.photos.index') }}"><span>▧</span> Mis fotos</a><a href="{{ route('account.videos.index') }}"><span>▣</span> Mis videos</a><a href="{{ route('account.services.edit') }}"><span>◉</span> Servicios y tipo de publicación</a><a href="{{ route('account.dashboard') }}#estadisticas"><span>▥</span> Estadísticas</a>
        </nav>
        <nav class="account-sidebar-bottom"><a href="{{ route('terms.show') }}"><span>?</span> Centro de ayuda</a><a href="{{ route('account.profile.edit') }}"><span>⚙</span> Configuración</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span>↪</span> Cerrar sesión</button></form></nav>
    </aside>
    <main class="account-main" id="contenido-principal">
        <header class="account-dashboard-topbar"><span class="account-notification">♧</span><span class="account-avatar">{{ mb_strtoupper(mb_substr($profile->name,0,1)) }}</span><strong>{{ $profile->name }}</strong><span>⌄</span></header>
        <section class="dashboard-welcome"><div><p class="account-eyebrow">Mi perfil</p><h1>Hola, {{ $profile->name }}</h1><p>Mantené tu perfil al día para publicarlo en Divas Cuyo.</p></div><div class="dashboard-updated"><span>◷</span><small>Última actualización</small><strong>{{ optional($profile->updated_at)->format('d/m/Y H:i') }}</strong></div></section>
        <section class="dashboard-completion"><div class="completion-ring" style="--completion: {{ $completionPercent * 3.6 }}deg"><strong>{{ $completionPercent }}%</strong></div><div class="completion-copy"><h2>Tu perfil está {{ $completionPercent }}% completo</h2><p>Completá la información pendiente para que podamos revisar y publicar tu perfil.</p></div><div class="completion-actions"><a class="account-primary-action" href="{{ route('identity.show',$user) }}">Completar identidad <span>→</span></a><a class="account-secondary-action" href="{{ route('account.profile.edit') }}">Editar perfil</a><a class="account-secondary-action" href="#instrucciones-pago">Ver instrucciones de pago</a></div></section>
        <section class="dashboard-section" id="estadisticas"><div class="dashboard-section-heading"><h2>Estados de tu perfil</h2><a href="{{ route('account.show',$user) }}">Ver mi perfil completo <span>→</span></a></div>
            <div class="account-status-grid dashboard-status-grid" aria-live="polite">
                <article class="account-status-card" id="estado-email"><div class="account-status-icon">✉</div><div class="account-status-content"><h3>Email</h3><strong class="account-badge account-badge-success">Verificado</strong><p>Tu correo está confirmado.</p></div></article>
                <article class="account-status-card"><div class="account-status-icon">♢</div><div class="account-status-content"><h3>Identidad</h3><strong class="account-badge account-badge-{{ $identityState['class'] }}">{{ $identityState['label'] }}</strong><p>{{ $identityState['description'] }}</p></div></article>
                <article class="account-status-card"><div class="account-status-icon">▤</div><div class="account-status-content"><h3>Revisión del perfil</h3><strong class="account-badge account-badge-{{ $reviewState['class'] }}">{{ $reviewState['label'] }}</strong><p>{{ $reviewState['description'] }}</p></div></article>
                <article class="account-status-card"><div class="account-status-icon">◷</div><div class="account-status-content"><h3>Publicación</h3><strong class="account-badge {{ $profile->is_published ? 'account-badge-success' : 'account-badge-muted' }}">{{ $profile->is_published ? 'Publicado' : 'No publicado' }}</strong><p>{{ $profile->is_published ? 'Tu perfil es visible públicamente.' : 'Tu perfil todavía no es visible.' }}</p></div></article>
                <article class="account-status-card"><div class="account-status-icon">$</div><div class="account-status-content"><h3>Pago de publicación</h3>@if($profile->currentPayment)<strong class="account-badge {{ $paymentIsCurrent ? 'account-badge-success' : 'account-badge-rejected' }}">{{ $paymentIsCurrent ? 'Vigente' : 'Vencido' }}</strong><p>Vence el {{ $profile->currentPayment->expires_at->format('d/m/Y') }}.</p>@else<strong class="account-badge account-badge-muted">Sin pago registrado</strong><p>Aún no hay un pago registrado.</p>@endif</div></article>
            </div>
        </section>
        <div class="dashboard-columns">
            <section class="dashboard-panel"><h2>Próximos pasos</h2><p class="dashboard-panel-intro">Completá estos pasos para publicar tu perfil.</p><ul class="dashboard-checklist">@foreach($nextSteps as $step)<li class="{{ $step['done'] ? 'is-done' : '' }}"><a href="{{ $step['href'] }}"><span class="check-circle">{{ $step['done'] ? '✓' : '' }}</span><span>{{ $step['label'] }}</span><b>›</b></a></li>@endforeach</ul></section>
            @if($paymentInstructions || $profile->currentPayment)<section class="dashboard-panel dashboard-payment-panel" id="instrucciones-pago"><h2>Instrucciones de pago</h2><p class="dashboard-panel-intro">Para enviar tu comprobante, realizá la transferencia con estos datos:</p><div class="payment-instruction-layout"><div class="payment-alias-card">@if($paymentInstructions?->alias)<small>Alias</small><strong>{{ $paymentInstructions->alias }}</strong><button type="button" class="copy-alias" data-copy="{{ $paymentInstructions->alias }}">Copiar</button>@endif @if($profile->currentPayment)<p>Publicación vigente hasta {{ $profile->currentPayment->expires_at->format('d/m/Y') }}.</p>@endif</div><div class="payment-important"><strong>ⓘ Importante</strong><p>Una vez realizada la transferencia, enviá el comprobante por WhatsApp para que podamos validarlo.</p>@if($paymentInstructions?->receipt_whatsapp)<a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$paymentInstructions->receipt_whatsapp) }}" target="_blank" rel="noopener">Contactar por WhatsApp <span>→</span></a>@endif</div></div>@if($paymentInstructions?->transfer_details || $paymentInstructions?->default_amount)<div class="payment-details">@if($paymentInstructions->transfer_details)<span><b>Datos de transferencia</b>{{ $paymentInstructions->transfer_details }}</span>@endif @if($paymentInstructions->default_amount)<span><b>Importe de referencia</b>$ {{ number_format((float)$paymentInstructions->default_amount,2,',','.') }}</span>@endif</div>@endif @if($profile->payments->isNotEmpty())<div class="payment-history"><h3>Historial de pagos</h3><div class="payment-table-wrap"><table><thead><tr><th>Fecha</th><th>Período</th><th>Importe</th><th>Vencimiento</th><th>Estado</th></tr></thead><tbody>@foreach($profile->payments as $payment)<tr><td>{{ $payment->paid_at->format('d/m/Y') }}</td><td>{{ $payment->period_days }} días</td><td>$ {{ number_format((float)$payment->amount,2,',','.') }}</td><td>{{ $payment->expires_at->format('d/m/Y') }}</td><td><span class="account-badge {{ $payment->expires_at->isPast() ? 'account-badge-rejected' : 'account-badge-success' }}">{{ $payment->expires_at->isPast() ? 'Vencido' : 'Vigente' }}</span></td></tr>@endforeach</tbody></table></div></div>@endif</section>@endif
        </div>
        <section class="dashboard-support"><span class="support-icon">♧</span><div><h2>¿Necesitás ayuda?</h2><p>Si tenés alguna duda o problema, nuestro equipo te acompaña.</p></div><a href="{{ route('terms.show') }}">Contactar soporte <span>›</span></a></section>
    </main>
</div>
<script>document.querySelectorAll('[data-copy]').forEach((button)=>button.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(button.dataset.copy);const label=button.textContent;button.textContent='Copiado';setTimeout(()=>button.textContent=label,1600)}catch(_){}}));</script>
</body></html>
