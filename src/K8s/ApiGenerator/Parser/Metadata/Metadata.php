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

namespace K8s\ApiGenerator\Parser\Metadata;

class Metadata
{
    /**
     * @var OperationMetadata[]
     */
    private array $operations = [];

    /**
     * @var DefinitionMetadata[]
     */
    private array $definitions = [];

    /**
     * @var ServiceGroupMetadata[]
     */
    private array $serviceGroups = [];

    public function addOperation(OperationMetadata $serviceOperationMetadata): void
    {
        $this->operations[] = $serviceOperationMetadata;
    }

    public function addDefinition(DefinitionMetadata $definitionMetadata): void
    {
        $this->definitions[] = $definitionMetadata;
    }

    public function addServiceGroup(ServiceGroupMetadata $serviceGroup): void
    {
        $this->serviceGroups[] = $serviceGroup;
    }

    /**
     * @return OperationMetadata[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return DefinitionMetadata[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function findDefinitionByKind(string $kind, string $version): DefinitionMetadata
    {
        foreach ($this->getDefinitions() as $definition) {
            if ($definition->getKubernetesKind() === $kind && $definition->getKubernetesVersion() === $version) {
                return $definition;
            }
        }

        throw new \RuntimeException(sprintf(
            'Cannot find DefinitionMetadata for Kind "%s" and Version "%s".',
            $kind,
            $version
        ));
    }

    /**
     * @return ServiceGroupMetadata[]
     */
    public function getServiceGroups(): array
    {
        return $this->serviceGroups;
    }

    public function findDefinitionByGoPackageName(string $name): ?DefinitionMetadata
    {
        foreach ($this->definitions as $definition) {
            if ($definition->getGoPackageName() === $name) {
                return $definition;
            }
        }

        return null;
    }
}
