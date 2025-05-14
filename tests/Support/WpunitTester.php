<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class WpunitTester extends \Codeception\Actor {

    use _generated\WpunitTesterActions;

    /**
     * Create a test administrator user and log them in
     *
     * @return \WP_User The created user
     */
    public function login_as_admin() {
        $user_id = $this->factory()->user->create(
            [
                'role' => 'administrator',
            ]
        );
        $user    = get_user_by( 'id', $user_id );

        wp_set_current_user( $user_id );

        return $user;
    }

    /**
     * Create a test user with a specific role and log them in
     *
     * @param string $role The user role
     * @return \WP_User The created user
     */
    public function login_as_role( string $role = 'subscriber' ) {
        $user_id = $this->factory()->user->create(
            [
                'role' => $role,
            ]
        );
        $user    = get_user_by( 'id', $user_id );

        wp_set_current_user( $user_id );

        return $user;
    }

    /**
     * Log out the current user
     */
    public function logout() {
        wp_set_current_user( 0 );
    }
}
