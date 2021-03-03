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

use Xezilaires\Metadata\Mapping;

/**
 * @template T of object
 */
final class SpreadsheetIteratorFactory implements IteratorFactory
{
    /**
     * @var Denormalizer
     */
    private $denormalizer;

    /**
     * @var array<class-string<Spreadsheet<T>>>
     */
    private $spreadsheetClasses;

    /**
     * @param array<class-string<Spreadsheet<T>>> $spreadsheetClasses
     */
    public function __construct(Denormalizer $denormalizer, array $spreadsheetClasses)
    {
        $this->denormalizer = $denormalizer;
        $this->spreadsheetClasses = $spreadsheetClasses;
    }

    /**
     * @return Iterator<T>
     */
    public function fromFile(\SplFileObject $file, Mapping $mapping): Iterator
    {
        foreach ($this->spreadsheetClasses as $spreadsheetClass) {
            return $this->fromSpreadsheet($spreadsheetClass::fromFile($file), $mapping);
        }

        throw new \LogicException('Install either phpoffice/phpspreadsheet or box/spout to read Excel files');
    }

    /**
     * @return Iterator<T>
     */
    public function fromSpreadsheet(Spreadsheet $spreadsheet, Mapping $mapping): Iterator
    {
        return new SpreadsheetIterator($spreadsheet, $mapping, $this->denormalizer);
    }
}
