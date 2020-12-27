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

namespace Crs\K8sApiGenerator\Code\CodeGenerator;

use Crs\K8sApiGenerator\Code\CodeFile;
use Crs\K8sApiGenerator\Code\CodeGenerator\Service\OperationMethodCodeGenerator;
use Crs\K8sApiGenerator\Code\CodeOptions;
use Crs\K8sApiGenerator\Code\DocLinkGenerator;
use Crs\K8sApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use Crs\K8sApiGenerator\Code\Formatter\PhpParameterDefinitionNameFormatter;
use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Crs\K8sApiGenerator\Parser\Metadata\ServiceGroupMetadata;
use Nette\PhpGenerator\PhpNamespace;

class ServiceCodeGenerator
{
    use CodeGeneratorTrait;
    use DocBlockFormatterTrait;

    private DocLinkGenerator $docLinkGenerator;

    private PhpParameterDefinitionNameFormatter $parameterNameFormatter;

    private OperationMethodCodeGenerator $operationCodeGenerator;

    public function __construct(?DocLinkGenerator $docLinkGenerator = null)
    {
        $this->docLinkGenerator = $docLinkGenerator ?? new DocLinkGenerator();
        $this->parameterNameFormatter = new PhpParameterDefinitionNameFormatter();
        $this->operationCodeGenerator = new OperationMethodCodeGenerator();
    }

    public function generate(ServiceGroupMetadata $serviceGroup, Metadata $metadata, CodeOptions $options): CodeFile
    {
        $namespace = new PhpNamespace($this->makeFinalNamespace($serviceGroup->getFinalNamespace(), $options));
        $namespace->addUse($options->getBaseServiceFqcn());
        $class = $namespace->addClass($serviceGroup->getClassName());
        $class->setExtends($options->getBaseServiceFqcn());

        if ($serviceGroup->getDescription()) {
            $class->addComment($this->formatDocblockDescription($serviceGroup->getDescription()));
        }

        foreach ($serviceGroup->getOperations() as $operation) {
            $this->operationCodeGenerator->generate(
                $operation,
                $namespace,
                $class,
                $metadata,
                $options
            );
        }

        return new CodeFile(
            $namespace,
            $class
        );
    }
}
