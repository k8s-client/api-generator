<?php

/**
 * This file is part of the `k8s/api-generator `library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace K8s\ApiGenerator\Parser\Metadata;

use Swagger\Annotations\Parameter;

class ParameterMetadata
{
    private const TYPE_MAP = [
        'integer' => 'int',
        'boolean' => 'bool',
    ];

    private OperationMetadata $operation;

    private Parameter $parameter;

    public function __construct(OperationMetadata $operation, Parameter $parameter)
    {
        $this->operation = $operation;
        $this->parameter = $parameter;
    }

    public function isRequiredDefinition(): bool
    {
        return $this->parameter->in === 'body'
            && (bool)$this->parameter->required;
    }

    public function isQueryParam() : bool
    {
        return $this->parameter->in === 'query';
    }

    public function isBool(): bool
    {
        return $this->getPhpDocType() === 'bool';
    }

    public function getDefinitionGoPackageName(): string
    {
        if (empty($this->parameter->schema)) {
            return '';
        }
        $toReplace = '#/definitions/';

        return str_replace($toReplace, '', $this->parameter->schema->ref);
    }

    public function getName(): ?string
    {
        return $this->parameter->name;
    }

    public function getDescription(): ?string
    {
        return $this->parameter->description;
    }

    public function getPhpDocType(): string
    {
        $type = $this->parameter->type ?? 'mixed';
        if (isset(self::TYPE_MAP[$type])) {
            return self::TYPE_MAP[$type];
        }

        return $type;
    }

    public function getPhpReturnType(): ?string
    {
        $type = $this->getPhpDocType();

        return ($type === 'mixed') ? null : $type;
    }
}
