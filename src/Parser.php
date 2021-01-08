<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

use Verraes\Parsica\ParserHasFailed;

class Parser
{
    /**
     * @param string $kdl
     * @return Document
     * @throws ParseException when parsing fails
     * @psalm-suppress InternalMethod
     */
    public function parse(string $kdl): Document
    {
        try {
            return nodes()->thenEof()->tryString($kdl)->output();
        } catch (ParserHasFailed $e) {
            throw new ParseException(
                $e->parseResult()->errorMessage(),
                0,
                $e,
            );
        }
    }
}
