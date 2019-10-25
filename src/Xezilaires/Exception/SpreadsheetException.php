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

namespace Xezilaires\Exception;

use Xezilaires\Exception;

final class SpreadsheetException extends \UnexpectedValueException implements Exception
{
    public static function noSpreadsheetFound(): self
    {
        return new self('No spreadsheet path given');
    }

    public static function invalidSpreadsheet(\Throwable $exception): self
    {
        return new self('Invalid spreadsheet: '.$exception->getMessage(), 0, $exception);
    }

    public static function invalidCell(\Throwable $exception): self
    {
        return new self('Invalid cell: '.$exception->getMessage(), 0, $exception);
    }

    /**
     * @return SpreadsheetException
     */
    public static function invalidSeek(\Throwable $exception): self
    {
        return new self('Invalid seek: '.$exception->getMessage(), 0, $exception);
    }

    public static function failedFetchingActiveWorksheet(?\Throwable $exception = null): self
    {
        $message = 'Failed to fetch active worksheet';
        if (null === $exception) {
            return new self($message);
        }

        return new self($message.': '.$exception->getMessage(), 0, $exception);
    }

    /**
     * @return SpreadsheetException
     */
    public static function noIterator(): self
    {
        return new self('No iterator was created');
    }

    /**
     * @return SpreadsheetException
     */
    public static function iteratorAlreadyCreated(): self
    {
        return new self('Iterator already created');
    }
}
