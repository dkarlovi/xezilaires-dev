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

namespace Xezilaires\Test;

use Nyholm\NSA;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\TestCase;
use Xezilaires\Denormalizer;
use Xezilaires\Exception\DenormalizerException;
use Xezilaires\Exception\MappingException;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Iterator;
use Xezilaires\Metadata\HeaderReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\Spreadsheet;
use Xezilaires\SpreadsheetIterator;

/**
 * @covers \Xezilaires\SpreadsheetIterator
 * @covers \Xezilaires\Metadata\Mapping
 *
 * @uses \Xezilaires\Metadata\HeaderReference
 *
 * @internal
 *
 * @small
 */
final class SpreadsheetIteratorTest extends TestCase
{
    public function testCanPerformValidCorrectly(): void
    {
        $mapping = new Mapping(\stdClass::class, [
            'name' => new HeaderReference('Name'),
        ], ['header' => 1]);

        $iterator = new SpreadsheetIterator(
            $this->getMockBuilder(Spreadsheet::class)->getMock(),
            $mapping,
            $this->getMockBuilder(Denormalizer::class)->getMock()
        );
        NSA::setProperty($iterator, 'iterator', $this->mockIterator([
            'valid' => ['count' => 1, 'params' => null, 'return' => true],
            'current' => ['count' => 0, 'params' => null],
            'prev' => ['count' => 0, 'params' => null],
            'next' => ['count' => 0, 'params' => null],
        ]));
        $iterator->valid();
    }

    public function testCanPerformNextCorrectly(): void
    {
        $mapping = new Mapping(\stdClass::class, [
            'name' => new HeaderReference('Name'),
        ], ['header' => 1]);

        $iterator = new SpreadsheetIterator(
            $this->getMockBuilder(Spreadsheet::class)->getMock(),
            $mapping,
            $this->getMockBuilder(Denormalizer::class)->getMock()
        );
        NSA::setProperty($iterator, 'iterator', $this->mockIterator([
            'valid' => ['count' => 0, 'params' => null, 'return' => true],
            'current' => ['count' => 0, 'params' => null],
            'prev' => ['count' => 0, 'params' => null],
            'next' => ['count' => 1, 'params' => null],
        ]));
        $iterator->next();
    }

    /**
     * @throws SpreadsheetException
     * @throws DenormalizerException
     */
    public function testWillOfferAnDidYouMeanForInvalidHeader(): void
    {
        $spreadsheet = $this
            ->getMockBuilder(Spreadsheet::class)
            ->getMock();
        $spreadsheet
            ->expects(static::once())
            ->method('getRow')
            ->with(1)
            ->willReturn(['Amen', 'Nope', 'Name']);

        $mapping = new Mapping(\stdClass::class, [
            'name' => new HeaderReference('Naem'),
        ], ['header' => 1]);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Invalid header "Naem", did you mean "Name"?');

        $iterator = new SpreadsheetIterator($spreadsheet, $mapping, $this->getMockBuilder(Denormalizer::class)->getMock());
        $iterator->current();
    }

    /**
     * @param null|array<string, array<string, null|bool|int>> $counts
     */
    private function mockIterator(?array $counts = null): Iterator
    {
        $iterator = $this
            ->getMockBuilder(Iterator::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $counts) {
            foreach ($counts as $method => $spec) {
                /** @var int $count */
                $count = $spec['count'];

                /** @var InvocationMocker $mocker */
                $mocker = $iterator
                    ->expects(static::exactly($count))
                    ->method($method)
                    ->with(...(array) $spec['params']);

                if (isset($spec['return'])) {
                    $mocker->willReturn($spec['return']);
                }
            }
        }

        return $iterator;
    }
}
