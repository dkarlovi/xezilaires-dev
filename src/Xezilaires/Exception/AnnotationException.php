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

use Doctrine\Common\Annotations\AnnotationException as DoctrineAnnotationException;
use Xezilaires\Exception;

final class AnnotationException extends \UnexpectedValueException implements Exception
{
    public static function unsupportedAnnotation(): self
    {
        return new self('Unsupported annotation');
    }

    /**
     * @param array<int, null|\Xezilaires\Annotation\ArrayReference|\Xezilaires\Annotation\Reference> $references
     */
    public static function tooManyReferencesDefined(\ReflectionProperty $property, array $references): self
    {
        return new self('Too many references defined for '.$property->getName());
    }

    public static function failedCreatingAnnotationReader(DoctrineAnnotationException $exception): self
    {
        return new self('Failed creating annotation reader', 0, $exception);
    }
}
