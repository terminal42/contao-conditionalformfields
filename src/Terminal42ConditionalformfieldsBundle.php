<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42ConditionalformfieldsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
