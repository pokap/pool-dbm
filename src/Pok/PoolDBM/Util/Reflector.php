<?php

namespace Pok\PoolDBM\Util;

class Reflector
{
    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return array
     */
    public static function parameters(array $parameters)
    {
        $args = array();
        foreach ($parameters as $param) {
            $arg = '';

            if ($param->isPassedByReference()) {
                $arg .= '&';
            }

            $arg .= '$' . $param->getName();

            if ($param->isOptional()) {
                $arg .= ' = ';

                switch (true) {
                    case null === $param->getDefaultValue():
                        $arg .= 'null';
                        break;
                    case is_array($param->getDefaultValue()):
                        $arg .= 'array()';
                        break;
                    case is_bool($param->getDefaultValue()):
                        $arg .= $param->getDefaultValue() ? 'true' : 'false';
                        break;
                    case is_int($param->getDefaultValue()):
                        $arg .= $param->getDefaultValue();
                        break;
                    default:
                        $arg .= '\'' . str_replace('\'', '\\\'', $param->getDefaultValue()) . '\'';
                        break;
                }
            }

            $args[] = $arg;
        }

        return $args;
    }
}
