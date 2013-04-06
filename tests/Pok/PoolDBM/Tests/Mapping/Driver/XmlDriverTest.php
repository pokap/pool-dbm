<?php

namespace Pok\PoolDBM\Tests\Mapping\Driver;

use Pok\PoolDBM\Mapping\Driver\XmlDriver;

class XmlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }
}
