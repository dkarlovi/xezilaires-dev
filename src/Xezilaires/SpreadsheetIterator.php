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

use Xezilaires\Bridge\Symfony\Serializer\Exception as SerializerException;
use Xezilaires\Exception\DenormalizerException;
use Xezilaires\Exception\MappingException;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Metadata\ArrayReference;
use Xezilaires\Metadata\ColumnReference;
use Xezilaires\Metadata\HeaderReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\Metadata\Reference;

final class SpreadsheetIterator implements Iterator
{
    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var Denormalizer
     */
    private $denormalizer;

    /**
     * @var array
     */
    private $context;

    /**
     * @var null|Iterator
     */
    private $iterator;

    /**
     * @var null|array<string, array<int, string>|string>
     */
    private $headers;

    /**
     * @var int
     */
    private $index = 0;

    public function __construct(Spreadsheet $spreadsheet, Mapping $mapping, Denormalizer $denormalizer, array $context = [])
    {
        $this->spreadsheet = $spreadsheet;
        $this->mapping = $mapping;
        $this->denormalizer = $denormalizer;
        $this->context = $context;
    }

    /**
     * @throws SpreadsheetException
     * @throws DenormalizerException
     */
    public function current(): object
    {
        $row = $this->spreadsheet->getCurrentRow();

        /** @var array<string, null|array<null|float|int|string>|float|int|string> $data */
        $data = [];
        foreach ($this->mapping->getReferences() as $name => $reference) {
            if ($reference instanceof ArrayReference) {
                $data[$name] = $this->readArrayReference($row, $reference);
            } else {
                $data[$name] = $this->readReference($row, $reference);
            }
        }

        try {
            return $this->denormalizer->denormalize($data, $this->mapping->getClassName(), null, $this->context);
        } catch (SerializerException $exception) {
            throw DenormalizerException::denormalizationFailed($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prev(): void
    {
        --$this->index;

        $this->getIterator()->prev();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->index;

        $this->getIterator()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->index;
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
        $this->index = 0;

        $this->getIterator()->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $rowIndex): void
    {
        /** @var int $start */
        $start = $this->mapping->getOption('start');
        $this->index = $rowIndex;

        $this->getIterator()->seek($start + $rowIndex);
    }

    private function getIterator(): Iterator
    {
        if (null === $this->iterator) {
            /** @var int $start */
            $start = $this->mapping->getOption('start');
            $this->spreadsheet->createIterator($start);

            $iterator = $this->spreadsheet->getIterator();

            $reverse = $this->mapping->getOption('reverse');
            if (true === $reverse) {
                $iterator = new ReverseIterator($iterator, $start, $this->spreadsheet->getHighestRow());
            }
            $this->iterator = $iterator;
        }

        return $this->iterator;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function getHeaderColumnReferences(): array
    {
        if (null === $this->headers) {
            /** @var null|int $headerRowIndex */
            $headerRowIndex = $this->mapping->getOption('header');
            if (null === $headerRowIndex) {
                throw MappingException::missingHeaderOption();
            }
            /** @var array<string, null|string> $headerRow */
            $headerRow = $this->spreadsheet->getRow($headerRowIndex);

            $this->headers = [];
            foreach ($headerRow as $column => $header) {
                if (null === $header) {
                    continue;
                }

                if (isset($this->headers[$header])) {
                    if (\is_string($this->headers[$header])) {
                        $this->headers[$header] = (array) $this->headers[$header];
                    }
                    $this->headers[$header][] = $column;
                } else {
                    $this->headers[$header] = $column;
                }
            }
        }

        return $this->headers;
    }

    private function getColumnByHeader(string $header): string
    {
        $headerColumnReferences = $this->getHeaderColumnReferences();
        if (false === \array_key_exists($header, $headerColumnReferences)) {
            throw MappingException::headerNotFound($header, array_keys($headerColumnReferences));
        }

        if (\is_array($headerColumnReferences[$header])) {
            throw MappingException::ambiguousHeader($header, $headerColumnReferences[$header]);
        }

        return $headerColumnReferences[$header];
    }

    /**
     * @param array<string, null|float|int|string> $row
     *
     * @return array<null|float|int|string>
     */
    private function readArrayReference(array $row, ArrayReference $reference): array
    {
        $data = [];
        foreach ($reference->getReference() as $arrayReference) {
            $data[] = $this->readReference($row, $arrayReference);
        }

        return $data;
    }

    /**
     * @param array<string, null|float|int|string> $row
     *
     * @return null|float|int|string
     */
    private function readReference(array $row, Reference $reference)
    {
        switch (true) {
            case $reference instanceof ColumnReference:
                $column = $reference->getReference();
                $data = $row[$column];
                break;
            case $reference instanceof HeaderReference:
                $column = $this->getColumnByHeader($reference->getReference());
                $data = $row[$column];
                break;
            default:
                throw MappingException::unexpectedReference();
        }

        return $data;
    }
}
