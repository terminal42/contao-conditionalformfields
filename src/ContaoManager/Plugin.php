<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\ConditionalformfieldsBundle\Terminal42ConditionalformfieldsBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            (new BundleConfig(Terminal42ConditionalformfieldsBundle::class))->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
