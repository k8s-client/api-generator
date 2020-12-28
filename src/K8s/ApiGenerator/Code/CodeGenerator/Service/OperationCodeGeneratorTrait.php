<?php

/**
 * This file is part of the k8s/api-generator library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace K8s\ApiGenerator\Code\CodeGenerator\Service;

trait OperationCodeGeneratorTrait
{
    private function isPodExec(string $phpMethodName): bool
    {
        return substr($phpMethodName, -strlen('PodExec')) === 'PodExec';
    }
}
