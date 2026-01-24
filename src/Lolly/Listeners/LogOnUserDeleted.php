<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LogOnUserDeleted class.
 *
 * Log when a user is deleted from WordPress.
 *
 * @package Lolly
 */
class LogOnUserDeleted {

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the delete_user action.
     *
     * Fires immediately before a user is deleted from the database.
     *
     * @param int      $user_id  ID of the user to delete.
     * @param int|null $reassign ID of the user to reassign posts and links to.
     * @param \WP_User $user     WP_User object of the user to delete.
     */
    public function handle( int $user_id, ?int $reassign, \WP_User $user ): void {
        $context = [
            'target_user' => [ 'id' => $user_id ],
            'roles'       => $user->roles,
        ];

        if ( $reassign !== null ) {
            $context['reassign_to'] = [ 'id' => $reassign ];
        }

        $this->logger->info( 'User deleted.', $context );
    }
}
