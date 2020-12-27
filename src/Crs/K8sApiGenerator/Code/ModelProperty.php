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

use Crs\K8sApiGenerator\Code\CodeGenerator\CodeGeneratorTrait;
use Crs\K8sApiGenerator\Parser\Metadata\DefinitionMetadata;
use Crs\K8sApiGenerator\Parser\Metadata\PropertyMetadata;

class ModelProperty
{
    use CodeGeneratorTrait;

    private const TYPE_MAP = [
        'integer' => 'int',
        'boolean' => 'bool',
    ];

    private string $phpPropertyName;

    private CodeOptions $options;

    private PropertyMetadata $property;

    private ?DefinitionMetadata $definition;

    public function __construct(
        string $phpPropertyName,
        PropertyMetadata $property,
        CodeOptions $options,
        ?DefinitionMetadata $definition = null
    ) {
        $this->phpPropertyName = $phpPropertyName;
        $this->options = $options;
        $this->property = $property;
        $this->definition = $definition;
    }

    public function isReadyOnly(): bool
    {
        return $this->property->isReadyOnly();
    }

    public function isRequired(): bool
    {
        return $this->property->isRequired();
    }

    public function isCollection(): bool
    {
        if (!$this->definition) {
            return false;
        }

        return $this->property->isArray()
            && $this->definition->isValidModel();
    }

    public function isModel(): bool
    {
        return $this->definition
            && $this->definition->isValidModel();
    }

    public function isDateTime(): bool
    {
        return $this->definition
            && $this->definition->isDateTime();
    }

    public function isBool(): bool
    {
        return $this->getPhpReturnType() === 'bool';
    }

    public function getModelFqcn(): ?string
    {
        if (!$this->definition || !$this->definition->isValidModel()) {
            return null;
        }

        return $this->makeFinalNamespace($this->definition->getPhpFqcn(), $this->options);
    }

    public function getModelClassName(): ?string
    {
        if (!$this->definition || !$this->definition->isValidModel()) {
            return null;
        }

        return $this->definition->getClassName();
    }

    public function getPhpReturnType(): ?string
    {
        $type = $this->property->getType() ?? null;
        if (isset(self::TYPE_MAP[$type])) {
            $type = self::TYPE_MAP[$type];
        }

        if (!$this->definition) {
            $type = $this->property->isArray() ? 'array' : $type;

            return ($type === 'number') ? null : $type;
        }

        if ($this->isCollection()) {
            return 'iterable';
        } elseif ($this->definition->isValidModel()) {
            return $this->makeFinalNamespace($this->definition->getPhpFqcn(), $this->options);
        } elseif ($this->definition->isDateTime()) {
            return \DateTimeInterface::class;
        }

        return null;
    }

    public function getKubernetesType(): ?string
    {
        if ($this->isCollection()) {
            return 'collection';
        }

        if (!$this->definition && $this->property->isArray()) {
            return 'array';
        }
        if (!$this->definition) {
            return $this->property->getType();
        }

        if ($this->definition->isValidModel()) {
            return 'model';
        } elseif ($this->definition->isDateTime()) {
            return 'DateTime';
        } elseif ($this->definition->isIntOrString()) {
            return 'int-or-string';
        } elseif ($this->definition->isString()) {
            return 'string';
        } elseif ($this->definition->isJSONSchemaPropsOrBool()) {
            return 'mixed';
        } elseif ($this->definition->isJsonValue()) {
            return 'mixed';
        } elseif ($this->definition->isJSONSchemaPropsOrArray()) {
            return 'array';
        } else {
            return 'object';
        }
    }

    public function getPhpDocType(): string
    {
        if ($this->isCollection()) {
            return 'iterable|' . $this->definition->getClassName() . '[]';
        }

        if (!$this->definition) {
            $docType = $this->property->getType() ?? 'mixed';
            $docType = ($docType === 'number') ? 'mixed' : $docType;
            $docType = isset(self::TYPE_MAP[$docType]) ? self::TYPE_MAP[$docType] : $docType;
            $docType .= ($this->property->isArray() && $docType !== 'array') ? '[]' : '';

            return $docType;
        }

        if ($this->definition->isValidModel()) {
            $docType = $this->definition->getClassName();
        } elseif ($this->definition->isDateTime()) {
            $docType = 'DateTimeInterface';
        } elseif ($this->definition->isIntOrString()) {
            $docType = 'int|string';
        } elseif ($this->definition->isString()) {
            $docType = 'string';
        } elseif ($this->definition->isJSONSchemaPropsOrBool()) {
            $docType = 'string';
        } elseif ($this->definition->isJsonValue()) {
            $docType = 'mixed';
        } elseif ($this->definition->isJSONSchemaPropsOrArray()) {
            $docType = 'array';
        } else {
            $docType = 'object';
        }

        if ($this->property->isArray() && $docType !== 'array') {
            $docType .= '[]';
        }

        return $docType;
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getPhpPropertyName(): string
    {
        return $this->phpPropertyName;
    }

    public function getAnnotationType(): ?string
    {
        if ($this->isCollection()) {
            return 'collection';
        } elseif ($this->isModel()) {
            return 'model';
        } elseif ($this->isDateTime()) {
            return 'datetime';
        } else {
            return null;
        }
    }

    public function getDescription(): string
    {
        return $this->property->getDescription();
    }

    public function getModelProps(): array
    {
        if (!$this->definition) {
            return [];
        }

        return $this->definition->getProperties();
    }

    /**
     * @return PropertyMetadata[]
     */
    public function getModelRequiredProps(): array
    {
        if (!$this->definition) {
            return [];
        }

        return $this->definition->getRequiredProperties();
    }
}
