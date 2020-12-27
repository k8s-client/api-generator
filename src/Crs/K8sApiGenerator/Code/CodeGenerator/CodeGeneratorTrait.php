<?php

/**
 * This file is part of the crs/k8s-api-generator library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Crs\K8sApiGenerator\Code\CodeGenerator;

use Crs\K8sApiGenerator\Code\CodeOptions;

trait CodeGeneratorTrait
{
    private function makeFinalNamespace(string $namespace, CodeOptions $options): string
    {
        return sprintf(
            '%s\\%s',
            $options->getRootNamespace(),
            $namespace
        );
    }
}
