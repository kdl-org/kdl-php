<?php

declare(strict_types=1);

namespace Shieldo\Kdl\Tests;

use PHPUnit\Framework\TestCase;
use Shieldo\Kdl\Node;
use Shieldo\Kdl\NodeInterface;

class NodeTest extends TestCase
{
    /**
     * @var Node
     */
    private $node;

    protected function setUp(): void
    {
        $this->node = new Node(
            'name',
            ['values'],
            ['properties' => true]
        );
    }

    public function testIsNode(): void
    {
        self::assertInstanceOf(NodeInterface::class, $this->node);
    }

    public function testGetters(): void
    {
        self::assertEquals('name', $this->node->getName());
        self::assertEquals(['values'], $this->node->getValues());
        self::assertEquals(['properties' => true], $this->node->getProperties());
        self::assertEquals([], $this->node->getChildren());
    }

    public function testAttachChild(): void
    {
        $child = new Node(
            'child',
            [],
            []
        );
        $this->node->attachChild($child);
        self::assertSame($child, $this->node->getChildren()[0]);
    }

    public function testSerialize(): void
    {
        $expectedSerialization = [
            'name' => 'name',
            'values' => ['values'],
            'properties' => (object)['properties' => true],
            'children' => [
                [
                    'name' => 'child',
                    'values' => [],
                    'properties' => (object)[],
                    'children' => [],
                ],
            ],
        ];
        $child = new Node(
            'child',
            [],
            []
        );
        $this->node->attachChild($child);
        self::assertEquals($expectedSerialization, $this->node->jsonSerialize());
    }
}
