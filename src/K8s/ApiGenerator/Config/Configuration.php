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

namespace K8s\ApiGenerator\Config;

class Configuration
{
    public const KEY_API_VERSION = 'api-version';

    public const KEY_GENERATOR_VERSION = 'generator-version';

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getApiVersion(): string
    {
        return $this->data[self::KEY_API_VERSION] ?? '';
    }

    public function setApiVersion(string $version): self
    {
        $this->data[self::KEY_API_VERSION] = $version;

        return $this;
    }

    public function getGeneratorVersion(): string
    {
        return $this->data[self::KEY_GENERATOR_VERSION] ?? '';
    }

    public function setGeneratorVersion(string $version): self
    {
        $this->data[self::KEY_GENERATOR_VERSION] = $version;

        return $this;
    }
}
