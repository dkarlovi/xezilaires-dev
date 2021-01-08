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

namespace Xezilaires\Bridge\Symfony;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Xezilaires\Bridge\Symfony\DependencyInjection\CompilerPass\RegisterCommandsCompilerPass;

/**
 * @internal
 */
final class AppKernel extends Kernel
{
    /**
     * @var BundleInterface[]
     */
    private $customBundles;

    /**
     * @param BundleInterface[] $customBundles
     */
    public function __construct(array $customBundles)
    {
        parent::__construct('prod', false);

        $this->customBundles = $customBundles;
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return array_merge([
            new FrameworkBundle(),
            new XezilairesBundle(),
        ], $this->customBundles);
    }

    /**
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/Resources/bin-config/services.xml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/xezilaires-'.md5(__DIR__);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/xezilaires-'.md5(__DIR__);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterCommandsCompilerPass());
    }
}
