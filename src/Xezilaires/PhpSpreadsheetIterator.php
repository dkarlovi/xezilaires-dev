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

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Xezilaires\Metadata\Mapping;

/**
 * Class CategoryProvider.
 */
class PhpSpreadsheetIterator implements Iterator
{
    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var null|Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var null|\Iterator
     */
    private $iterator;

    /**
     * @var null|DenormalizerInterface
     */
    private $denormalizer;

    /**
     * @var null|array<string, string>
     */
    private $headers;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @param \SplFileObject $file
     * @param Mapping        $mapping
     */
    public function __construct(\SplFileObject $file, Mapping $mapping)
    {
        $this->file = $file;
        $this->mapping = $mapping;

        $this->mapping->setReferenceResolver(new PhpSpreadsheetHeaderReferenceResolver($this));
    }

    /**
     * @param string $header
     *
     * @return string
     */
    public function getColumnByHeader(string $header): string
    {
        $headerColumnReferences = $this->getHeaderColumnReferences();

        return $headerColumnReferences[$header];
    }

    /**
     * @param int    $rowIndex
     * @param string $columnIndex
     *
     * @return null|string|int|float
     */
    public function fetch(int $rowIndex, string $columnIndex)
    {
        $worksheet = $this->getActiveWorksheet();
        try {
            $cell = $worksheet->getCell(sprintf('%1$s%2$d', $columnIndex, $rowIndex));
        } catch (Exception $exception) {
            throw new \RuntimeException('Value not found at coordinates: '.$exception->getMessage());
        }

        if (null !== $cell) {
            /** @var null|string|int|float $value */
            $value = $cell->getValue();

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function current()
    {
        /** @var Row $row */
        $row = $this->getIterator()->current();
        $row = $this->readRow($row->getRowIndex());

        /** @var array<string, null|string|int|float> $data */
        $data = [];
        foreach ($this->mapping->getColumnMapping() as $name => $column) {
            $data[$name] = $row[$column];
        }

        return $this->getDenormalizer()->denormalize($data, $this->mapping->getClassName());
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->key;

        $this->getIterator()->next();
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->key = 0;

        $this->getIterator()->rewind();
    }

    /**
     * @return bool
     */
    public function areItemsNestable(): bool
    {
        return is_subclass_of($this->mapping->getClassName(), Nestable::class);
    }

    /**
     * @return Spreadsheet
     */
    private function getSpreadsheet(): Spreadsheet
    {
        if (null === $this->spreadsheet) {
            $path = $this->file->getRealPath();
            if (false === $path) {
                throw new \RuntimeException('Unable to open spreadsheet');
            }

            try {
                $reader = IOFactory::createReaderForFile($path);
                $this->spreadsheet = $reader->load($path);
            } catch (ReaderException $exception) {
                throw new \RuntimeException('Unable to open spreadsheet', 0, $exception);
            }
        }

        return $this->spreadsheet;
    }

    /**
     * @return Worksheet
     */
    private function getActiveWorksheet(): Worksheet
    {
        try {
            return $this->getSpreadsheet()->getActiveSheet();
        } catch (Exception $exception) {
            throw new \RuntimeException('Unable to fetch active worksheet', 0, $exception);
        }
    }

    /**
     * @return \Iterator
     */
    private function getIterator(): \Iterator
    {
        if (null === $this->iterator) {
            $sheet = $this->getActiveWorksheet();

            /** @var int $start */
            $start = $this->mapping->getOption('start');
            $this->iterator = $sheet->getRowIterator($start);
        }

        return $this->iterator;
    }

    /**
     * @param int $rowIndex
     *
     * @return array<string, null|string|int|float>
     */
    private function readRow(int $rowIndex): array
    {
        $data = [];
        $worksheet = $this->getActiveWorksheet();
        $columnIterator = $worksheet->getColumnIterator();

        foreach ($columnIterator as $column) {
            $columnIndex = $column->getColumnIndex();
            $data[$columnIndex] = $this->fetch($rowIndex, $columnIndex);
        }

        return $data;
    }

    /**
     * @return DenormalizerInterface
     */
    private function getDenormalizer(): DenormalizerInterface
    {
        if (null === $this->denormalizer) {
            $this->denormalizer = new ObjectNormalizer();
        }

        return $this->denormalizer;
    }

    /**
     * @return array<string, string>
     */
    private function getHeaderColumnReferences(): array
    {
        if (null === $this->headers) {
            /** @var null|int $headerRowIndex */
            $headerRowIndex = $this->mapping->getOption('header');
            if (null === $headerRowIndex) {
                throw new \RuntimeException('Header index must be set');
            }
            /** @var array<string, string> $headerRow */
            $headerRow = $this->readRow($headerRowIndex);

            $headers = [];
            foreach ($headerRow as $column => $header) {
                if (isset($headers[$header])) {
                    throw new \RuntimeException('Header already used');
                }

                $headers[$header] = $column;
            }
            $this->headers = $headers;
        }

        return $this->headers;
    }
}
