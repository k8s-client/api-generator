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

namespace Crs\K8sApiGenerator\Code\CodeGenerator\Model;

use Crs\K8sApiGenerator\Code\CodeOptions;
use Crs\K8sApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use Crs\K8sApiGenerator\Code\Formatter\PhpMethodNameFormatter;
use Crs\K8sApiGenerator\Code\Formatter\PhpPropertyNameFormatter;
use Crs\K8sApiGenerator\Code\ModelProperty;
use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class AllowedPropsMethodGenerator
{
    use DocBlockFormatterTrait;

    private PhpPropertyNameFormatter $propertyNameFormatter;

    private PhpMethodNameFormatter $methodNameFormatter;

    public function __construct()
    {
        $this->propertyNameFormatter = new PhpPropertyNameFormatter();
        $this->methodNameFormatter = new PhpMethodNameFormatter();
    }

    public function generate(
        ClassType $class,
        PhpNamespace $namespace,
        ModelProperty $model,
        Metadata $metadata,
        CodeOptions $options,
        array $allowedProps = [],
        bool $onlyGetters = false
    ): void {
        foreach ($model->getModelProps() as $prop) {
            if (!empty($allowedProps) && !in_array($prop->getName(), $allowedProps)) {
                continue;
            }
            $modelProp = new ModelProperty(
                $this->propertyNameFormatter->format($prop->getName()),
                $prop,
                $options,
                $metadata->findDefinitionByGoPackageName($prop->getGoPackageName())
            );
            if ($modelProp->isCollection()) {
                $namespace->addUse($options->getCollectionFqcn());
            }
            if ($modelProp->getModelFqcn()) {
                $namespace->addUse($modelProp->getModelFqcn());
            }

            $phpProperty = $modelProp->getPhpPropertyName();

            $phpMethodName = $this->methodNameFormatter->formatModelProperty($modelProp, 'get');
            $method = $class->addMethod($phpMethodName);

            $method->setReturnType($modelProp->getPhpReturnType());
            $method->setBody(sprintf(
                'return $this->%s->%s();',
                $model->getPhpPropertyName(),
                $phpMethodName
            ));
            $method->setReturnNullable(!$modelProp->isRequired());
            $method->addComment($this->formatDocblockDescription($modelProp->getDescription()));
            if (!$modelProp->getPhpReturnType() || $modelProp->isCollection()) {
                if ($method->getComment()) {
                    $method->addComment('');
                }
                $method->addComment(sprintf(
                    '@return %s',
                    $modelProp->getPhpDocType()
                ));
            }

            if ($modelProp->isReadyOnly() || $onlyGetters) {
                continue;
            }

            $phpMethodName = $this->methodNameFormatter->formatModelProperty($modelProp, 'set');
            $method = $class->addMethod($phpMethodName);
            $method->addComment($this->formatDocblockDescription($modelProp->getDescription()));
            $parameter = $method->addParameter($phpProperty);
            $parameter->setType($modelProp->getPhpReturnType());

            if ($method->getComment()) {
                $method->addComment('');
            }
            if (!$modelProp->getPhpReturnType()) {
                $method->addComment(sprintf(
                    '@param %s $%s',
                    $modelProp->getPhpDocType(),
                    $phpProperty
                ));
            }
            $method->addComment('@return static');

            $method->addBody(sprintf(
                '$this->%s->%s($%s);',
                $model->getPhpPropertyName(),
                $phpMethodName,
                $phpProperty
            ));
            $method->addBody('');
            $method->addBody('return $this;');

            if (!$modelProp->isCollection()) {
                continue;
            }
            $namespace->addUse($options->getCollectionFqcn());
            $addProperty = rtrim($phpProperty, 's');

            $phpMethodName = $this->methodNameFormatter->formatModelProperty($modelProp, 'add');
            $method = $class->addMethod($phpMethodName);
            $parameter = $method->addParameter($addProperty);
            $parameter->setType($modelProp->getModelFqcn());

            if ($method->getComment()) {
                $method->addComment('');
            }
            $method->addComment('@return static');

            $method->addBody(sprintf(
                '$this->%s->%s($%s);',
                $model->getPhpPropertyName(),
                $phpMethodName,
                $addProperty
            ));

            $method->addBody('');
            $method->addBody('return $this;');
        }
    }
}
