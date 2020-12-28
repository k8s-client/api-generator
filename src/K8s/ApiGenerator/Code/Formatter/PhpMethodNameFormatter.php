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

use K8s\ApiGenerator\Code\ModelProperty;
use K8s\ApiGenerator\Parser\Metadata\ParameterMetadata;

class PhpMethodNameFormatter
{
    public function formatModelProperty(ModelProperty $property, string $mode): string
    {
        $prefix = $this->getPrefix($property->isBool(), $mode);

        return $this->makeFinalName($prefix, $property->getPhpPropertyName());
    }

    public function formatQueryParameter(ParameterMetadata $parameter, string $mode = 'get'): string
    {
        $prefix = $this->getPrefix($parameter->isBool(), $mode);

        return $this->makeFinalName($prefix, $parameter->getName());
    }

    private function makeFinalName(string $prefix, string $propertyName): string
    {
        return sprintf(
            '%s%s',
            $prefix,
            ucfirst($propertyName)
        );
    }

    private function getPrefix(bool $isBool, string $mode): string
    {
        $prefix = $mode;

        if ($isBool && $mode === 'get') {
            $prefix = 'is';
        } elseif ($isBool && $mode === 'set') {
            $prefix .= 'Is';
        }

        return $prefix;
    }
}
