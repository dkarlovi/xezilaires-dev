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

use Xezilaires\Metadata\Mapping;

interface IteratorFactory
{
    public function fromFile(\SplFileObject $file, Mapping $mapping): Iterator;

    public function fromSpreadsheet(Spreadsheet $spreadsheet, Mapping $mapping): Iterator;
}
