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

namespace K8s\ApiGenerator\Code\CodeGenerator;

use K8s\ApiGenerator\Code\CodeFile;
use K8s\ApiGenerator\Code\CodeGenerator\Model\AllowedPropsMethodGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\Model\ModelAnnotationGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\Model\ModelConstructorGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\Model\ModelPropertyGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\Model\ModelPropsTrait;
use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use K8s\ApiGenerator\Code\CodeGenerator\Model\ModelMethodGenerator;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;
use Nette\PhpGenerator\PhpNamespace;

class ModelCodeGenerator
{
    use CodeGeneratorTrait;
    use DocBlockFormatterTrait;
    use ModelPropsTrait;

    private ModelPropertyGenerator $modelPropertyGenerator;

    private ModelMethodGenerator $modelMethodGenerator;

    private AllowedPropsMethodGenerator $allowedPropsMethodGenerator;

    private ModelAnnotationGenerator $modelAnnotationGenerator;

    private ModelConstructorGenerator $modelConstructorGenerator;

    public function __construct()
    {
        $this->modelPropertyGenerator = new ModelPropertyGenerator();
        $this->modelMethodGenerator = new ModelMethodGenerator();
        $this->allowedPropsMethodGenerator = new AllowedPropsMethodGenerator();
        $this->modelAnnotationGenerator = new ModelAnnotationGenerator();
        $this->modelConstructorGenerator = new ModelConstructorGenerator();
    }

    public function generate(DefinitionMetadata $model, Metadata $metadata, CodeOptions $options): CodeFile
    {
        $namespace = new PhpNamespace($this->makeFinalNamespace($model->getNamespace(), $options));
        $namespace->addUse($options->getAnnotationsNamespace(), 'Kubernetes');
        $class = $namespace->addClass($model->getClassName());
        $class->addComment($this->formatDocblockDescription($model->getDescription()));
        $this->modelAnnotationGenerator->generate($model, $class, $metadata, $options);

        $properties = [];
        foreach ($model->getProperties() as $property) {
            $properties[] = $this->modelPropertyGenerator->generate(
                $property,
                $model,
                $metadata,
                $options,
                $class,
                $namespace
            );
        }
        list(
            'metadata' => $metadataProp,
            'spec' => $specProp,
            'status' => $statusProp
        ) = $this->getCoreProps($properties);

        $this->modelConstructorGenerator->generate(
            $model,
            $properties,
            $class,
            $namespace,
            $metadata,
            $options
        );

        if ($metadataProp) {
            $this->allowedPropsMethodGenerator->generate(
                $class,
                $namespace,
                $metadataProp,
                $metadata,
                $options,
                [],
                ($metadataProp->getModelClassName() === 'ListMeta')
            );
        }

        if ($specProp) {
            $this->allowedPropsMethodGenerator->generate(
                $class,
                $namespace,
                $specProp,
                $metadata,
                $options
            );
        }
        if ($model->getKubernetesKind() && $statusProp) {
            $this->allowedPropsMethodGenerator->generate(
                $class,
                $namespace,
                $statusProp,
                $metadata,
                $options,
                [],
                true
            );
        }

        if ($model->isItemList()) {
            $item = null;
            foreach ($properties as $property) {
                if ($property->getName() === 'items') {
                    $item = $property;
                    break;
                }
            }
            $class->addImplement('IteratorAggregate');
            $method = $class->addMethod('getIterator');
            $method->setReturnType(\Traversable::class);
            $method->addBody('return new \ArrayIterator(iterator_to_array($this->items));');
            if ($item) {
                $method->addComment(sprintf(
                    '@return \ArrayIterator|%s',
                    $item->getPhpDocType()
                ));
            }
            if ($class->getComment()) {
                $class->addComment('');
            }
            $class->addComment(sprintf(
                '@implements \IteratorAggregate<int, %s>',
                $item->getModelClassName()
            ));
        }

        foreach ($properties as $property) {
            $this->modelMethodGenerator->generate(
                $class,
                $namespace,
                $property,
                $model,
                $options
            );
        }

        return new CodeFile(
            $namespace,
            $class
        );
    }
}
