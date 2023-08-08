<?php

declare(strict_types=1);

namespace Kdl\Kdl\Tests;

class XikTest extends \PHPUnit\Framework\TestCase
{
    public function testXik(): void
    {
        $kdl = file_get_contents(__DIR__ . '/kdl/xik.kdl');
        $xml = file_get_contents(__DIR__ . '/kdl/xik-output.xml');

        $doc = \Kdl\Kdl\Xik::parseString($kdl);

        $formatted = new \DOMDocument();
        $formatted->preserveWhiteSpace = false;
        $formatted->formatOutput = true;
        $formatted->loadXML($doc->saveXML());

        self::assertSame($xml, $formatted->saveXML());
    }
}
