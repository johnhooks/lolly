<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

class ActivationCest {

    public function test_it_deactivates_activates_correctly( EndToEndTester $I ): void {
        $I->loginAsAdmin();
        $I->amOnPluginsPage();

        $I->seePluginActivated( 'duzuki-logger' );

        $I->deactivatePlugin( 'duzuki-logger' );

        $I->seePluginDeactivated( 'duzuki-logger' );

        $I->activatePlugin( 'duzuki-logger' );

        $I->seePluginActivated( 'duzuki-logger' );
    }
}
