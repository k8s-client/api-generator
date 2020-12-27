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

namespace Crs\K8sApiGenerator\Parser;

use Crs\K8sApiGenerator\Parser\Formatter\ServiceGroupNameFormatter;
use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Crs\K8sApiGenerator\Parser\Metadata\ServiceGroupMetadata;
use Crs\K8sApiGenerator\Parser\MetadataGenerator\OperationMetadataGenerator;
use Crs\K8sApiGenerator\Parser\Formatter\GoPackageNameFormatter;
use Crs\K8sApiGenerator\Parser\Metadata\DefinitionMetadata;
use Swagger\Annotations\Swagger;

class MetadataParser
{
    private OperationMetadataGenerator $serviceGenerator;

    private GoPackageNameFormatter $goPkgNameFormatter;

    private ServiceGroupNameFormatter $groupNameFormatter;

    public function __construct(?OperationMetadataGenerator $operationMetadataGenerator = null)
    {
        $this->serviceGenerator = $operationMetadataGenerator ?? new OperationMetadataGenerator();
        $this->goPkgNameFormatter = new GoPackageNameFormatter();
        $this->groupNameFormatter = new ServiceGroupNameFormatter();
    }

    public function parse(Swagger $openApi): Metadata
    {
        $metadata = new Metadata();

        foreach ($openApi->definitions as $definition) {
            $metadata->addDefinition(new DefinitionMetadata(
                $this->goPkgNameFormatter->format($definition->definition),
                $definition
            ));
        }

        foreach ($openApi->paths as $path) {
            $openApiObject = new OpenApiContext($path, $openApi, $metadata);
            foreach ($this->serviceGenerator->generate($openApiObject, $metadata) as $serviceOperationMetadata) {
                $metadata->addOperation($serviceOperationMetadata);
            }
        }

        // @todo What to do with endpoints not relating to a specific group?
        list($groups, $noGroups) = $this->getGroupedOperations($metadata);

        foreach ($groups as $group => $kinds) {
            foreach ($kinds as $kind => $versions) {
                foreach ($versions as $version => $operations) {
                    $metadata->addServiceGroup(new ServiceGroupMetadata(
                        $this->groupNameFormatter->format($group, $version, $kind),
                        $operations
                    ));
                }
            }
        }

        return $metadata;
    }

    private function getGroupedOperations(Metadata $generatedApi): array
    {
        $groups = [];
        $noGroup = [];

        foreach ($generatedApi->getOperations() as $operation) {
            if ($operation->getKubernetesGroup() === null) {
                $noGroup[] = $operation;
                continue;
            }
            $group = $operation->getKubernetesGroup() === '' ? 'core' : $operation->getKubernetesGroup();
            $kind = $operation->getKubernetesKind();
            $version = $operation->getKubernetesVersion();
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            if (!isset($groups[$group][$kind])) {
                $groups[$group][$kind] = [];
            }
            if (!isset($groups[$group][$kind][$version])) {
                $groups[$group][$kind][$version] = [];
            }
            $groups[$group][$kind][$version][] = $operation;
        }

        return [$groups, $noGroup];
    }
}
