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

namespace K8s\ApiGenerator\Parser\Metadata;

use Swagger\Annotations\Property;
use const Swagger\UNDEFINED;

class PropertyMetadata
{
    private bool $isRequired;

    private Property $property;

    public function __construct(Property $property, bool $isRequired)
    {
        $this->property = $property;
        $this->isRequired = $isRequired;
    }

    public function getType(): ?string
    {
        if ($this->property->type === 'object' && isset($this->property->additionalProperties->type)) {
            return $this->property->additionalProperties->type;
        } elseif (isset($this->property->items->type)) {
            return $this->property->items->type;
        } else {
            return $this->property->type;
        }
    }

    public function getDescription(): string
    {
        return (string)$this->property->description;
    }

    public function getName(): string
    {
        return $this->property->property;
    }

    public function isModelReference(): bool
    {
        return isset($this->property->ref)
            || isset($this->property->items->ref);
    }

    public function isReadyOnly(): bool
    {
        $isReadOnly = (bool)$this->property->readOnly;
        if ($isReadOnly) {
            return true;
        }

        // Hacky solution since they aren't setting it in their OpenAPI spec properly.
        return (bool)preg_match('/Read-only\./', (string)$this->property->description);
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function getGoPackageName(): string
    {
        $toReplace = '#/definitions/';

        if ($this->property->ref) {
            return str_replace($toReplace, '', $this->property->ref);
        } elseif (!empty($this->property->items) && !empty($this->property->items->ref)) {
            return str_replace($toReplace, '', $this->property->items->ref);
        }

        return '';
    }

    public function isArray(): bool
    {
        return $this->property->type === 'array'
            || $this->property->type === 'object';
    }
}
