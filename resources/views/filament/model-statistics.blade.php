@php
    $stats = app(\App\Services\ProfileStatistics::class)->summary($this->getRecord(), $this->statisticsPeriod);
@endphp
<div>
    <label for="admin-stats-period">Período</label>
    <select id="admin-stats-period" wire:model.live="statisticsPeriod" style="min-height:44px;margin-bottom:16px">
        <option value="7">Últimos 7 días</option>
        <option value="30">Últimos 30 días</option>
        <option value="month">Este mes</option>
    </select>
    @include('statistics.summary')
</div>
