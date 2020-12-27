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

use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Swagger\Annotations\AbstractAnnotation;
use Swagger\Annotations\Definition;
use Swagger\Annotations\Swagger;

class OpenApiContext
{
    private AbstractAnnotation $subject;

    private Swagger $openApi;

    private Metadata $generatedApi;

    public function __construct(
        AbstractAnnotation $subject,
        Swagger $openApi,
        Metadata $generatedApi
    ) {
        $this->subject = $subject;
        $this->openApi = $openApi;
        $this->generatedApi = $generatedApi;
    }

    public function getSubject(): AbstractAnnotation
    {
        return $this->subject;
    }

    public function findRef(string $ref): ?Definition
    {
        $ref = $this->openApi->ref($ref);

        if ($ref && !$ref instanceof Definition) {
            throw new \RuntimeException(sprintf(
                'Expected a Definition, got: %s',
                get_class($ref)
            ));
        }

        return $ref;
    }
}
