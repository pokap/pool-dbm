<?php

namespace Pok\PoolDBM\Tests\Mapping;

use Pok\PoolDBM\Mapping\Driver\XmlDriver;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . '/Driver/fixtures/xml');
    }
}
