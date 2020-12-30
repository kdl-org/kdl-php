<?php

declare(strict_types=1);

namespace Shieldo\Kdl;

interface NodeInterface extends \JsonSerializable
{
    public function getName(): string;

    public function getValues(): array;

    public function getProperties(): array;

    /**
     * @return array<NodeInterface>
     */
    public function getChildren(): array;
}
