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

namespace K8s\ApiGenerator\Parser\Formatter;

class ServiceGroupNameFormatter
{
    public function format(string $groupName, string $version, string $kind): ServiceGroupName
    {
        $groupPieces = explode(
            '.',
            str_replace('.k8s.io', '', $groupName)
        );

        foreach ($groupPieces as $i => $groupPiece) {
            if (isset(GoPackageNameFormatter::NAME_REPLACEMENTS[$groupPiece])) {
                $groupPieces[$i] = GoPackageNameFormatter::NAME_REPLACEMENTS[$groupPiece];
            } else {
                $groupPieces[$i] = ucfirst($groupPiece);
            }
        }

        if (count($groupPieces) === 1) {
            $baseNamespace = '';
            $baseName = $groupPieces[0];
        } else {
            $baseName = array_pop($groupPieces);
            $baseNamespace = implode('\\', $groupPieces);
        }

        return new ServiceGroupName(
            $groupName,
            $baseNamespace,
            $baseName,
            $version,
            $kind
        );
    }
}
