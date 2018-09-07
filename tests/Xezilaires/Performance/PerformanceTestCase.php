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

namespace Xezilaires\Test\Performance;

use PhpBench\Benchmark\Metadata\Annotations as Bench;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Xezilaires\Bridge\Symfony\Serializer\ObjectSerializer;
use Xezilaires\Metadata\ColumnReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\Spreadsheet;
use Xezilaires\SpreadsheetIterator;
use Xezilaires\Test\FixtureTrait;
use Xezilaires\Test\Model\Product;

/**
 * @Bench\BeforeMethods({"setUp"})
 */
abstract class PerformanceTestCase
{
    use FixtureTrait;

    /**
     * @var Mapping
     */
    private $mapping;

    public function setUp(): void
    {
        $this->mapping = new Mapping(
            Product::class,
            [
                'name' => new ColumnReference('A'),
                'price' => new ColumnReference('B'),
            ],
            [
                'start' => 2,
            ]
        );
    }

    /**
     * @Bench\Assert(stat="mean", value="10")
     * @Bench\Revs(1000)
     */
    public function benchIteratorConstruction(): void
    {
        $this->getSpreadsheet($this->fixture('massive.xlsx'));
    }

    /**
     * @Bench\Assert(stat="mean", value="10")
     * @Bench\Revs(1000)
     */
    public function benchIteratorIteration(): void
    {
        $iterator = $this->createIterator($this->getSpreadsheet($this->fixture('products.xlsx')), $this->mapping);

        foreach ($iterator as $row) {
        }
    }

    abstract protected function getSpreadsheet(\SplFileObject $file): Spreadsheet;

    private function createIterator(Spreadsheet $spreadsheet, Mapping $mapping): SpreadsheetIterator
    {
        $serializer = new ObjectSerializer(new Serializer([new ObjectNormalizer()]));

        return new SpreadsheetIterator($spreadsheet, $mapping, $serializer);
    }
}
