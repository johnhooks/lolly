<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserLogout class.
 *
 * Log when a user logs out of WordPress.
 *
 * @package Lolly
 */
class LogOnUserLogout {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_logout action.
     *
     * @param int $user_id ID of the user logging out.
     */
    public function handle( int $user_id ): void {
        $this->logger->info(
            'User logged out.',
            [
                'target_user' => [ 'id' => $user_id ],
            ]
        );
    }
}
