<?php

namespace App\Services\VideoHosting;

use App\Contracts\VideoHostingProvider;
use InvalidArgumentException;

class VideoHostingManager
{
    public function provider(string $name): VideoHostingProvider
    {
        $class = config('model-videos.providers.'.$name);
        if (! is_string($class) || ! is_a($class, VideoHostingProvider::class, true)) {
            throw new InvalidArgumentException('Proveedor de videos no configurado.');
        }

        return app($class);
    }
}
