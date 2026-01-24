<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserRoleChanged class.
 *
 * Log when a user's role is changed in WordPress.
 *
 * @package Lolly
 */
class LogOnUserRoleChanged {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the set_user_role action.
     *
     * Fires after the user's role has changed.
     *
     * @param int      $user_id   The user ID.
     * @param string   $role      The new role.
     * @param string[] $old_roles An array of the user's previous roles.
     */
    public function handle( int $user_id, string $role, array $old_roles ): void {
        $this->logger->info(
            'User role changed.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
                'old_roles'   => $old_roles,
            ]
        );
    }
}
