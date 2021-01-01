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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CodeCleaner
{
    use CodeDirectoriesTrait;

    private const CS_FIXER_RULES = [
        '@PSR2',
        'no_unused_imports',
        'global_namespace_import',
        'ordered_imports',
    ];

    public function cleanCode(CodeOptions $options, OutputInterface $output): void
    {
        $directories = $this->getCodeDirectories($options);

        foreach ($directories as $name => $dirPath) {
            $output->writeln(sprintf(
                '<info>Running php-cs-fixer on %s.</info>',
                $name
            ));
            $commandProcess = new Process([
                'php',
                'vendor/bin/php-cs-fixer',
                'fix',
                $dirPath,
                sprintf('--rules=%s', implode(',', self::CS_FIXER_RULES)),
                '--using-cache=no',
            ]);
            $commandProcess->setTimeout(null);
            $commandProcess->start();
            $commandProcess->wait();
        }
    }
}
