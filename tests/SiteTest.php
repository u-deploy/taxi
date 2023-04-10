<?php

use UDeploy\Taxi\Site;

class SiteTest extends BaseApplicationTestCase
{
    public function test_get_property_returns_null()
    {
        $site = new Site(
            __DIR__ . '/fixtures/Scratch',
            [
                'name' => 'test',
            ]
        );

        $this->assertNull($site->get('random'));
    }
}
