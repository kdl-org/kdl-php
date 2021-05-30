<?php

declare(strict_types=1);

namespace Kdl\Kdl\Tests\bench;

use Kdl\Kdl\Parser;

class ParseBench
{
    /**
     * @Revs(100)
     */
    public function benchParseWebsiteKdl(): void
    {
        $kdl = file_get_contents(__DIR__ . '/../kdl/website.kdl');
        (new Parser())->parse($kdl);
    }
}
