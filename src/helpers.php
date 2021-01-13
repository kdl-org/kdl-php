<?php

declare(strict_types=1);

namespace Kdl\Kdl;

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
