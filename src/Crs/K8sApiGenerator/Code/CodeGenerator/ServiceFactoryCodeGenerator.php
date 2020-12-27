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

namespace Crs\K8sApiGenerator\Code\CodeGenerator;

use Crs\K8sApiGenerator\Code\CodeFile;
use Crs\K8sApiGenerator\Code\CodeOptions;
use Crs\K8sApiGenerator\Parser\Metadata\Metadata;
use Crs\K8sApiGenerator\Parser\Metadata\ServiceGroupMetadata;
use Nette\PhpGenerator\PhpNamespace;

class ServiceFactoryCodeGenerator
{
    use CodeGeneratorTrait;

    public function generate(Metadata $metadata, CodeOptions $options): CodeFile
    {
        $namespace = new PhpNamespace($this->makeFinalNamespace('Service', $options));

        $namespace->addUse($options->getBaseServiceFactoryFqcn());
        $class = $namespace->addClass('ServiceFactory');
        $class->addExtend($options->getBaseServiceFactoryFqcn());

        foreach ($metadata->getServiceGroups() as $serviceGroup) {
            $serviceFqcn = $this->makeFinalNamespace(
                $serviceGroup->getFqcn(),
                $options
            );
            $namespace->addUse($serviceFqcn);

            $method = $class->addMethod($this->makeMethodName($serviceGroup));
            $method->setReturnType($serviceFqcn);
            $method->addBody(sprintf(
                'return $this->makeService(\'%s\');',
                $serviceFqcn,
            ));
        }

        return new CodeFile(
            $namespace,
            $class
        );
    }

    private function makeMethodName(ServiceGroupMetadata $serviceGroup): string
    {
        $method = lcfirst($serviceGroup->getVersion());

        if ($serviceGroup->getGroup()) {
            $group = $serviceGroup->getGroup();
            $group = strpos($group, '.') === false ?
                $group : explode('.', $serviceGroup->getGroup(), -1)[0];
            $method .= ucfirst($group);
        }

        return $method.$serviceGroup->getKind();
    }
}
