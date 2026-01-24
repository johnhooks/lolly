<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnApplicationPasswordCreated class.
 *
 * Log when an application password is created for a user.
 *
 * @package Lolly
 */
class LogOnApplicationPasswordCreated {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_create_application_password action.
     *
     * @param int                  $user_id       User ID.
     * @param array<string, mixed> $item          Application password item.
     * @param string               $_new_password The unhashed generated application password (not logged).
     */
    public function handle( int $user_id, array $item, string $_new_password ): void {
        $this->logger->info(
            'Application password created.',
            [
                'target_user' => [ 'id' => $user_id ],
                'app_name'    => $item['name'] ?? null,
                'app_uuid'    => $item['uuid'] ?? null,
            ]
        );
    }
}
