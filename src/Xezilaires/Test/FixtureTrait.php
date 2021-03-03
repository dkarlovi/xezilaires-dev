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

namespace Xezilaires\Test;

/**
 * @internal
 */
trait FixtureTrait
{
    private function fixture(string $name): \SplFileObject
    {
        try {
            return new \SplFileObject(__DIR__.'/resources/fixtures/'.$name);
        } catch (\RuntimeException $exception) {
            throw new \LogicException(sprintf('Invalid fixture name: %1$s', $name), 0, $exception);
        }
    }

    private function invalidFixture(string $name): \SplFileObject
    {
        try {
            return new class(__DIR__.'/resources/fixtures/'.$name) extends \SplFileObject {
                /**
                 * @psalm-suppress ImplementedReturnTypeMismatch Invalid by design
                 * @phpstan-ignore-next-line
                 */
                public function getRealPath(): bool
                {
                    return false;
                }
            };
        } catch (\RuntimeException $exception) {
            throw new \LogicException(sprintf('Invalid fixture name: %1$s', $name), 0, $exception);
        }
    }
}
