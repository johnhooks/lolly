<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserCreated class.
 *
 * Log when a new user is created in WordPress.
 *
 * @package Lolly
 */
class LogOnUserCreated {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the user_register action.
     *
     * @param int                  $user_id   User ID.
     * @param array<string, mixed> $_userdata The raw array of data passed to wp_insert_user().
     */
    public function handle( int $user_id, array $_userdata ): void {
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
}
