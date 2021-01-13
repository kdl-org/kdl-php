<?php

namespace Kdl\Kdl\Tests;

use Kdl\Kdl\Document;
use PHPUnit\Framework\TestCase;
use Kdl\Kdl\NodeInterface;

class DocumentTest extends TestCase
{
    public function testIterable(): void
    {
        $nodes = [$this->createMock(NodeInterface::class)];
        $document = new Document($nodes);
        self::assertSame($nodes, iterator_to_array($document));
    }
}
