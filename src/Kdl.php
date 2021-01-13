<?php

declare(strict_types=1);

namespace Kdl\Kdl;

final class Kdl
{
    /**
     * @param string $kdl
     * @return mixed
     */
    public static function parse(string $kdl)
    {
        return (new Parser())->parse($kdl);
    }
}
