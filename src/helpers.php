<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

use Verraes\Parsica\Internal\Assert;
use Verraes\Parsica\Internal\EndOfStream;
use Verraes\Parsica\Internal\Fail;
use Verraes\Parsica\Internal\Succeed;
use Verraes\Parsica\Parser;
use Verraes\Parsica\ParseResult;
use Verraes\Parsica\Stream;

function isCharBetween(int $from, int $to): callable
{
    return static fn(string $x): bool => $from <= mb_ord($x) && mb_ord($x) <= $to;
}
