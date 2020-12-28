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

namespace K8s\ApiGenerator\Code\CodeGenerator\Model;

use K8s\ApiGenerator\Code\ModelProperty;

trait ModelPropsTrait
{
    /**
     * @param ModelProperty[] $properties
     */
    protected function getCoreProps(array $properties): array
    {
        $props = [
            'spec' => null,
            'metadata' => null,
            'status' => null,
        ];

        foreach ($properties as $modelProp) {
            if ($modelProp->getName() === 'metadata') {
                $props['metadata'] = $modelProp;
            }
            if ($modelProp->getName() === 'spec' || $modelProp->getName() === 'template') {
                $props['spec'] = $modelProp;
            }
            if ($modelProp->getName() === 'status') {
                $props['status'] = $modelProp;
            }
        }

        return $props;
    }
}
