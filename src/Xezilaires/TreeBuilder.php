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

namespace Xezilaires;

use Tree\Node\Node;
use Tree\Node\NodeInterface;
use Xezilaires\Exception\NestableIteratorException;

final class TreeBuilder
{
    /**
     * @var NodeInterface
     */
    private $root;

    /**
     * @var array<float|int|string, NodeInterface>
     */
    private $ids = [];

    /**
     * @var NodeInterface[][]
     */
    private $paths = [];

    /**
     * @throws NestableIteratorException
     */
    public function __construct(Iterator $iterator, string $rootNode = 'Root')
    {
        $this->root = new Node($rootNode);

        /** @var Nestable $node */
        foreach ($iterator as $node) {
            $treeNode = new Node($node);
            if ($node->hasParent()) {
                /** @var float|int|string $parentIdentifier */
                $parentIdentifier = $node->getParentIdentifier();

                $this->fetch($parentIdentifier)->addChild($treeNode);
            } else {
                $this->root->addChild($treeNode);
            }
            $this->ids[$node->getIdentifier()] = $treeNode;
        }
    }

    public function getRoot(): NodeInterface
    {
        return $this->root;
    }

    /**
     * @throws NestableIteratorException
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
     * @throws NestableIteratorException
     */
    public function getPath(?string $id): string
    {
        return '/'.ltrim(
            implode(
                '/',
                array_map(
                    static function (NodeInterface $node): string {
                        return str_replace('/', '-', (string) $node->getValue());
                    },
                    $this->getAncestors($id)
                )
            ),
            '/'
        );
    }

    /**
     * @param float|int|string $id
     *
     * @throws NestableIteratorException
     */
    private function fetch($id): NodeInterface
    {
        if (false === isset($this->ids[$id])) {
            throw NestableIteratorException::noSuchNode($id);
        }

        return $this->ids[$id];
    }
}
