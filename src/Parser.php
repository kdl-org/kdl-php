<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

use Verraes\Parsica\Parser as ParsicaParser;

class Parser
{
    public function parse(string $kdl): Document
    {
        return self::parser()->tryString($kdl)->output();
    }

    private static function parser(): ParsicaParser
    {
        return nodes()->thenEof();
    }
}
