<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AuthListener class.
 *
 * Handles logging of authentication events in WordPress.
 *
 * @package Lolly
 */
class AuthListener {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_login action.
     *
     * @param string   $_user_login Username of the user logging in.
     * @param \WP_User $user        WP_User object of the logged in user.
     */
    public function on_login( string $_user_login, \WP_User $user ): void {
        $this->logger->info(
            'User logged in.',
            [
                'target_user' => [ 'id' => $user->ID ],
            ]
        );
    }

    /**
     * Handle the wp_logout action.
     *
     * @param int $user_id ID of the user that was logged out.
     */
    public function on_logout( int $user_id ): void {
        $this->logger->info(
            'User logged out.',
            [
                'target_user' => [ 'id' => $user_id ],
            ]
        );
    }

    /**
     * Handle the wp_login_failed action.
     *
     * @param string   $username Username used in the failed login attempt.
     * @param WP_Error $error    WP_Error object with the authentication failure details.
     */
    public function on_login_failed( string $username, WP_Error $error ): void {
        $this->logger->warning(
            'Login failed.',
            [
                'username' => $username,
                'wp_error' => $error,
            ]
        );
    }

    /**
     * Handle the after_password_reset action.
     *
     * @param \WP_User $user The user whose password was reset.
     */
    public function on_password_reset( \WP_User $user ): void {
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
     * @param \WP_User             $_old_user_data Object containing user's data prior to update.
     * @param array<string, mixed> $userdata      The raw array of data passed to wp_insert_user().
     */
    public function on_profile_update( int $user_id, \WP_User $_old_user_data, array $userdata ): void {
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

    /**
     * Handle the wp_create_application_password action.
     *
     * @param int                  $user_id  The user ID.
     * @param array<string, mixed> $new_item The details about the created password.
     * @param string               $new_password The unhashed generated application password.
     */
    public function on_app_password_created( int $user_id, array $new_item, string $new_password ): void {
        $this->logger->info(
            'Application password created.',
            [
                'target_user' => [ 'id' => $user_id ],
                'app_name'    => $new_item['name'] ?? '',
                'app_uuid'    => $new_item['uuid'] ?? '',
            ]
        );
    }

    /**
     * Handle the wp_delete_application_password action.
     *
     * @param int                  $user_id The user ID.
     * @param array<string, mixed> $item    The data about the application password being deleted.
     */
    public function on_app_password_deleted( int $user_id, array $item ): void {
        $this->logger->info(
            'Application password deleted.',
            [
                'target_user' => [ 'id' => $user_id ],
                'app_name'    => $item['name'] ?? '',
                'app_uuid'    => $item['uuid'] ?? '',
            ]
        );
    }
}
