<?php

declare(strict_types=1);

namespace Kdl\Kdl;

use Parsica\Parsica\Internal\Assert;
use Parsica\Parsica\Internal\EndOfStream;
use Parsica\Parsica\Internal\Fail;
use Parsica\Parsica\Internal\Succeed;
use Parsica\Parsica\Parser;
use Parsica\Parsica\ParseResult;
use Parsica\Parsica\Stream;

function isCharBetween(int $from, int $to): callable
{
    return static fn(string $x): bool => $from <= mb_ord($x) && mb_ord($x) <= $to;
}

function memo(string $name, callable $parserFactory): Parser
{
    $stored = _memo($name);
    if ($stored === null) {
        $stored = $parserFactory();
        _memo($name, $stored);
    }

    return $stored;
}

function _memo(?string $name, ?Parser $parser = null, bool $justClear = false)
{
    static $memo = [];
    if ($justClear) {
        $memo = [];

        return null;
    }
    if ($name === null) {
        throw new \InvalidArgumentException();
    }
    if ($parser === null) {
        return $memo[$name] ?? null;
    }

    $memo[$name] = $parser;

    return null;
}

function clearMemo(): void
{
    _memo(null, null, true);
}
