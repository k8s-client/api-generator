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
use K8s\ApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use K8s\ApiGenerator\Code\Formatter\PhpMethodNameFormatter;
use K8s\ApiGenerator\Code\ModelProperty;
use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ModelMethodGenerator
{
    use DocBlockFormatterTrait;

    private PhpMethodNameFormatter $methodNameFormatter;

    public function __construct()
    {
        $this->methodNameFormatter = new PhpMethodNameFormatter();
    }

    public function generate(
        ClassType $phpClass,
        PhpNamespace $phpNamespace,
        ModelProperty $property,
        DefinitionMetadata $definition,
        CodeOptions $options
    ): void {
        $phpProperty = $property->getPhpPropertyName();

        $method = $phpClass->addMethod($this->methodNameFormatter->formatModelProperty($property, 'get'));

        if ($property->getModelFqcn()) {
            $phpNamespace->addUse($property->getModelFqcn());
        }

        $method->setReturnType($property->getPhpReturnType());
        $method->setBody(sprintf(
            'return $this->%s;',
            $phpProperty
        ));
        $method->setReturnNullable(!$this->isPropRequired($definition, $property));
        $method->addComment($this->formatDocblockDescription($property->getDescription()));

        if (!$property->getPhpReturnType() || $property->isCollection()) {
            if ($method->getComment()) {
                $method->addComment('');
            }
            $method->addComment(sprintf(
                '@return %s',
                $property->getPhpDocType()
            ));
        }

        if ($property->isReadyOnly()) {
            return;
        }

        $method = $phpClass->addMethod($this->methodNameFormatter->formatModelProperty($property, 'set'));
        $method->addComment($this->formatDocblockDescription($property->getDescription()));
        $parameter = $method->addParameter($phpProperty);
        $parameter->setType($property->getPhpReturnType());

        if ($method->getComment()) {
            $method->addComment('');
        }
        if (!$property->getPhpReturnType()) {
            $method->addComment(sprintf(
                '@param %s $%s',
                $property->getPhpDocType(),
                $phpProperty
            ));
        }
        $method->addComment('@return static');

        $method->addBody(sprintf(
            '$this->%s = $%s;',
            $phpProperty,
            $phpProperty
        ));
        $method->addBody('');
        $method->addBody('return $this;');

        if (!$property->isCollection()) {
            return;
        }
        $phpNamespace->addUse($options->getCollectionFqcn());
        $addProperty = rtrim($phpProperty, 's');

        $method = $phpClass->addMethod($this->methodNameFormatter->formatModelProperty($property, 'add'));
        $parameter = $method->addParameter($addProperty);
        $parameter->setType($property->getModelFqcn());

        if ($method->getComment()) {
            $method->addComment('');
        }
        $method->addComment('@return static');

        $method->addBody(sprintf(
            <<<BODY
            if (!\$this->{$property->getPhpPropertyName()}) {
                \$this->{$property->getPhpPropertyName()} = new Collection();
            }
            BODY
        ));

        $method->addBody(sprintf(
            '$this->%s[] = $%s;',
            $phpProperty,
            $addProperty
        ));
        $method->addBody('');
        $method->addBody('return $this;');
    }

    private function isPropRequired(DefinitionMetadata $definition, ModelProperty $property): bool
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
