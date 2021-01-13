<?php

declare(strict_types=1);

namespace Kdl\Kdl\Tests;

use PHPUnit\Framework\TestCase;
use Kdl\Kdl\Document;
use Verraes\Parsica\Parser;
use Verraes\Parsica\ParserHasFailed;

use function Kdl\Kdl\{boolean,
    escline,
    multiLineComment,
    newline,
    nodes,
    nodeSpace,
    number,
    singleLineComment,
    string_,
    ws};

class GrammarTest extends TestCase
{
    private const ERROR = 'ERROR';

    /**
     * @dataProvider strings
     * @param string $input
     * @param string $output
     */
    public function testString(string $input, string $output): void
    {
        $this->makeAssertionsForParser($input, $output, string_());
    }

    public function strings(): array
    {
        return [
            //escaped strings
            ["\"\"", ""],
            ["\"hello\"", "hello"],
            ["\"hello\nworld\"", "hello\nworld"],
            ["\"\u{10FFF}\"", "\u{10FFF}"],
            [<<<'EOT'
"\"\\\/\b\f\n\r\t"
EOT
            ,"\"\\/\u{08}\u{0C}\n\r\t"],
            ['"\\u{10}"', '\u{10}'],
            ['"\\i"', self::ERROR],
//            ['"\\u{c0ffee}"', self::ERROR], // src/parser.rs:791 from kdl-rs references this...
            //raw strings
            ['r"foo"', 'foo'],
            ["r\"foo\nbar\"", "foo\nbar"],
            ['r#"foo"#', 'foo'],
            ['r##"foo"##', 'foo'],
            ['r"\nfoo\r"', '\nfoo\r'],
            ['r##"foo"#', self::ERROR],
        ];
    }

    /**
     * @dataProvider numbers
     */
    public function testNumber(string $input, $output): void
    {
        $this->makeAssertionsForParser($input, $output, number());
    }

    public function numbers(): array
    {
        return [
            //floats
            ['1.0', 1.0],
            ['0.0', 0.0],
            ['-1.0', -1.0],
            ['+1.0', 1.0],
            ['1.0e10', 1.0e10],
            ['1.0e-10', 1.0e-10],
            ['-1.0e-10', -1.0e-10],
            ['123_456_789.0', 123456789.0],
            ['123_456_789.0_', 123456789.0],
            ['?1.0', self::ERROR],
            ['_1.0', self::ERROR],
            ['1._0', self::ERROR],
            ['1.', self::ERROR],
            ['.0', self::ERROR],
            //integers
            ['0', 0],
            ['0123456789', 123456789],
            ['0123_456_789', 123456789],
            ['0123_456_789_', 123456789],
            ['+0123456789', 123456789],
            ['-0123456789', -123456789],
            ['?0123456789', self::ERROR],
            ['_0123456789', self::ERROR],
            ['a', self::ERROR],
            ['--', self::ERROR],
            //hexadecimal
            ['0x0123456789abcdef', 0x0123456789abcdef],
            ['0x01234567_89abcdef', 0x0123456789abcdef],
            ['0x01234567_89abcdef_', 0x0123456789abcdef],
            ['0x_123', self::ERROR],
            ['0xg', self::ERROR],
            ['0xx', self::ERROR],
            //octal
            ['0o01234567', 001234567],
            ['0o0123_4567', 001234567],
            ['0o01234567_', 001234567],
            //binary
            ['0b0101', 0b0101],
            ['0b01_10', 0b110],
            ['0b01___10', 0b110],
            ['0b0110_', 0b110],
            ['0b_0110', self::ERROR],
            ['0b20', self::ERROR],
            ['0bb', self::ERROR],
        ];
    }

    /**
     * @dataProvider booleans
     */
    public function testBooleans(string $input, $output): void
    {
        $this->makeAssertionsForParser($input, $output, boolean());
    }

    public function booleans(): array
    {
        return [
            ['true', true],
            ['false', false],
            ['blah', self::ERROR],
        ];
    }

    /**
     * @dataProvider nodeSpaces
     * @param string $input
     * @param        $output
     */
    public function testNodeSpaces(string $input, $output): void
    {
        $this->makeAssertionsForParser($input, $output, nodeSpace());
    }

    public function nodeSpaces(): array
    {
        return [
            [' ', ''],
            ["\t ", ''],
            ["\t \\ // hello\n ", ''],
            ['blah', self::ERROR]
        ];
    }

    /**
     * @dataProvider singleLineComments
     * @param string $input
     * @param string $remainder
     */
    public function testSingleLineComments(string $input, string $remainder): void
    {
        self::assertEquals($remainder, (string) singleLineComment()->tryString($input)->remainder());
    }

    public function singleLineComments(): array
    {
        return [
            ['//hello', ''],
            ["// \thello", ''],
            ["//hello\n", ''],
            ["//hello\r\n", ''],
            ["//hello\n\r", "\r"],
            ["//hello\rworld", 'world'],
            ["//hello\nworld\r\n", "world\r\n"],
        ];
    }

    /**
     * @dataProvider multiLineComments
     * @param string $input
     * @param string $remainder
     */
    public function testMultiLineComments(string $input, string $remainder): void
    {
        self::assertEquals($remainder, (string) multiLineComment()->tryString($input)->remainder());
    }

    public function multiLineComments(): array
    {
        return [
            ["/*hello*/", ''],
            ["/*hello*/\n", "\n"],
            ["/*\nhello\r\n*/", ''],
            ["/*\nhello** /\n*/", ''],
            ["/**\nhello** /\n*/", ''],
            ["/*hello*/world", 'world'],
        ];
    }

    /**
     * @dataProvider esclines
     * @param string $input
     * @param string $remainder
     */
    public function testEsclines(string $input, string $remainder): void
    {
        self::assertEquals($remainder, (string) escline()->tryString($input)->remainder());
    }

    public function esclines(): array
    {
        return [
            ["\\\nfoo", 'foo'],
            ["\\\n  foo", '  foo'],
            ["\\  \t \nfoo", 'foo'],
            ["\\ // test \nfoo", 'foo'],
            ["\\ // test \n  foo", '  foo'],
        ];
    }

    /**
     * @dataProvider ws
     * @param string $input
     * @param string $remainder
     */
    public function testWs(string $input, string $remainder): void
    {
        $this->makeRemainderAssertionsForParser($input, $remainder, ws());
    }

    public function ws(): array
    {
        return [
            [' ', ''],
            ["\t", ''],
            ["/* \nfoo\r\n */ etc", ' etc'],
            ["hi", self::ERROR],
        ];
    }

    /**
     * @dataProvider newlines
     * @param string $input
     * @param string $remainder
     */
    public function testNewlines(string $input, string $remainder): void
    {
        $this->makeRemainderAssertionsForParser($input, $remainder, newline());
    }

    public function newlines(): array
    {
        return [
            ["\n", ''],
            ["\r", ''],
            ["\r\n", ''],
            ["\n\n", "\n"],
            ['blah', self::ERROR],
        ];
    }

    /**
     * @dataProvider nodesWithSlashdashComments
     * @param string $input
     * @param string $remainder
     */
    public function testNodesWithSlashdashComments(string $input, string $remainder): void
    {
        $this->makeRemainderAssertionsForParser($input, $remainder, nodes());
    }

    public function nodesWithSlashdashComments(): array
    {
        return [
            ["/-node", ''],
            ["/- node", ''],
            ["/- node\n", ''],
            ["/-node 1 2 3", ''],
            ["/-node key=false", ''],
            ["/-node{\nnode\n}", ''],
            ["/-node 1 2 3 key=\"value\" \\\n{\nnode\n}", ''],
        ];
    }

    /**
     * @dataProvider argSlashdashComments
     * @param string $input
     * @param array  $values
     */
    public function testArgSlashdashComments(string $input, array $values): void
    {
        /** @var Document $parsed */
        $parsed = nodes()->thenEof()->tryString($input)->output();
        self::assertInstanceOf(Document::class, $parsed);
        self::assertEquals($values, $parsed->getNodes()[0]->getValues());
    }

    public function argSlashdashComments(): array
    {
        return [
            ["node /-1", []],
            ["node /-1 2", [2]],
            ["node 1 /- 2 3", [1, 3]],
            ["node /--1", []],
            ["node /- -1", []],
            ["node \\\n/- -1", []],
        ];
    }

    /**
     * @dataProvider propSlashdashComments
     * @param string $input
     * @param array  $properties
     */
    public function testPropSlashdashComments(string $input, array $properties): void
    {
        /** @var Document $parsed */
        $parsed = nodes()->thenEof()->tryString($input)->output();
        self::assertInstanceOf(Document::class, $parsed);
        self::assertEquals($properties, $parsed->getNodes()[0]->getProperties());
    }

    public function propSlashdashComments(): array
    {
        return [
            ["node /-key=1", []],
            ["node /- key=1", []],
            ["node key=1 /-key2=2", ['key' => 1]],
        ];
    }

    /**
     * @dataProvider childrenSlashDashComments
     * @param string $input
     */
    public function testChildrenSlashDashComments(string $input): void
    {
        /** @var Document $parsed */
        $parsed = nodes()->thenEof()->tryString($input)->output();
        self::assertInstanceOf(Document::class, $parsed);
        self::assertCount(0, $parsed->getNodes()[0]->getChildren());
    }

    public function childrenSlashDashComments(): array
    {
        return [
            ["node /-{}"],
            ["node /- {}"],
            ["node /-{\nnode2\n}"],
        ];
    }

    private function makeRemainderAssertionsForParser(string $input, string $remainder, Parser $parser): void
    {
        if ($remainder === self::ERROR) {
            $this->assertParseFail($input, $parser);

            return;
        }
        self::assertEquals($remainder, (string) $parser->tryString($input)->remainder());
    }

    private function makeAssertionsForParser(string $input, $output, Parser $parser): void
    {
        if ($output !== self::ERROR) {
            self::assertEquals($output, $parser->thenEof()->tryString($input)->output());
        } else {
            $this->assertParseFail($input, $parser);
        }
    }

    private function assertParseFail(string $input, Parser $parser): void
    {
        try {
            $result = $parser->tryString($input);
        } catch (ParserHasFailed $e) {
            $this->expectNotToPerformAssertions();
            return;
        }
        self::assertGreaterThan(0, strlen((string) $result->remainder()));
    }
}
