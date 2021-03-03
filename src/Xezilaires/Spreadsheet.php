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

namespace Xezilaires;

/**
 * @template T of object
 */
interface Spreadsheet
{
    public static function fromFile(\SplFileObject $file): self;

    /**
     * @param int $startRowIndex row index where this iterator starts, one-based
     */
    public function createIterator(int $startRowIndex): void;

    /**
     * @return Iterator<T>
     */
    public function getIterator(): Iterator;

    /**
     * @param int $rowIndex row index to fetch, one-based
     *
     * @return array<string, null|float|int|string>
     */
    public function getRow(int $rowIndex): array;

    /**
     * @return array<string, null|float|int|string>
     */
    public function getCurrentRow(): array;

    /**
     * @return int row index of the last row, one-based
     */
    public function getHighestRow(): int;
}
