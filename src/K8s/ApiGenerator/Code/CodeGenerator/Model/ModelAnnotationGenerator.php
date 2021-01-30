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
use K8s\ApiGenerator\Parser\Metadata\DefinitionMetadata;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\OperationMetadata;
use Nette\PhpGenerator\ClassType;

class ModelAnnotationGenerator
{
    use CodeGeneratorTrait;

    public function generate(DefinitionMetadata $model, ClassType $class, Metadata $metadata, CodeOptions $options): void
    {
        if (!($model->getKubernetesKind() && $model->getKubernetesVersion())) {
            return;
        }
        if ($class->getComment()) {
            $class->addComment('');
        }
        $annotationProps = ['"' . $model->getKubernetesKind() . '"'];
        if ($model->getKubernetesGroup()) {
            $annotationProps[] = sprintf(
                'group="%s"',
                $model->getKubernetesGroup()
            );
        }
        if ($model->getKubernetesVersion()) {
            $annotationProps[] = sprintf(
                'version="%s"',
                $model->getKubernetesVersion()
            );
        }
        $class->addComment(sprintf(
            '@Kubernetes\Kind(%s)',
            implode(',', $annotationProps)
        ));

        $modelSvcGroup = null;
        foreach ($metadata->getServiceGroups() as $serviceGroup) {
            $def = $serviceGroup->getModelDefinition();
            if ($def && $def->getPhpFqcn() === $model->getPhpFqcn()) {
                $modelSvcGroup = $serviceGroup;
                break;
            }
        }

        if (!$modelSvcGroup) {
            return;
        }

        $operations = [
            'get' => $modelSvcGroup->getReadOperation(),
            'post' => $modelSvcGroup->getCreateOperation(),
            'delete' => $modelSvcGroup->getDeleteOperation(),
            'watch' => $modelSvcGroup->getWatchOperation(),
            'put' => $modelSvcGroup->getPutOperation(),
            'put-status' => $modelSvcGroup->getPutStatusOperation(),
            'deletecollection' => $modelSvcGroup->getDeleteCollectionOperation(),
            'deletecollection-all' => $modelSvcGroup->getDeleteCollectionOperation(false),
            'watch-all' => $modelSvcGroup->getWatchOperation(false),
            'patch' => $modelSvcGroup->getPatchOperation(),
            'patch-status' => $modelSvcGroup->getPatchStatusOperation(),
            'list' =>  $modelSvcGroup->getListOperation(),
            'list-all' => $modelSvcGroup->getListOperation(false),
        ];

        /**@var OperationMetadata $operation */
        foreach (array_filter($operations) as $action => $operation) {
            $params = [sprintf('path="%s"', $operation->getUriPath())];

            foreach ($operation->getParameters() as $parameter) {
                if ($parameter->isRequiredDefinition()) {
                    $definition = $metadata->findDefinitionByGoPackageName($parameter->getDefinitionGoPackageName());
                    $params[] = sprintf(
                        'body="%s"',
                        $definition->isValidModel() ? 'model' : 'patch'
                    );
                }
            }

            $isWatchAction = ($action === 'watch' || $action === 'watch-all');
            $returnedDefinition = $operation->getReturnedDefinition();
            if ($returnedDefinition && !$isWatchAction) {
                $responseModel = ($returnedDefinition === $model)
                    ? 'static::class'
                    : $this->makeFinalNamespace($returnedDefinition->getPhpFqcn(), $options);
                $params[] = sprintf('response="%s"', $responseModel);
            } elseif ($isWatchAction) {
                $responseModel = $metadata->findDefinitionByKind('WatchEvent', 'v1');
                $responseModel = $this->makeFinalNamespace($responseModel->getPhpFqcn(), $options);
                $params[] = sprintf('response="%s"', $responseModel);
            }

            $class->addComment(sprintf(
                '@Kubernetes\Operation("%s",%s)',
                $action,
                implode(',', $params)
            ));
        }
    }
}
