<?php

declare(strict_types=1);

namespace Dozuki\Lib\Services\Redactors;

use Dozuki\Lib\Contracts\Redactors;
use Dozuki\Lib\Services\Redactors\HttpMessage\DefaultRedactor;
use Dozuki\lucatume\DI52\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Provider extends ServiceProvider {
    /**
     * @var class-string[]
     */
    public array $provides = [
        Redactors\HttpMessage::class,
    ];

    public function register() {
        $this->container->bind( Redactors\HttpMessage::class, DefaultRedactor::class );
    }
}
