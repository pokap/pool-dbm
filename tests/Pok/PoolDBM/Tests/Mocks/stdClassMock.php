<?php

namespace Pok\PoolDBM\Tests\Mocks;

class stdClassMock
{
    public function __call($name, $arguments)
    {
        if (isset($this->$name) && is_callable($this->$name)) {
            $func = $this->$name;

            return call_user_func_array($func, $arguments);
        }
    }
}
