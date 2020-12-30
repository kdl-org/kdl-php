<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

/**
 * Class for an object that represents a KDL document, providing accessors to nodes.
 */
class Document implements \JsonSerializable
{
    /**
     * @var NodeInterface[]
     */
    private $nodes;

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
            function (NodeInterface $node) {
                return $node->jsonSerialize();
            },
            $this->getNodes()
        );
    }
}
