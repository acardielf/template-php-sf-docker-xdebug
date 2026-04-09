<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Application kernel.
 *
 * Bootstraps the Symfony application and registers bundles,
 * container configuration, and routing.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
