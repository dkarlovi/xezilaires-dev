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

namespace Xezilaires\Test\Performance\Spout;

use PhpBench\Benchmark\Metadata\Annotations as Bench;
use Xezilaires\Spreadsheet;
use Xezilaires\Test\Performance\PerformanceTestCase;

/**
 * @internal
 *
 * @small
 */
final class SpoutSpreadsheetIteratorBench extends PerformanceTestCase
{
    protected function getSpreadsheet(\SplFileObject $file): Spreadsheet
    {
        return new \Xezilaires\Bridge\Spout\Spreadsheet($file);
    }
}
