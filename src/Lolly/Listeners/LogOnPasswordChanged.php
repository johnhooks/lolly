<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnPasswordChanged class.
 *
 * Log when a user's password is changed in WordPress.
 *
 * @package Lolly
 */
class LogOnPasswordChanged {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the after_password_reset action.
     *
     * Fires after the user's password is reset.
     *
     * @param \WP_User $user The user whose password was reset.
     */
    public function handle_password_reset( \WP_User $user ): void {
        $this->logger->info(
            'User password reset.',
            [
                'target_user' => [ 'id' => $user->ID ],
            ]
        );
    }

    /**
     * Handle the profile_update action for password changes.
     *
     * @param int                  $user_id       User ID.
     * @param \WP_User             $old_user_data Object containing user's data prior to update.
     * @param array<string, mixed> $userdata      The raw array of data passed to wp_insert_user().
     */
    public function handle_profile_update( int $user_id, \WP_User $old_user_data, array $userdata ): void {
        // Only log if password was changed.
        if ( ! isset( $userdata['user_pass'] ) || $userdata['user_pass'] === '' ) {
            return;
        }

        $this->logger->info(
            'User password changed.',
            [
                'target_user' => [ 'id' => $user_id ],
            ]
        );
    }
}
