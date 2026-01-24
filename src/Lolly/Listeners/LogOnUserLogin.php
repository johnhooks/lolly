<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserLogin class.
 *
 * Log when a user logs into WordPress.
 *
 * @package Lolly
 */
class LogOnUserLogin {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_login action.
     *
     * @param string   $_user_login Username of the user logging in.
     * @param \WP_User $user        WP_User object of the logged in user.
     */
    public function handle( string $_user_login, \WP_User $user ): void {
        $this->logger->info(
            'User logged in.',
            [
                'target_user' => [ 'id' => $user->ID ],
            ]
        );
    }
}
