<?php

declare(strict_types=1);

/*
 * This file is part of the xezilaires project.
 *
 * (c) Dalibor Karlović <dalibor@flexolabs.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xezilaires\Infrastructure\Utility;

use Tree\Node\Node;
use Tree\Node\NodeInterface;
use Xezilaires\Iterator;
use Xezilaires\Nestable;

/**
 * Class TreeBuilder.
 */
class TreeBuilder
{
    /**
     * @var NodeInterface
     */
    private $root;

    /**
     * @var NodeInterface[]
     */
    private $ids = [];

    /**
     * @var NodeInterface[][]
     */
    private $paths = [];

    /**
     * @param Iterator $iterator
     */
    public function __construct(Iterator $iterator)
    {
        if (false === $iterator->areItemsNestable()) {
            throw new \RuntimeException('Iterator items must be nestable');
        }

        $this->root = new Node('Root');

        /** @var Nestable $node */
        foreach ($iterator as $node) {
            $treeNode = new Node($node);
            if ($node->hasParent()) {
                $this->fetch($node->getParentIdentifier())->addChild($treeNode);
            } else {
                $this->root->addChild($treeNode);
            }
            $this->ids[$node->getIdentifier()] = $treeNode;
        }
    }

    /**
     * @return NodeInterface
     */
    public function getRoot(): NodeInterface
    {
        return $this->root;
    }

    /**
     * @param null|string $id
     *
     * @return NodeInterface[]
     */
    public function getAncestors(?string $id): array
    {
        if (null === $id) {
            return [$this->root, new Node('Uncategorized')];
        }

        if (false === isset($this->paths[$id])) {
            $this->paths[$id] = $this->fetch($id)->getAncestorsAndSelf();
        }

        return $this->paths[$id];
    }

    /**
     * @param null|string $id
     *
     * @return string
     */
    public function getPath(?string $id): string
    {
        return '/'.ltrim(
            implode(
                '/',
                array_map(
                    function (NodeInterface $node): string {
                        return str_replace('/', '-', (string) $node->getValue());
                    },
                    $this->getAncestors($id)
                )
            ),
            '/'
        );
    }

    /**
     * @param string|int|float $id
     *
     * @return NodeInterface
     */
    private function fetch($id): NodeInterface
    {
        if (false === isset($this->ids[$id])) {
            throw new \InvalidArgumentException(sprintf('No such node: "%1$s"', $id));
        }

        return $this->ids[$id];
    }
}