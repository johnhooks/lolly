<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UserListener class.
 *
 * Handles logging of user lifecycle events in WordPress.
 *
 * @package Lolly
 */
class UserListener {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the user_register action.
     *
     * @param int                  $user_id   User ID.
     * @param array<string, mixed> $_userdata The raw array of data passed to wp_insert_user().
     */
    public function on_created( int $user_id, array $_userdata ): void {
        $user  = get_userdata( $user_id );
        $roles = $user instanceof \WP_User ? $user->roles : [];

        $this->logger->info(
            'User created.',
            [
                'target_user' => [ 'id' => $user_id ],
                'roles'       => $roles,
            ]
        );
    }

    /**
     * Handle the delete_user action.
     *
     * @param int      $user_id  ID of the user to delete.
     * @param int|null $reassign ID of the user to reassign posts and links to.
     * @param \WP_User $user     WP_User object of the user to delete.
     */
    public function on_deleted( int $user_id, ?int $reassign, \WP_User $user ): void {
        $context = [
            'target_user' => [ 'id' => $user_id ],
            'roles'       => $user->roles,
        ];

        if ( $reassign !== null ) {
            $context['reassign_to'] = [ 'id' => $reassign ];
        }

        $this->logger->info( 'User deleted.', $context );
    }

    /**
     * Handle the add_user_role action.
     *
     * @param int    $user_id The user ID.
     * @param string $role    The added role.
     */
    public function on_role_added( int $user_id, string $role ): void {
        $this->logger->info(
            'User role added.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
            ]
        );
    }

    /**
     * Handle the set_user_role action.
     *
     * @param int      $user_id   The user ID.
     * @param string   $role      The new role.
     * @param string[] $old_roles An array of the user's previous roles.
     */
    public function on_role_changed( int $user_id, string $role, array $old_roles ): void {
        $this->logger->info(
            'User role changed.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
                'old_roles'   => $old_roles,
            ]
        );
    }

    /**
     * Handle the remove_user_role action.
     *
     * @param int    $user_id The user ID.
     * @param string $role    The removed role.
     */
    public function on_role_removed( int $user_id, string $role ): void {
        $this->logger->info(
            'User role removed.',
            [
                'target_user' => [ 'id' => $user_id ],
                'role'        => $role,
            ]
        );
    }
}
