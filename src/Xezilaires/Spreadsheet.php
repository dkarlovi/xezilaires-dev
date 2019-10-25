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

use Xezilaires\Exception\SpreadsheetException;

interface Spreadsheet
{
    /**
     * @param int $startRowIndex row index where this iterator starts, one-based
     *
     * @throws SpreadsheetException
     */
    public function createIterator(int $startRowIndex): void;

    /**
     * @throws SpreadsheetException
     */
    public function getIterator(): Iterator;

    /**
     * @param int $rowIndex row index to fetch, one-based
     *
     * @throws SpreadsheetException
     *
     * @return array<string, null|float|int|string>
     */
    public function getRow(int $rowIndex): array;

    /**
     * @throws SpreadsheetException
     *
     * @return array<string, null|float|int|string>
     */
    public function getCurrentRow(): array;

    /**
     * @throws SpreadsheetException
     *
     * @return int row index of the last row, one-based
     */
    public function getHighestRow(): int;
}
