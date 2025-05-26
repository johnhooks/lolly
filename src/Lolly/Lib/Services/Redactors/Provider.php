<?php

declare(strict_types=1);

namespace Lolly\Lib\Services\Redactors;

use Lolly\Lib\Contracts\Redactors;
use Lolly\Lib\Services\Redactors\HttpMessage\DefaultRedactor;
use Lolly\lucatume\DI52\ServiceProvider;

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
