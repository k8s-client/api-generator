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

namespace K8s\ApiGenerator\Code\Formatter;

use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;

class PhpParameterDefinitionNameFormatter
{
    public function format(DefinitionMetadata $definition): string
    {
        return lcfirst($definition->getClassName());
    }
}
