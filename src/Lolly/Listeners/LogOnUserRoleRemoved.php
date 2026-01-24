<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserRoleRemoved class.
 *
 * Log when a role is removed from a user in WordPress.
 *
 * @package Lolly
 */
class LogOnUserRoleRemoved {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the remove_user_role action.
     *
     * Fires after a role has been removed from a user.
     *
     * @param int    $user_id The user ID.
     * @param string $role    The removed role.
     */
    public function handle( int $user_id, string $role ): void {
        $this->logger->info(
            'User role removed.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
            ]
        );
    }
}
