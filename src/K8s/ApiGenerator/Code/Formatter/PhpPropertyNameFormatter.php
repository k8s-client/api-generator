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

class PhpPropertyNameFormatter
{
    public function format(string $propertyName): string
    {
        $propertyName = str_replace('$', '', $propertyName);

        if (strpos($propertyName, '-') !== false) {
            $propertyName = ucwords($propertyName, '-');
            $propertyName = lcfirst($propertyName);
            $propertyName = str_replace('-', '', $propertyName);
        }

        return $propertyName;
    }
}
