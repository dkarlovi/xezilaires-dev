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

namespace Xezilaires\Metadata;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Xezilaires\Exception\MappingException;

/**
 * @final
 */
/* final */ class Mapping
{
    /**
     * @var string
     * @psalm-var class-string
     */
    private $className;

    /**
     * @var array<string, Reference>
     */
    private $references = [];

    /**
     * @var array<string, null|bool|string>
     */
    private $options;

    /**
     * @var bool
     */
    private $headerOptionRequired = false;

    /**
     * @param array<string, Reference> $references
     */
    public function __construct(string $className, array $references, array $options = null)
    {
        $this->setClassName($className);
        $this->setReferences($references);

        try {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);

            /** @var array<string, null|bool|string> $options */
            $options = $resolver->resolve($options ?? []);
        } catch (OptionsResolverException $exception) {
            throw MappingException::invalidOption($exception);
        }
        $this->setOptions($options);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return array<string, Reference>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @return null|bool|string
     */
    public function getOption(string $option)
    {
        return $this->options[$option];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'start' => 1,
            'end' => null,
            'header' => null,
            'reverse' => false,
        ]);

        $resolver->setAllowedTypes('start', 'int');
        $resolver->setAllowedTypes('end', ['int', 'null']);
        $resolver->setAllowedTypes('header', ['int', 'null']);
        $resolver->setAllowedTypes('reverse', 'bool');
    }

    /**
     * @psalm-param string $className
     */
    private function setClassName(string $className): void
    {
        if (false === class_exists($className)) {
            throw MappingException::classNotFound($className);
        }

        $this->className = $className;
    }

    /**
     * @param array<string, object> $references
     */
    private function setReferences(array $references): void
    {
        if ([] === $references) {
            throw MappingException::noReferencesSpecified();
        }

        $headerOptionRequired = false;
        foreach ($references as $name => $reference) {
            if (false === $reference instanceof Reference) {
                throw MappingException::invalidReference($name);
            }

            if (true === $reference instanceof HeaderReference) {
                $headerOptionRequired = true;
            }

            $this->references[$name] = $reference;
        }

        $this->headerOptionRequired = $headerOptionRequired;
    }

    /**
     * @param array<string, null|bool|string> $options
     */
    private function setOptions(array $options): void
    {
        if (true === $this->headerOptionRequired && false === isset($options['header'])) {
            throw MappingException::missingHeaderOption();
        }

        $this->options = $options;
    }
}
