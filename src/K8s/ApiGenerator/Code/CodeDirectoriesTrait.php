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

trait CodeDirectoriesTrait
{
    private function getCodeDirectories(CodeOptions $options): array
    {
        $directory = $options->getSrcDir();
        $directory = substr($directory, -1) !== DIRECTORY_SEPARATOR ? ($directory . DIRECTORY_SEPARATOR) : $directory;
        if ($directory[0] !== DIRECTORY_SEPARATOR) {
            $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
        }
        $directory .= implode(
            DIRECTORY_SEPARATOR,
            explode('\\', $options->getRootNamespace())
        );
        $directory .= DIRECTORY_SEPARATOR;

        return [
            'models' => $directory . 'Model',
            'services' => $directory . 'Service',
        ];
    }
}