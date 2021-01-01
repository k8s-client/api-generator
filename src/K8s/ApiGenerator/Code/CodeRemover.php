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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\OutputInterface;

class CodeRemover
{
    public function removeCode(OutputInterface $output, CodeOptions $options): void
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

        $directories = [
            'models' => 'Model',
            'services' => 'Service',
        ];

        foreach ($directories as $name => $dirName) {
            $dirPath = $directory . $dirName;
            if (!file_exists($directory)) {
                continue;
            }
            $output->writeln(sprintf(
                '<info>Deleting contents of directory: %s</info>',
                $dirPath
            ));

            $dirIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($dirIterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }
}
