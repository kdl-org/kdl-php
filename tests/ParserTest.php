<?php

declare(strict_types=1);

namespace Shieldo\Kdl\Tests;

use PHPUnit\Framework\TestCase;
use Shieldo\Kdl\Parser;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    /**
     * @dataProvider kdlNodes
     * @param array  $expectedNodeShape
     * @param string $kdl
     */
    public function testNodes(array $expectedNodeShape, string $kdl): void
    {
        $result = $this->parser->parse($kdl);
        self::assertEquals($expectedNodeShape, $result->jsonSerialize());
    }

    public function kdlNodes(): array
    {
        $suite = json_decode(file_get_contents(__DIR__ . '/suite.json') ?: '', true);
        $testData = [];
        foreach ($suite as $suiteName => $suiteData) {
            $testData[] = [$this->completeSuiteNodes($suiteData), $this->getKdlFile($suiteName)];
        }

        return $testData;
    }

    private function getKdlFile(string $name): string
    {
        return file_get_contents(sprintf('%s/kdl/%s.kdl', __DIR__, $name)) ?: '';
    }

    private function completeSuiteNodes(array $nodeData): array
    {
        return array_map(
            static function ($node) {
                return array_merge(
                    [
                        'values' => [],
                        'properties' => (object)[],
                        'children' => [],
                    ],
                    $node
                );
            },
            $nodeData
        );
    }
}
