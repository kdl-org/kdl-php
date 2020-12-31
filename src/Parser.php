<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

use Verraes\Parsica\Parser as ParsicaParser;

class Parser
{
    public function parse(string $kdl): Document
    {
        $result = self::parser()->tryString($kdl);
//        var_dump($result->remainder());
        return $result->output();
    }

    private static function parser(): ParsicaParser
    {
        return nodes();
    }
}
