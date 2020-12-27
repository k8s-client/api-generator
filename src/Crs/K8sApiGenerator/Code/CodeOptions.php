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

namespace Crs\K8sApiGenerator\Code;

class CodeOptions
{
    private string $rootNamespace;

    private string $version;

    private string $srcDir;

    private string $baseServiceFactoryFqcn = 'Crs\\K8s\\Service\\AbstractServiceFactory';

    private string $baseServiceFqcn = 'Crs\\K8s\\Service\\AbstractService';

    private string $annotationsBaseNamespace = 'Crs\\K8s\\Annotation';

    private string $collectionFqcn = '\\Crs\\K8s\\Collection';

    public function __construct(
        string $version,
        string $rootNamespace,
        string $srcDir
    ) {
        $this->rootNamespace = $rootNamespace;
        $this->version = $version;
        $this->srcDir = $srcDir;
    }

    public function getAnnotationsNamespace(): string
    {
        return $this->annotationsBaseNamespace;
    }

    public function getRootNamespace(): string
    {
        return $this->rootNamespace;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSrcDir(): string
    {
        return $this->srcDir;
    }

    public function getBaseServiceFqcn(): string
    {
        return $this->baseServiceFqcn;
    }

    public function getBaseServiceFactoryFqcn(): string
    {
        return $this->baseServiceFactoryFqcn;
    }

    public function getCollectionFqcn(): string
    {
        return $this->collectionFqcn;
    }
}
