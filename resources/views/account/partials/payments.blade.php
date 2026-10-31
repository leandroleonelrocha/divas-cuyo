@php
    $paymentState = app(\App\Services\PublicationPaymentStatus::class)->summary($profile);
    $paymentCurrent = in_array($paymentState['status'], ['current', 'near_expiry'], true);
    $amount = config('publication.monthly_prices_ars.'.$profile->publicationType?->slug);
    $warningDays = max(0, (int) config('publication.expiry_warning_days', 7));
@endphp
<div class="payments-screen">
    <header class="payments-heading">
        <a href="{{ route('account.dashboard') }}">← Volver a Inicio</a>
        <h1>Mis pagos</h1>
        <p>Consultá tu vigencia, los datos para transferir y tus pagos registrados.</p>
    </header>
    <section class="dashboard-panel payments-state" aria-labelledby="payment-state-title">
        <h2 id="payment-state-title">Estado del pago</h2>
        <span class="account-badge {{ $paymentCurrent ? 'account-badge-success' : 'account-badge-muted' }}">
            {{ $paymentCurrent ? '✓ ' : '' }}{{ \App\Services\PublicationPaymentStatus::LABELS[$paymentState['status']] }}
        </span>
        @if ($paymentState['expires_at'])
            <p>{{ $paymentState['status'] === 'expired' ? 'Venció el' : 'Vencimiento:' }} <strong>{{ $paymentState['expires_at']->format('d/m/Y') }}</strong></p>
        @endif
        @if ($paymentState['status'] === 'near_expiry')
            <p>Tu período está por terminar. Consultá los datos de transferencia para renovar; la renovación no es automática.</p>
        @elseif ($paymentState['status'] === 'expired')
            <p>Consultá las instrucciones para regularizar tu pago.</p>
        @elseif ($paymentState['status'] === 'unpaid')
            <p>Todavía no hay un pago registrado por la administración.</p>
        @elseif ($paymentState['status'] === 'scheduled')
            <p>El período abonado todavía no comenzó. Consultá sus fechas en el historial.</p>
        @endif
    </section>
    <details class="dashboard-panel payments-instructions" id="instrucciones-pago" @if (! $paymentCurrent) open @endif>
        <summary>Instrucciones de transferencia</summary>
        @if ($paymentInstructions)
            @if (is_numeric($amount))
                <p class="payments-amount"><strong>$ {{ number_format((float) $amount, 2, ',', '.') }} ARS</strong> / mes · {{ $profile->publicationType?->name }}</p>
            @else
                <p>Consultá el importe con la administración antes de transferir.</p>
            @endif
            @if ($paymentInstructions->alias)
                <div class="payments-alias">
                    <div><span>Alias</span><strong id="payment-alias">{{ $paymentInstructions->alias }}</strong></div>
                    <button type="button" class="account-secondary-action" id="copy-payment-alias" aria-describedby="alias-copy-feedback" hidden>Copiar alias</button>
                </div>
                <p id="alias-copy-feedback" role="status" aria-live="polite"></p>
            @endif
            @if ($paymentInstructions->transfer_details)
                <p class="payments-transfer-details">{{ $paymentInstructions->transfer_details }}</p>
            @endif
            <p>Después de transferir, enviá el comprobante para que la administración registre tu pago.</p>
            @if ($paymentInstructions->receipt_whatsapp)
                <a class="account-primary-action" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $paymentInstructions->receipt_whatsapp) }}" target="_blank" rel="noopener">Enviar comprobante por WhatsApp</a>
            @endif
        @else
            <p>Las instrucciones de pago todavía no están disponibles.</p>
        @endif
    </details>
    <section class="dashboard-panel payments-history" aria-labelledby="payments-history-title">
        <h2 id="payments-history-title">Historial de pagos</h2>
        @if ($profile->payments->isEmpty())
            <p>Todavía no tenés pagos registrados.</p>
        @else
            <table class="payments-responsive-table">
                <thead><tr><th scope="col">Fecha</th><th scope="col">Importe</th><th scope="col">Período</th><th scope="col">Estado</th></tr></thead>
                <tbody>
                    @foreach ($profile->payments->sortByDesc('starts_at') as $payment)
                        @php
                            $status = $payment->starts_at->gt(today()) ? 'Programado' : ($payment->expires_at->lt(today()) ? 'Vencido' : ($payment->expires_at->lte(today()->addDays($warningDays)) ? 'Próximo a vencer' : 'Vigente'));
                        @endphp
                        <tr>
                            <td data-label="Fecha">{{ $payment->paid_at->format('d/m/Y') }}</td>
                            <td data-label="Importe">$ {{ number_format((float) $payment->amount, 2, ',', '.') }} ARS</td>
                            <td data-label="Período">{{ $payment->starts_at->format('d/m/Y') }} — {{ $payment->expires_at->format('d/m/Y') }}</td>
                            <td data-label="Estado">{{ $status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
<script>
(() => {
    const button = document.getElementById('copy-payment-alias');
    if (!button) return;
    button.hidden = false;
    button.addEventListener('click', async () => {
        const alias = document.getElementById('payment-alias');
        const feedback = document.getElementById('alias-copy-feedback');
        try {
            await navigator.clipboard.writeText(alias.textContent.trim());
            feedback.textContent = 'Alias copiado.';
        } catch {
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(alias);
            selection.removeAllRanges();
            selection.addRange(range);
            feedback.textContent = 'No se pudo copiar automáticamente. El alias quedó seleccionado para copiarlo manualmente.';
        }
    });
})();
</script>
