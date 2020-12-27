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

namespace Crs\K8sApiGenerator\Parser\MetadataGenerator;

use Crs\K8sApiGenerator\Parser\Formatter\ServiceGroupNameFormatter;
use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Crs\K8sApiGenerator\Parser\Metadata\OperationMetadata;
use Crs\K8sApiGenerator\Parser\Metadata\ResponseMetadata;
use Crs\K8sApiGenerator\Parser\OpenApiContext;
use Swagger\Annotations\Operation;
use Swagger\Annotations\Path;
use Swagger\Annotations\Response;

class OperationMetadataGenerator
{
    public const OPERATIONS = [
        'get',
        'delete',
        'post',
        'patch',
        'put',
        'options',
        'head',
    ];
    
    private ServiceGroupNameFormatter $groupNameFormatter;

    public function __construct()
    {
        $this->groupNameFormatter = new ServiceGroupNameFormatter();
    }

    /**
     * @return OperationMetadata[]
     */
    public function generate(OpenApiContext $openApiObject, Metadata $generatedApi): array
    {
        /** @var Path $path */
        $path = $openApiObject->getSubject();

        $serviceOperations = [];
        foreach (self::OPERATIONS as $httpOperation) {
            if (isset($path->$httpOperation)) {
                /** @var Operation $apiOperation */
                $apiOperation = $path->$httpOperation;
                $responses = $this->parseResponses(
                    $apiOperation->responses,
                    $openApiObject,
                    $generatedApi
                );
                $serviceOperations[] = new OperationMetadata(
                    $path,
                    $apiOperation,
                    $responses
                );
            }
        }

        return $serviceOperations;
    }

    public function supports(OpenApiContext $openApiObject): bool
    {
        return $openApiObject->getSubject() instanceof Path;
    }

    /**
     * @return ResponseMetadata[]
     */
    private function parseResponses(array $responses, OpenApiContext $openApiContext, Metadata $generatedApi): array
    {
        $responsesMetadata = [];

        /** @var Response $response */
        foreach ($responses as $response) {
            if (empty($response->schema) || empty($response->schema->ref)) {
                $responsesMetadata[] = new ResponseMetadata($response);

                continue;
            }
            $def = $openApiContext->findRef($response->schema->ref);
            $definition = $generatedApi->findDefinitionByGoPackageName((string)$def->definition);
            if (!$definition) {
                throw new \RuntimeException('No model found: '. $def->definition);
            }
            $responsesMetadata[] = new ResponseMetadata($response, $definition);
        }

        return $responsesMetadata;
    }
}
