<?php

namespace Shieldo\Kdl\Tests;

use Shieldo\Kdl\Document;
use PHPUnit\Framework\TestCase;
use Shieldo\Kdl\NodeInterface;

class DocumentTest extends TestCase
{
    public function testIterable(): void
    {
        $nodes = [$this->createMock(NodeInterface::class)];
        $document = new Document($nodes);
        self::assertSame($nodes, iterator_to_array($document));
    }
}
