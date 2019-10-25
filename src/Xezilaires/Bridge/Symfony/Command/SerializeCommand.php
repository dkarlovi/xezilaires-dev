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

namespace Xezilaires\Bridge\Symfony\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Xezilaires\Metadata\Annotation\AnnotationDriver;
use Xezilaires\Serializer;
use Xezilaires\SpreadsheetIteratorFactory;

final class SerializeCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'xezilaires:serialize';

    /**
     * @var SpreadsheetIteratorFactory
     */
    private $iteratorFactory;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(SpreadsheetIteratorFactory $iteratorFactory, Serializer $serializer)
    {
        /** @psalm-suppress MissingThrowsDocblock */
        parent::__construct('xezilaires:serialize');

        $this->setDescription('Serialize Excel files into JSON, XML, CSV');

        $this->iteratorFactory = $iteratorFactory;
        $this->serializer = $serializer;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('xezilaires:serialize')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to file to process')
            ->addArgument('class', InputArgument::REQUIRED, 'Process the rows as class')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Format to export to', 'json')
            ->addOption('reverse', 'r', InputOption::VALUE_NONE, 'Iterate in reverse')
            ->addOption('xml-root', null, InputOption::VALUE_OPTIONAL, 'Name of root node in XML format', 'root');
    }

    /**
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var string $path */
        $path = $input->getArgument('path');
        /**
         * @var string $class
         * @psalm-var class-string $class
         */
        $class = $input->getArgument('class');
        /** @var null|string $format */
        $format = $input->getOption('format');
        /** @var bool $reverse */
        $reverse = $input->getOption('reverse');
        /** @var null|string $xmlRoot */
        $xmlRoot = $input->getOption('xml-root');

        if (null === $format) {
            throw new \UnexpectedValueException('Format is required');
        }

        $context = [];
        switch ($format) {
            case 'csv':
                if (false === class_exists(CsvEncoder::class)) {
                    /** @psalm-suppress MissingThrowsDocblock */
                    throw new \LogicException('CSV format is only available with Symfony 4.0+');
                }
                break;
            case 'json':
                $context['json_encode_options'] = JSON_PRETTY_PRINT;
                break;
            case 'xml':
                if (null === $xmlRoot) {
                    throw new \UnexpectedValueException('XML root node name cannot be empty if XML format requested');
                }
                $context['xml_root_node_name'] = $xmlRoot;
                break;
        }

        $driver = new AnnotationDriver();
        $iterator = $this->iteratorFactory->fromFile(
            new \SplFileObject($path),
            $driver->getMetadataMapping($class, ['reverse' => $reverse])
        );
        $output->write($this->serializer->serialize($iterator, $format, $context));

        return 0;
    }
}
