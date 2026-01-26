<?php

declare(strict_types=1);

namespace Lolly\Rest;

use Lolly\Dropin\DropinManager;
use Lolly\lucatume\DI52\ServiceProvider;
use Lolly\Schema\SchemaLoader;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API service provider.
 *
 * Registers the custom REST API controllers for Lolly.
 */
class Provider extends ServiceProvider {

    /**
     * Classes provided by this provider.
     *
     * @var class-string[]
     */
    public array $provides = [
        SchemaLoader::class,
        SettingsController::class,
        DropinManager::class,
    ];

    /**
     * Register the provider.
     */
    public function register(): void {
        $this->container->singleton( SchemaLoader::class, SchemaLoader::class );
        $this->container->singleton( DropinManager::class, DropinManager::class );
        $this->container->singleton( SettingsController::class, SettingsController::class );

        add_action( 'rest_api_init', $this->container->callback( SettingsController::class, 'register_routes' ) );
    }
}
