<?php

declare(strict_types=1);

/*
 * This file is part of the xezilaires project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xezilaires\Bridge\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator as PhpSpreadsheetRowIterator;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Iterator;

/**
 * @internal
 *
 * @template T as \PhpOffice\PhpSpreadsheet\Worksheet\Row
 *
 * @implements Iterator<T>
 */
final class RowIterator implements Iterator
{
    /**
     * @var PhpSpreadsheetRowIterator
     */
    private $iterator;

    public function __construct(PhpSpreadsheetRowIterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function current(): object
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): int
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function seek(int $rowIndex): void
    {
        try {
            $this->iterator->seek($rowIndex);
        } catch (PhpSpreadsheetException $exception) {
            throw SpreadsheetException::invalidSeek($exception);
        }
    }

    public function prev(): void
    {
        $this->iterator->prev();
    }
}
