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

namespace K8s\ApiGenerator\Github;

class GitTag
{
    private array $tag;

    public function __construct(array $tag)
    {
        $this->tag = $tag;
    }

    public function getRef(): string
    {
        return $this->tag['ref'];
    }

    public function getCommonName(): string
    {
        return substr($this->tag['ref'], 10);
    }

    public function isStable(): bool
    {
        $version = strtolower($this->getCommonName());

        return strpos($version, '-rc') === false
            && strpos($version, '-beta') === false
            && strpos($version, '-dev') === false
            && strpos($version, '-alpha') === false;
    }

    public function startsWith(string $name): bool
    {
        return substr($this->getCommonName(), 0, strlen($name)) === $name;
    }
}
