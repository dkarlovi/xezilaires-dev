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

use Xezilaires\Bridge\Symfony\Serializer\Exception as SerializerException;
use Xezilaires\Exception;

final class DenormalizerException extends \UnexpectedValueException implements Exception
{
    public static function denormalizationFailed(SerializerException $exception): self
    {
        return new self('Denormalization failed', 0, $exception);
    }
}
