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

interface Iterator extends \Iterator
{
    public function current(): object;

    public function key(): int;

    /**
     * @param int $rowIndex row index to seek to, one-based
     */
    public function seek(int $rowIndex): void;

    public function prev(): void;
}
