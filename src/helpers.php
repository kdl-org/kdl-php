<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

function isCharBetween(int $from, int $to): callable
{
    return static fn(string $x): bool => $from <= mb_ord($x) && mb_ord($x) <= $to;
}
