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
use K8s\ApiGenerator\Code\CodeGenerator\Service\OperationMethodCodeGenerator;
use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\DocLinkGenerator;
use K8s\ApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use K8s\ApiGenerator\Code\Formatter\PhpParameterDefinitionNameFormatter;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\ServiceGroupMetadata;
use K8s\Core\Contract\ApiInterface;
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
        $namespace->addUse(ApiInterface::class);

        $class = $namespace->addClass($serviceGroup->getClassName());
        $class->addProperty('api')
            ->setPrivate()
            ->addComment('@var ApiInterface');

        $class->addProperty('namespace')
            ->setPrivate()
            ->addComment('@var string|null');

        $constructor = $class->addMethod('__construct');
        $param = $constructor->addParameter('api');
        $param->setType(ApiInterface::class);
        $constructor->addBody('$this->api = $api;');

        $method = $class->addMethod('useNamespace')->setReturnType('self');
        $param = $method->addParameter('namespace');
        $param->setType('string');
        $method->addBody(
            <<<BODY
            \$this->namespace = \$namespace;
            
            return \$this;
            BODY
        );

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
