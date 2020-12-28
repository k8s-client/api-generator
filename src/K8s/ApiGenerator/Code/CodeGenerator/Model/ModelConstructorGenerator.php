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

use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\Formatter\PhpPropertyNameFormatter;
use K8s\ApiGenerator\Code\ModelProperty;
use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\Core\Collection;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;

class ModelConstructorGenerator
{
    use ModelPropsTrait;

    private PhpPropertyNameFormatter $propertyNameFormatter;

    public function __construct()
    {
        $this->propertyNameFormatter = new PhpPropertyNameFormatter();
    }

    /**
     * @param ModelProperty[] $properties
     */
    public function generate(
        DefinitionMetadata $model,
        array $properties,
        ClassType $class,
        PhpNamespace $namespace,
        Metadata $metadata,
        CodeOptions $options
    ): void {
        list('metadata' => $metadataProp, 'spec' => $specProp, ) = $this->getCoreProps($properties);
        $requiredProps = $this->getRequiredProps($properties);

        if (!empty($requiredProps) || ($specProp || $metadataProp)) {
            $this->addModelConstructor(
                $metadata,
                $options,
                $class,
                $namespace,
                $properties
            );
        } elseif (count($properties) <= 5) {
            $this->addGenericConstructor(
                $class,
                $namespace,
                $properties
            );
        }

        if ($model->getClassName() === 'ObjectMeta') {
            $this->addObjectMetaConstructor($class);
        }
    }

    /**
     * @param ModelProperty[] $properties
     */
    private function addModelConstructor(
        Metadata $metadata,
        CodeOptions $options,
        ClassType $class,
        PhpNamespace $namespace,
        array $properties
    ): void {
        list('metadata' => $metadataProp, 'spec' => $specProp, ) = $this->getCoreProps($properties);
        $requiredProps = $this->getRequiredProps($properties);
        $docblockParams = [];
        $constructor = $class->addMethod('__construct');

        if ($metadataProp && $metadataProp->getModelClassName() === 'ObjectMeta') {
            $param = $constructor->addParameter('name');
            $param->setType('string');
            $param->setNullable(true);

            $constructor->addBody(sprintf(
                '$this->%s = new %s($name);',
                $metadataProp->getPhpPropertyName(),
                $metadataProp->getModelClassName()
            ));
        }

        foreach ($requiredProps as $requiredProp) {
            if ($requiredProp === $specProp) {
                continue;
            }
            if ($requiredProp === $metadataProp) {
                continue;
            }
            $this->addPropertyToConstructor(
                $constructor,
                $requiredProp,
                $namespace,
                true
            );
        }

        if ($specProp) {
            $specParams = [];

            foreach ($specProp->getModelRequiredProps() as $specReqProp) {
                $modelProp = new ModelProperty(
                    $this->propertyNameFormatter->format($specReqProp->getName()),
                    $specReqProp,
                    $options,
                    $metadata->findDefinitionByGoPackageName($specReqProp->getGoPackageName())
                );
                if ($modelProp->isCollection()) {
                    $namespace->addUse(Collection::class);
                }
                if ($modelProp->getModelFqcn()) {
                    $namespace->addUse($modelProp->getModelFqcn());
                }
                $param = $constructor->addParameter($modelProp->getPhpPropertyName());
                $param->setType($modelProp->getPhpReturnType());
                $docblockParams[$param->getName()] = $modelProp;
                $specParams [] = '$' . $modelProp->getPhpPropertyName();
            }

            if (!empty($specParams)) {
                $constructor->addBody(sprintf(
                    '$this->%s = new %s(%s);',
                    $specProp->getPhpPropertyName(),
                    $specProp->getModelClassName(),
                    implode(', ', $specParams)
                ));
            }
        }

        # Hacky solution as image is not mark as required, but in most cases you want to specify it.
        if ($class->getName() === 'Container') {
            $imageProp = null;
            foreach ($properties as $property) {
                if ($property->getName() === 'image') {
                    $imageProp = $property;
                    break;
                }
            }
            if ($imageProp) {
                $constructor->addParameter($imageProp->getPhpPropertyName())
                    ->setType($imageProp->getPhpReturnType())
                    ->setNullable(true)
                    ->setDefaultValue(null);
                $constructor->addBody('$this->image = $image;');
                $docblockParams[$imageProp->getPhpPropertyName()] = $imageProp;
            }
        }

        /** @var ModelProperty $prop */
        foreach ($docblockParams as $paramName => $prop) {
            $constructor->addComment(sprintf(
                '@param %s $%s',
                $prop->getPhpDocType(),
                $paramName
            ));
        }
    }

    private function addGenericConstructor(
        ClassType $classType,
        PhpNamespace $namespace,
        array $properties
    ): void {
        $constructor = $classType->addMethod('__construct');

        foreach ($properties as $property) {
            $this->addPropertyToConstructor(
                $constructor,
                $property,
                $namespace,
                false
            );
        }
    }

    private function addObjectMetaConstructor(ClassType $classType): void
    {
        $constructor = $classType->addMethod('__construct');
        $constructor->addParameter('name')
            ->setType('string')
            ->setNullable(true)
            ->setDefaultValue(null);
        $constructor->addParameter('namespace')
            ->setType('string')
            ->setNullable(true)
            ->setDefaultValue(null);

        $constructor->addBody(<<<BODY
        if (\$name) {
            \$this->name = \$name;
        }
        if (\$namespace) {
            \$this->namespace = \$namespace;
        }
        BODY);
    }

    private function addPropertyToConstructor(
        Method $constructor,
        ModelProperty $prop,
        PhpNamespace $namespace,
        bool $isRequired
    ): void {
        $param = $constructor->addParameter($prop->getPhpPropertyName());
        $param->setType($prop->getPhpReturnType());
        $param->setNullable(!($isRequired || $prop->isCollection()));

        if (!$isRequired) {
            $param->setDefaultValue($prop->getDefaultConstructorValue());
        }

        if ($prop->isCollection()) {
            $namespace->addUse(Collection::class);
            $constructor->addBody(sprintf(
                '$this->%s = new Collection($%s);',
                $prop->getPhpPropertyName(),
                $prop->getPhpPropertyName()
            ));
        } else {
            $constructor->addBody(sprintf(
                '$this->%s = $%s;',
                $prop->getPhpPropertyName(),
                $prop->getPhpPropertyName()
            ));
        }
        $constructor->addComment(sprintf(
            '@param %s $%s',
            $prop->getPhpDocType() . ($isRequired && !$prop->isCollection() ? '' : '|null'),
            $prop->getPhpPropertyName()
        ));
    }

    /**
     * @param ModelProperty[] $properties
     * @return ModelProperty[]
     */
    protected function getRequiredProps(array $properties): array
    {
        return array_filter($properties, fn (ModelProperty $prop) => $prop->isRequired());
    }
}
