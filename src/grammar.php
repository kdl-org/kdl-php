<?php

declare(strict_types=1);

namespace Kdl\Kdl;

use Parsica\Parsica\Parser;

use function Parsica\Parsica\{andPred,
    anySingleBut,
    assemble,
    atLeastOne,
    between,
    binDigitChar,
    char,
    choice,
    collect,
    crlf,
    digitChar,
    either,
    eof,
    hexDigitChar,
    isCharCode,
    keepFirst,
    keepSecond,
    noneOfS,
    notPred,
    octDigitChar,
    oneOfS,
    optional,
    orPred,
    recursive,
    repeat,
    satisfy,
    sepBy,
    sequence,
    string,
    takeWhile,
    zeroOrMore};

function nodes(): Parser
{
    //the three top-level parsers have circular references, so declare them all as recursive
    $nodesParser = recursive();
    $nodeParser = recursive();
    $nodeChildrenParser = recursive();

    $nodesParser->recurse(
        between(
            zeroOrMore(linespace()),
            zeroOrMore(linespace()),
            optional(
                collect($nodeParser, optional($nodesParser))
                    ->map(fn($collection) => array_merge([$collection[0]], $collection[1] ?? []))
            ),
        )
    );
    $nodesParser
        ->label('nodes');

    $nodeParser->recurse(
        collect(
            sequence(
                optional(assemble(string("/-"), zeroOrMore(ws()))),
                identifier(),
            ),
            optional(nodeSpace()),
            sepBy(
                nodeSpace(),
                nodePropsAndArgs(),
            )->map(
                function (?array $propsAndArgs): array {
                    return [
                        'values'     => array_merge(
                            ...array_map(
                                fn(array $struct) => $struct['values'],
                                array_filter(
                                    $propsAndArgs ?? [],
                                    fn(array $struct) => array_key_exists('values', $struct)
                                ),
                            )
                        ),
                        'properties' => array_merge(
                            ...array_map(
                                fn(array $struct) => $struct['properties'],
                                array_filter(
                                    $propsAndArgs ?? [],
                                    fn(array $struct) => array_key_exists('properties', $struct),
                                ),
                            )
                        ),
                    ];
                }
            ),
            keepFirst(
                optional(between(zeroOrMore(nodeSpace()), zeroOrMore(ws()), $nodeChildrenParser)),
                nodeTerminator(),
            ),
        )->map(function (array $collection): NodeInterface {
            [$identifier, , $nodePropsAndArgs, $children] = $collection;
            $node = new Node(
                $identifier,
                $nodePropsAndArgs['values'] ?? [],
                $nodePropsAndArgs['properties'] ?? [],
            );
            if (is_array($children)) {
                foreach ($children as $child) {
                    $node->attachChild($child);
                }
            }

            return $node;
        })
    );
    $nodeParser->label('node');

    $nodeChildrenParser->recurse(
        collect(
            optional(assemble(string("/-"), zeroOrMore(ws())))
                ->map(fn($x) => (bool) $x),
            between(char('{'), char('}'), $nodesParser)
        )->map(function (array $args) {
            [$isSlashDashComment, $nodes] = $args;

            return (!$isSlashDashComment) ? $nodes : null;
        })
    );
    $nodeChildrenParser->label('node-children');

    return $nodesParser
        ->map(fn($nodes) => new Document($nodes ?: []));
}

function nodePropsAndArgs(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => collect(
            optional(assemble(string("/-"), zeroOrMore(ws())))
                ->map(fn($x) => (bool) $x),
            either(prop(), value())
        )
            ->map(function (array $args) {
                [$isSlashDashComment, $propOrArg] = $args;

                return (!$isSlashDashComment) ? $propOrArg : [];
            })
            ->label('node-props-and-args')
    );
}

function nodeSpace(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(assemble(zeroOrMore(ws()), escline(), zeroOrMore(ws())), atLeastOne(ws()))
            ->map(fn($x) => null)
            ->label('node-space')
    );
}

function nodeTerminator(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => choice(singleLineComment(), newline(), char(';'), eof())
            ->label('node-terminator')
    );
}

function identifier(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(string_(), bareIdentifier())
            ->label('identifier')
    );
}

function bareIdentifier(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => assemble(either(digitChar(), identifierChar()), zeroOrMore(identifierChar()))
            ->label('bare-identifier')
    );
}

function identifierChar(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => satisfy(orPred(
            isCharCode(array_merge(
                range(0x21, 0x3A),
                range(0x3F, 0x5A),
                range(0x5E, 0x7A),
                [0x7C],
            )),
            isCharBetween(0x7E, 0xFFFF),
        ))
            ->label('identifier-char')
    );
}

function prop(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => collect(identifier(), char('='), value())
            ->map(fn(array $collected) => ['properties' => [$collected[0] => $collected[2]['values'][0]]])
            ->label('prop')
    );
}

function value(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => choice(string_(), number(), boolean(), string("null")->map(fn() => null))
            ->map(fn($value) => ['values' => [$value]])
            ->label('value')
    );
}

function string_(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(rawString(), escapedString())
            ->label('string')
    );
}

function escapedString(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => between(char('"'), char('"'), zeroOrMore(character()))
            ->label('escaped-string')
    );
}

function character(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(keepSecond(char('\\'), escape()), noneOfS("\\\""))
            ->label('character')
    );
}

function escape(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(
            oneOfS('"\\/bfnrt')
                ->map(function (string $val): string {
                    $escapes = [
                        'b' => "\u{08}",
                        'f' => "\u{0C}",
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                    ];
                    return $escapes[$val] ?? $val;
                }),
            assemble(string("u{"), repeat(6, optional(hexDigitChar())), char('}'))
                ->map(fn($val): string => '\\' . $val),
        )
            ->label('escape')
    );
}

function rawString(): Parser
{
    return memo(
        __FUNCTION__,
        fn () => sequence(
            char('r'),
            keepFirst(zeroOrMore(char('#')), char('"'))
                ->bind(static function (?string $hashes) {
                    $end = '"' . ($hashes ?? '');
                    $endLen = strlen($end);

                    $tail = '';
                    return takeWhile(static function (string $c) use (&$tail, $end, $endLen) {
                        $result = $tail !== $end;
                        $tail = substr($tail . $c, -$endLen);
                        return $result;
                    })
                        ->map(static fn (string $chunk) => substr($chunk, 0, -$endLen));
                })
        )
            ->label('raw-string')
    );
}

function number(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => choice(hex(), octal(), binary(), decimal())
            ->label('number')
    );
}

function decimal(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => collect(
            integer(),
            optional(assemble(char('.'), digitChar(), zeroOrMore(either(digitChar(), char('_'))))),
            optional(exponent()),
        )
            ->map(function (array $collection) {
                [$integer, $fraction, $exponent] = $collection;
                $useInt = $fraction === null && $exponent === null;
                $assembly = implode('', array_filter(
                    [
                        $integer,
                        $fraction ? str_replace('_', '', $fraction) : null,
                        $exponent,
                    ],
                    fn($x): bool => $x !== null,
                ));
                return $useInt ? (int) $assembly : (float) $assembly;
            })
            ->label('decimal')
    );
}

function exponent(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => assemble(either(char('e'), char('E')), integer())
            ->label('exponent')
    );
}

function integer(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => assemble(optional(sign()), digitChar(), zeroOrMore(either(digitChar(), char('_'))))
            ->map(fn($x) => str_replace('_', '', $x))
            ->label('integer')
    );
}

function sign(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(char('+'), char('-'))
            ->label('sign')
    );
}

function hex(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => keepSecond(string("0x"), assemble(hexDigitChar(), zeroOrMore(either(hexDigitChar(), char('_')))))
            ->map(fn(string $x) => hexdec(str_replace('_', '', $x)))
            ->label('hex')
    );
}

function octal(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => keepSecond(string("0o"), assemble(octDigitChar(), zeroOrMore(either(octDigitChar(), char('_')))))
            ->map(fn(string $x) => octdec(str_replace('_', '', $x)))
            ->label('octal')
    );
}

function binary(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => keepSecond(string("0b"), assemble(binDigitChar(), zeroOrMore(either(binDigitChar(), char('_')))))
            ->map(fn(string $x) => bindec(str_replace('_', '', $x)))
            ->label('binary')
    );
}

function boolean(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(string("true"), string("false"))
            ->map(fn(string $val) => $val === "true")
            ->label('boolean')
    );
}

function escline(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => assemble(string('\\'), zeroOrMore(ws()), either(singleLineComment(), newline()))
            ->label('escline')
    );
}

function linespace(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => choice(newline(), ws(), singleLineComment())
            ->label('linespace')
    );
}

function newline(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => either(
            crlf(),
            satisfy(isCharCode([
                0x0D,
                0x0A,
                0x85,
                0x0C,
                0x2028,
                0x2029,
            ]))
        )
    );
}

function notNewline(): Parser
{
    //constructing the complement of newline()
    return memo(
        __FUNCTION__,
        fn() => satisfy(
            notPred(isCharCode([
                0x0D,
                0x0A,
                0x85,
                0x0C,
                0x2028,
                0x2029,
            ])),
        )
    );
}

function ws(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => choice(
            bom(),
            unicodeSpace(),
            multiLineComment()
        )
            ->label('ws')
    );
}

function bom(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => char("\u{FFEF}")
            ->label('bom')
    );
}

function unicodeSpace(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => satisfy(isCharCode([
            0x09,
            0x20,
            0xA0,
            0x1680,
            0x2000,
            0x2001,
            0x2002,
            0x2003,
            0x2004,
            0x2005,
            0x2006,
            0x2007,
            0x2008,
            0x2009,
            0x200A,
            0x202F,
            0x205F,
            0x3000,
        ]))->label('whitespace (unicode-space)')
    );
}

function singleLineComment(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => between(string("//"), either(newline(), eof()), atLeastOne(notNewline()))
            ->label('single-line-comment')
    );
}

function multiLineComment(): Parser
{
    return memo(
        __FUNCTION__,
        fn() => between(
            string("/*"),
            string("*/"),
            zeroOrMore(either(anySingleBut('*'), char('*')->notFollowedBy(char('/')))),
        )
            ->label('multi-line-comment')
    );
}
