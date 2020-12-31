<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

/**
 * Class for an object that represents a KDL document, providing accessors to nodes.
 */
class Document implements \JsonSerializable
{
    /**
     * @var array<NodeInterface>
     */
    private array $nodes;

    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * @return NodeInterface[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function jsonSerialize(): array
    {
        return array_map(
            static function (NodeInterface $node): array {
                return $node->jsonSerialize();
            },
            $this->getNodes()
        );
    }
}
