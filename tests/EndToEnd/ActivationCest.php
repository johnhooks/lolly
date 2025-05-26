<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

class ActivationCest {

    public function test_it_deactivates_activates_correctly( EndToEndTester $I ): void {
        $I->loginAsAdmin();
        $I->amOnPluginsPage();

        $I->seePluginActivated( 'lolly-log' );

        $I->deactivatePlugin( 'lolly-log' );

        $I->seePluginDeactivated( 'lolly-log' );

        $I->activatePlugin( 'lolly-log' );

        $I->seePluginActivated( 'lolly-log' );
    }
}
