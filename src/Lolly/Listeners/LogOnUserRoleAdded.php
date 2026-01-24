<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserRoleAdded class.
 *
 * Log when a role is added to a user in WordPress.
 *
 * @package Lolly
 */
class LogOnUserRoleAdded {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the add_user_role action.
     *
     * Fires after a role has been added to a user.
     *
     * @param int    $user_id The user ID.
     * @param string $role    The added role.
     */
    public function handle( int $user_id, string $role ): void {
        $this->logger->info(
            'User role added.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
            ]
        );
    }
}
