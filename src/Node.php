<?php

namespace Shieldo\Kdl;

class Node implements NodeInterface
{
    private string $name;
    private array $values;
    private array $properties;
    private array $children;

    public function __construct(string $name, array $values, array $properties)
    {
        $this->name = $name;
        $this->values = $values;
        $this->properties = $properties;
        $this->children = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function attachChild(Node $child): void
    {
        $this->children[] = $child;
    }

    public function jsonSerialize(): array
    {
        return self::serialize($this);
    }

    private static function serialize(NodeInterface $node): array
    {
        return [
            'name' => $node->getName(),
            'values' => $node->getValues(),
            'properties' => (object)$node->getProperties(),
            'children' => array_map(
                static function (NodeInterface $child): array {
                    return self::serialize($child);
                },
                $node->getChildren()
            ),
        ];
    }
}
