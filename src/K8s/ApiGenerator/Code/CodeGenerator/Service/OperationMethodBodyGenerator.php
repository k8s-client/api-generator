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
use K8s\ApiGenerator\Parser\Metadata\OperationMetadata;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;

class OperationMethodBodyGenerator
{
    use CodeGeneratorTrait;
    use OperationCodeGeneratorTrait;

    public function generate(
        Method $method,
        OperationMetadata $operation,
        PhpNamespace $namespace,
        CodeOptions $options,
        ?string $body
    ): void {
        $method->addBody('$options[\'query\'] = $query;');
        if ($body) {
            $method->addBody(sprintf(
                '$options[\'body\'] = $%s;',
                $body
            ));
        }
        if ($this->hasHandlerParam($method)) {
            $method->addBody('$options[\'handler\'] = $handler;');
        }
        if ($operation->getReturnedDefinition()) {
            $model = $operation->getReturnedDefinition();
            $namespace->addUse($this->makeFinalNamespace($model->getPhpFqcn(), $options));
            $method->addBody(sprintf(
                '$options[\'model\'] = %s::class;',
                $model->getClassName()
            ));
        }
        $this->addUriBodyCode($operation, $method);

        $returnOrNot = $operation->getReturnedType() !== 'void' ? 'return ' : '';
        $method->addBody('');

        if ($operation->isWebsocketOperation()) {
            $type = $this->getWebsocketOperationType($operation->getPhpMethodName());
            $method->addBody(
                <<<PHP_BODY
            $returnOrNot\$this->api->executeWebsocket(
                \$uri,
                ?,
                \$handler
            );
            PHP_BODY,
                [
                    $type
                ]
            );
        } else {
            $method->addBody(
                <<<PHP_BODY
            $returnOrNot\$this->api->executeHttp(
                \$uri,
                ?,
                \$options
            );
            PHP_BODY,
                [
                    $operation->getKubernetesAction()
                ]
            );
        }
    }

    private function addUriBodyCode(OperationMetadata $operation, Method $method): void
    {
        $params = '[';

        foreach ($operation->getRequiredPathParameters() as $parameter) {
            if ($parameter !== 'namespace') {
                $params .= "'{" . $parameter . "}' => \${$parameter},";
            }
        }
        $params .= ']';

        $method->addBody(
            <<<PHP_BODY
            \$uri = \$this->api->makeUri(
                ?,
                $params,
                \$query,
                \$this->namespace
            );
            PHP_BODY,
            [$operation->getUriPath()]
        );
    }

    private function hasHandlerParam(Method $method): bool
    {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === 'handler') {
                return true;
            }
        }

        return false;
    }

    private function getWebsocketOperationType(string $phpMethodName): string
    {
        if ($this->isPodExec($phpMethodName)) {
            return 'exec';
        } elseif ($this->isPortForward($phpMethodName)) {
            return 'portforward';
        } elseif ($this->isProxy($phpMethodName)) {
            return 'proxy';
        } else {
            return 'generic';
        }
    }
}
