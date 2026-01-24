<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnLoginFailed class.
 *
 * Log when a login attempt fails in WordPress.
 *
 * @package Lolly
 */
class LogOnLoginFailed {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the wp_login_failed action.
     *
     * @param string   $username Username used in the failed login attempt.
     * @param WP_Error $error    WP_Error object with the authentication failure details.
     */
    public function handle( string $username, WP_Error $error ): void {
        $this->logger->warning(
            'Login failed.',
            [
                'username' => $username,
                'wp_error' => $error,
            ]
        );
    }
}
