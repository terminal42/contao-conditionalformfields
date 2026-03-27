<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class Terminal42ConditionalformfieldsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container
            ->services()
            ->load(__NAMESPACE__.'\\', '../src/')
            ->autoconfigure()
            ->autowire()
        ;
    }
}
