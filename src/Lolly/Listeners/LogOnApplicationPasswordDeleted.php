<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnApplicationPasswordDeleted class.
 *
 * Log when an application password is deleted for a user.
 *
 * @package Lolly
 */
class LogOnApplicationPasswordDeleted {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_delete_application_password action.
     *
     * @param int                  $user_id User ID.
     * @param array<string, mixed> $item    The application password item that was deleted.
     */
    public function handle( int $user_id, array $item ): void {
        $this->logger->info(
            'Application password deleted.',
            [
                'target_user' => [ 'id' => $user_id ],
                'app_name'    => $item['name'] ?? null,
                'app_uuid'    => $item['uuid'] ?? null,
            ]
        );
    }
}
