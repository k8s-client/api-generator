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

namespace K8s\ApiGenerator\Code;

use K8s\ApiGenerator\Parser\Metadata\OperationMetadata;

class DocLinkGenerator
{
    private const MIN_VERSION = 'v1.15';

    private const LINK_FORMAT = 'https://kubernetes.io/docs/reference/generated/kubernetes-api/%s/#%s';

    private const ACTION_MAP = [
        'post' => 'create',
        'get' => 'read',
        'deletecollection' => 'delete-collection',
    ];

    public function canGenerateLink(string $version): bool
    {
        return (bool)version_compare($version, self::MIN_VERSION, 'ge');
    }

    public function generateLink(string $version, OperationMetadata $operation): string
    {
        $actionKindVersionGroup = [];

        if ($operation->getKubernetesAction()) {
            $action = $operation->getKubernetesAction();
            $actionKindVersionGroup[] = self::ACTION_MAP[$action] ?? $action;
        }
        if ($operation->getKubernetesKind()) {
            $actionKindVersionGroup[] = $operation->getKubernetesKind();
        }
        if ($operation->getKubernetesVersion()) {
            $actionKindVersionGroup[] = $operation->getKubernetesVersion();
        }
        $actionKindVersionGroup[] = $operation->getKubernetesGroup() ? $operation->getKubernetesGroup() : 'core';

        return sprintf(
            self::LINK_FORMAT,
            implode('.', explode('.', $version, -1)),
            implode(
                '-',
                array_map(fn (string $piece) => str_replace('.', '-', strtolower($piece)), $actionKindVersionGroup)
            )
        );
    }
}
