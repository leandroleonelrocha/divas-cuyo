<?php

namespace Tests\Support;

use App\Services\LegalAccess;

trait AcceptsLegalDisclaimer
{
    protected function setUpAcceptsLegalDisclaimer(): void
    {
        // Exercise the real middleware as a visitor with a valid acceptance.
        $this->withCookie(config('legal.cookie'), app(LegalAccess::class)->cookie('accepted')->getValue());
    }
}
