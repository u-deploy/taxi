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

    public function test_site_can_read_config()
    {
        $site = new Site(
            __DIR__ . '/fixtures/Parked/Sites/Config/config-site',
            [
                'name' => 'laravel-config',
            ]
        );

        $this->assertEquals(
            'taxt-config-test',
            $site->config()->get('app.name','default')
        );

        $this->assertEquals(
            'testing',
            $site->config()->get('app.version','default')
        );


    }
}
