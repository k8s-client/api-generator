<?php

declare(strict_types=1);

namespace K8s\ApiGenerator\Config;

class ConfigurationManager
{
    private const CONFIG_NAME = '.k8s-api.json';

    public function read(): ?Configuration
    {
        $config = null;

        if (file_exists($this->getFilePath())) {
            $config = $this->getConfigFromFile();
        }

        return $config;
    }

    public function newConfig(string $apiVersion, string $generatorVersion): Configuration
    {
        return new Configuration([
            Configuration::KEY_API_VERSION => $apiVersion,
            Configuration::KEY_GENERATOR_VERSION => $generatorVersion,
        ]);
    }

    public function write(Configuration $configuration): void
    {
        $data = json_encode([
            Configuration::KEY_GENERATOR_VERSION => $configuration->getGeneratorVersion(),
            Configuration::KEY_API_VERSION => $configuration->getApiVersion(),
        ]);

        if (file_put_contents($this->getFilePath(), $data) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to save config to "%s".',
                $this->getFilePath()
            ));
        }
    }

    private function getConfigFromFile(): Configuration
    {
        $file = $this->getFilePath();
        $config = file_get_contents($file);

        if ($config === false) {
            throw new \RuntimeException(sprintf(
                'Unable to read config file: %s',
                $file
            ));
        }
        $config = json_decode($config, true);
        if ($config === false) {
            throw new \RuntimeException(sprintf(
                'Unable to decode JSON config file: %s',
                $file
            ));
        }

        return new Configuration($config);
    }

    private function getFilePath(): string
    {
        return sprintf(
            '%s%s%s',
            getcwd(),
            DIRECTORY_SEPARATOR,
            self::CONFIG_NAME
        );
    }
}
