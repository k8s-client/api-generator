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

use K8s\ApiGenerator\Parser\Formatter\ServiceGroupName;

class ServiceGroupMetadata
{
    private ServiceGroupName $group;

    /**
     * @var OperationMetadata[]
     */
    private array $operations;

    public function __construct(
        ServiceGroupName $group,
        array $operations
    ) {
        $this->group = $group;
        $this->operations = $operations;
    }

    public function getFqcn(): string
    {
        return $this->makeFinalNamespace($this->group->getFqcn());
    }

    public function getFinalNamespace(): string
    {
        return $this->makeFinalNamespace($this->group->getFullNamespace());
    }

    public function getNamespace(): string
    {
        return $this->group->getFullNamespace();
    }

    public function getClassName(): string
    {
        return $this->group->getClassName();
    }

    public function getKind(): string
    {
        return $this->group->getKind();
    }

    public function getVersion(): string
    {
        return $this->group->getVersion();
    }

    public function getGroup(): ?string
    {
        return $this->group->getGroupName();
    }

    /**
     * @return OperationMetadata[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getDescription(): string
    {
        foreach ($this->operations as $operation) {
            if ($operation->getPhpMethodName() === 'read' && $operation->getReturnedDefinition()) {
                $definition = $operation->getReturnedDefinition();

                return $definition->getDescription();
            }
        }

        return '';
    }

    public function getModelDefinition(): ?DefinitionMetadata
    {
        $operation = $this->getCreateOperation();
        if (!$operation) {
            return null;
        }

        return $operation->getReturnedDefinition();
    }

    public function getCreateOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'post') {
                continue;
            }
            if (substr($operation->getPhpMethodName(), 0, strlen('create')) === 'create') {
                return $operation;
            }
        }

        return null;
    }

    public function getDeleteOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'delete') {
                continue;
            }
            if (substr($operation->getPhpMethodName(), 0, strlen('delete')) === 'delete') {
                return $operation;
            }
        }

        return null;
    }

    public function getDeleteCollectionOperation(bool $namespaced = true): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'deletecollection') {
                continue;
            }
            if ($namespaced && $operation->requiresNamespace()) {
                return $operation;
            } elseif (!$namespaced && !$operation->requiresNamespace()) {
                return $operation;
            }
        }

        return null;
    }

    public function getWatchOperation(bool $namespaced = true): ?OperationMetadata
    {
        $operation = $this->getListOperation($namespaced);

        return ($operation && $operation->isWatchable()) ? $operation : null;
    }

    public function getListOperation(bool $namespaced = true): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'list') {
                continue;
            }
            $operationIsNamespaced = strpos($operation->getUriPath(), "/{namespace}/") !== false;
            if ($operationIsNamespaced && $namespaced) {
                return $operation;
            } elseif (!$operationIsNamespaced && !$namespaced) {
                return $operation;
            } else {
                continue;
            }
        }

        return null;
    }

    public function getReadOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() === 'get') {
                return $operation;
            }
        }

        return null;
    }

    public function getPatchOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() === 'patch') {
                return $operation;
            }
        }

        return null;
    }

    private function makeFinalNamespace(string $namespace): string
    {
        return sprintf(
            'Service\\%s',
            $namespace
        );
    }
}
