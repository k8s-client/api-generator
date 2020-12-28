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

namespace K8s\ApiGenerator\Code\CodeGenerator\Service;

use K8s\ApiGenerator\Code\CodeGenerator\CodeGeneratorTrait;
use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\DocLinkGenerator;
use K8s\ApiGenerator\Code\Formatter\DocBlockFormatterTrait;
use K8s\ApiGenerator\Code\Formatter\PhpParameterDefinitionNameFormatter;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\OperationMetadata;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class OperationMethodCodeGenerator
{
    use CodeGeneratorTrait;
    use DocBlockFormatterTrait;
    use OperationCodeGeneratorTrait;

    private DocLinkGenerator $docLinkGenerator;

    private PhpParameterDefinitionNameFormatter $parameterNameFormatter;

    private OperationMethodBodyGenerator $operationBodyGenerator;

    public function __construct()
    {
        $this->operationBodyGenerator = new OperationMethodBodyGenerator();
        $this->docLinkGenerator = new DocLinkGenerator();
        $this->parameterNameFormatter = new PhpParameterDefinitionNameFormatter();
    }

    public function generate(
        OperationMetadata $operation,
        PhpNamespace $namespace,
        ClassType $class,
        Metadata $metadata,
        CodeOptions $options
    ): void {
        $docblocks = [];
        $method = $class->addMethod($operation->getPhpMethodName());

        foreach ($operation->getRequiredPathParameters() as $parameter) {
            if ($parameter !== 'namespace') {
                $method->addParameter($parameter)
                    ->setType('string');
            }
        }

        if ($operation->getDescription()) {
            $method->addComment($this->formatDocblockDescription($operation->getDescription()));
        }

        $body = null;
        $queryParams = [];
        foreach ($operation->getParameters() as $param) {
            if ($param->isRequiredDefinition()) {
                $definition = $metadata->findDefinitionByGoPackageName($param->getDefinitionGoPackageName());
                if ($definition->isValidModel()) {
                    $paramName = $this->parameterNameFormatter->format($definition);
                    $paramFqcn = $this->makeFinalNamespace($definition->getPhpFqcn(), $options);

                    $namespace->addUse($paramFqcn);
                    $method->addParameter($paramName)
                        ->setType($paramFqcn);
                    $body = $paramName;
                } elseif ($definition->isPatch()) {
                    $method->addParameter('patch')
                        ->setType('array');
                    $body = 'patch';
                }
            } elseif ($param->isQueryParam()) {
                $queryParams[$param->getName()] = $param->getDescription();
            }
        }

        if (!empty($queryParams)) {
            if ($method->getComment()) {
                $method->addComment('');
                $method->addComment('Allowed query parameters:');
                foreach ($queryParams as $name => $description) {
                    $method->addComment(sprintf(
                        '  %s',
                        $name
                    ));
                }
            }
        }

        $docblocks[] = ['param' => 'array|object $query'];

        # This conditional is here for positioning...if it's a websocket, we want the callable / handler first.
        if (!$operation->isWebsocketOperation()) {
            $method->addParameter('query', []);
        }

        if ($operation->needsCallableParameter()) {
            $param = $method->addParameter('handler');
            $types = 'callable';
            if (!$this->isPodExec($operation->getPhpMethodName())) {
                $param->setType('callable');
            } else {
                $types .= '|object';
            }
            if (!$operation->isWebsocketOperation()) {
                $param->setNullable(true);
                $param->setDefaultValue(null);
                $types .='|null';
            }
            $docblocks[] = ['param' => "$types \$handler"];
        }

        if ($operation->isWebsocketOperation()) {
            $method->addParameter('query', []);
        }

        switch ($operation->getReturnedType()) {
            case 'model':
                $model = $operation->getReturnedDefinition();
                $namespace->addUse($this->makeFinalNamespace($model->getPhpFqcn(), $options));
                $method->setReturnType($this->makeFinalNamespace(
                    $model->getPhpFqcn(),
                    $options
                ));
                break;
            case 'string':
            case 'void':
                $method->setReturnType($operation->getReturnedType());
                break;
            default:
                break;
        }
        $method->setReturnNullable($operation->isNullable());

        $docblocks = array_merge($docblocks, $this->makeDocblock($operation, $options));
        if (!empty($docblocks) && $method->getComment()) {
            $method->addComment('');
        }

        foreach ($docblocks as $docblock) {
            foreach ($docblock as $name => $value) {
                $method->addComment(sprintf(
                    '@%s %s',
                    $name,
                    $value
                ));
            }
        }

        $this->operationBodyGenerator->generate(
            $method,
            $operation,
            $namespace,
            $options,
            $body
        );
    }

    private function makeDocblock(OperationMetadata $operation, CodeOptions $options): array
    {
        $docblocks = [];

        if ($operation->isDeprecated() && $operation->getDeprecationDescription()) {
            $docblocks[] = ['deprecated' => $operation->getDeprecationDescription()];
        }
        if ($this->docLinkGenerator->canGenerateLink($options->getVersion())) {
            $docblocks[] = ['link' => $this->docLinkGenerator->generateLink($options->getVersion(), $operation)];
        }

        return $docblocks;
    }
}
