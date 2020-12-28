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

namespace K8s\ApiGenerator\Code\Writer;

use K8s\ApiGenerator\Code\CodeFile;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Filesystem\Filesystem;

class PhpFileWriter
{
    private const LICENSE_BLOCK = <<<LICNESE
        This file was automatically generated by k8s/api-generator.
        
        (c) Chad Sikorra <Chad.Sikorra@gmail.com>
        
        For the full copyright and license information, please view the LICENSE
        file that was distributed with this source code.
        LICNESE;

    private Filesystem $fileSystem;

    private PsrPrinter $psrPrinter;

    public function __construct(?Filesystem $fileSystem = null, ?PsrPrinter $psrPrinter = null)
    {
        $this->fileSystem = $fileSystem ?? new Filesystem();
        $this->psrPrinter = $psrPrinter ?? new PsrPrinter();
    }

    public function write(CodeFile $codeFile, string $srcDir): string
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment(self::LICENSE_BLOCK);
        $file->addNamespace($codeFile->getPhpNamespace());

        $filename = $srcDir . DIRECTORY_SEPARATOR . $codeFile->getFullFileName();
        $this->fileSystem->dumpFile(
            $filename,
            $this->psrPrinter->printFile($file)
        );

        return $filename;
    }
}