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

class GitBlob
{
    private array $blob;

    public function __construct(array $blob)
    {
        $this->blob = $blob;
    }

    public function getContent(): string
    {
        return base64_decode($this->blob['content']);
    }
}
