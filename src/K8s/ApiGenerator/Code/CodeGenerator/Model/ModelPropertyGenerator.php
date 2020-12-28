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

use K8s\ApiGenerator\Code\CodeGenerator\CodeGeneratorTrait;
use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\Formatter\PhpPropertyNameFormatter;
use K8s\ApiGenerator\Code\ModelProperty;
use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\PropertyMetadata;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ModelPropertyGenerator
{
    use CodeGeneratorTrait;

    private PhpPropertyNameFormatter $propertyNameFormatter;

    public function __construct()
    {
        $this->propertyNameFormatter = new PhpPropertyNameFormatter();
    }

    public function generate(
        PropertyMetadata $property,
        DefinitionMetadata $definition,
        Metadata $metadata,
        CodeOptions $options,
        ClassType $class,
        PhpNamespace $namespace
    ): ModelProperty {
        $default = $this->getDefaultValue($definition, $property);

        $reference = $property->isModelReference() ?
            $metadata->findDefinitionByGoPackageName($property->getGoPackageName()) : null;
        $phpPropertyName = $this->propertyNameFormatter->format($property->getName());
        $modelProp = new ModelProperty($phpPropertyName, $property, $options, $reference);

        if ($modelProp->isDateTime()) {
            $namespace->addUse($modelProp->getPhpReturnType());
        }
        if ($modelProp->getModelFqcn()) {
            $namespace->addUse($modelProp->getModelFqcn());
        }

        $phpProperty = $class->addProperty(
            $phpPropertyName,
            $default
        );
        $phpProperty->setProtected();
        $phpProperty->setNullable(!$this->isPropRequired($definition, $property));

        if ($default) {
            $phpProperty->setValue($default);
        }

        $annotationProps = ['"' . $property->getName() . '"'];
        if ($modelProp->getAnnotationType()) {
            $annotationProps[] = sprintf('type="%s"', $modelProp->getAnnotationType());
        }
        if ($modelProp->getModelFqcn()) {
            $annotationProps[] = sprintf('model=%s::class', $modelProp->getModelClassName());
        }

        $phpProperty->addComment(
            sprintf(
                '@Kubernetes\Attribute(%s)',
                implode(',', $annotationProps)
            )
        );
        $phpProperty->addComment(
            sprintf(
                '@var %s%s',
                $modelProp->getPhpDocType(),
                $this->isPropRequired($definition, $property) ? '' : '|null'
            )
        );

        return $modelProp;
    }

    private function getDefaultValue(DefinitionMetadata $definition, PropertyMetadata $property): ?string
    {
        if ($property->getName() === 'kind') {
            return $definition->getKubernetesKind();
        } elseif ($property->getName() === 'apiVersion') {
            $default = '';
            if ($definition->getKubernetesGroup()) {
                $default .= $definition->getKubernetesGroup() . '/';
            }
            return $default . $definition->getKubernetesVersion();
        }

        return null;
    }

    private function isPropRequired(DefinitionMetadata $definition, PropertyMetadata $property): bool
    {
        if ($property->isRequired()) {
            return true;
        }
        if ($definition->isKindWithSpecAndMetadata() && ($property->getName() === 'apiVersion' || $property->getName() === 'kind')) {
            return true;
        }

        return $definition->isKindWithSpecAndMetadata()
            && ($property->getName() === 'spec' || $property->getName() === 'metadata');
    }
}
