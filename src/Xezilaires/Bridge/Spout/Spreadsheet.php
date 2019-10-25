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

namespace Xezilaires\Bridge\Spout;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Iterator;
use Xezilaires\Spreadsheet as SpreadsheetInterface;

final class Spreadsheet implements SpreadsheetInterface
{
    /**
     * @var array<int, string> $indexCache
     */
    private static $indexCache = [];

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var null|ReaderInterface
     */
    private $reader;

    /**
     * @var null|RowIterator
     */
    private $iterator;

    public function __construct(\SplFileObject $file)
    {
        $this->file = $file;
    }

    public function createIterator(int $startRowIndex): void
    {
        if (null !== $this->iterator) {
            throw SpreadsheetException::iteratorAlreadyCreated();
        }

        $sheet = $this->getActiveWorksheet();

        /** @var IteratorInterface $iterator */
        $iterator = $sheet->getRowIterator();

        $this->iterator = new RowIterator($iterator, $startRowIndex);
    }

    public function getIterator(): Iterator
    {
        if (null === $this->iterator) {
            throw SpreadsheetException::noIterator();
        }

        return $this->iterator;
    }

    public function getRow(int $rowIndex): array
    {
        $iterator = $this->getIterator();
        $seekRow = $iterator->key();
        $iterator->seek($rowIndex);
        $row = $this->getCurrentRow();
        $iterator->seek($seekRow);

        return $row;
    }

    public function getCurrentRow(): array
    {
        /** @var \ArrayObject $rowArrayObject */
        $rowArrayObject = $this->getIterator()->current();

        /** @var array<string, null|float|int|string> $row */
        $row = [];

        /**
         * @var int                   $columnIndex
         * @var null|float|int|string $columnValue
         */
        foreach ($rowArrayObject as $columnIndex => $columnValue) {
            $columnName = self::stringFromColumnIndex($columnIndex + 1);

            $row[$columnName] = '' !== $columnValue ? $columnValue : null;
        }

        return $row;
    }

    public function getHighestRow(): int
    {
        if (null === $this->iterator) {
            throw SpreadsheetException::noIterator();
        }

        return $this->iterator->getHighestRow();
    }

    /**
     * @author https://github.com/phpoffice/PhpSpreadsheet
     */
    private static function stringFromColumnIndex(int $columnIndex): string
    {
        if (false === isset(self::$indexCache[$columnIndex])) {
            $indexValue = $columnIndex;
            $base26 = '';
            do {
                /** @var int $characterValue Psalm bug? */
                $characterValue = ($indexValue % 26) ?: 26;
                $indexValue = ($indexValue - $characterValue) / 26;
                $base26 = \chr($characterValue + 64).($base26 ?: '');
            } while ($indexValue > 0);

            self::$indexCache[$columnIndex] = $base26;
        }

        return self::$indexCache[$columnIndex];
    }

    /**
     * @throws SpreadsheetException
     */
    private function getReader(): ReaderInterface
    {
        if (null === $this->reader) {
            $path = $this->file->getRealPath();
            if (false === $path) {
                throw SpreadsheetException::noSpreadsheetFound();
            }

            try {
                $this->reader = ReaderEntityFactory::createXLSXReader();
                $this->reader->open($path);
            } catch (UnsupportedTypeException | IOException $exception) {
                throw SpreadsheetException::invalidSpreadsheet($exception);
            }
        }

        return $this->reader;
    }

    /**
     * @throws SpreadsheetException
     */
    private function getActiveWorksheet(): SheetInterface
    {
        try {
            /** @var SheetInterface $sheet */
            foreach ($this->getReader()->getSheetIterator() as $sheet) {
                return $sheet;
            }
        } catch (ReaderNotOpenedException $exception) {
            throw SpreadsheetException::failedFetchingActiveWorksheet($exception);
        }

        throw SpreadsheetException::failedFetchingActiveWorksheet();
    }
}
