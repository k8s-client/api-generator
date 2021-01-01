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

namespace K8s\ApiGenerator\Command;

use K8s\ApiGenerator\Code\CodeGenerator;
use K8s\ApiGenerator\Code\CodeOptions;
use K8s\ApiGenerator\Code\CodeRemover;
use K8s\ApiGenerator\Config\Configuration;
use K8s\ApiGenerator\Config\ConfigurationManager;
use K8s\ApiGenerator\Github\GithubClient;
use K8s\ApiGenerator\Github\GitTag;
use K8s\ApiGenerator\Parser\MetadataParser;
use Swagger\Annotations\Swagger;
use Swagger\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenerateCommand extends Command
{
    private const GITHUB_OWNER = 'kubernetes';

    private const GITHUB_REPO = 'kubernetes';

    private const SWAGGER_SPEC_PATH = '/api/openapi-spec/swagger.json';

    private GithubClient $githubClient;

    private Serializer $serializer;

    private MetadataParser $metadataParser;

    private CodeGenerator $codeGenerator;

    private ConfigurationManager $configManager;

    private CodeRemover $codeRemover;

    public function __construct(
        ?GithubClient $githubClient = null,
        ?Serializer $serializer = null,
        ?MetadataParser $metadataParser = null,
        ?CodeGenerator $codeGenerator = null,
        ?ConfigurationManager $configManager = null,
        ?CodeRemover $codeRemover = null
    ) {
        $this->githubClient = $githubClient ?? new GithubClient();
        $this->serializer = $serializer ?? new Serializer();
        $this->metadataParser = $metadataParser ?? new MetadataParser();
        $this->codeGenerator = $codeGenerator ?? new CodeGenerator();
        $this->configManager = $configManager ?? new ConfigurationManager();
        $this->codeRemover = $codeRemover ?? new CodeRemover();
        parent::__construct('generate');
    }

    protected function configure(): void
    {
        $this->setDescription('Generate K8s API objects from OpenAPI specifications.');
        $this->addOption(
            'api-version',
            'a',
            InputOption::VALUE_REQUIRED,
            'The API version of K8s to generate.'
        );
        $this->addOption(
            'src-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'The location of the soure files.',
            'src/'
        );
        $this->addOption(
            'root-namespace',
            null,
            InputOption::VALUE_REQUIRED,
            'The root namespace for the generated classes.',
            'K8s\\Api'
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Always generate the API regardless of the config values.'
        );
        $this->addOption(
            'no-delete',
            null,
            InputOption::VALUE_NONE,
            'Do not delete existing generated code.'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiVersion = $input->getOption('api-version');
        $rootNamespace = $input->getOption('root-namespace');
        $srcDir = $input->getOption('src-dir');

        if (!(is_string($apiVersion) || is_null($apiVersion))) {
            $output->writeln(sprintf('<error>The api-version must be a string or null.</error>'));

            return self::FAILURE;
        }
        if (!(is_string($rootNamespace) || is_null($rootNamespace))) {
            $output->writeln(sprintf('<error>The root-namespace must be a string or null.</error>'));

            return self::FAILURE;
        }
        if (!(is_string($srcDir) || is_null($srcDir))) {
            $output->writeln(sprintf('<error>The src-dir must be a string or null.</error>'));

            return self::FAILURE;
        }

        $tag = $this->getTagFromRepo(
            $output,
            $apiVersion
        );
        $apiVersion = $apiVersion ?? $tag->getCommonName();

        $config = $this->configManager->read();
        if ($config && !$this->shouldGenerateApi($input, $config, $apiVersion)) {
            $output->writeln(sprintf(
                '<info>Not generating API for version %s.</info>',
                $apiVersion
            ));
            $output->writeln(sprintf(
                '<info>Config is at API version %s and generator version %s. </info>',
                $config->getApiVersion(),
                $config->getGeneratorVersion()
            ));

            return self::SUCCESS;
        }

        $output->writeln("<info>Fetching Open-API specification for API data...</info>");
        $gitContent = $this->githubClient->getBlob(
            self::GITHUB_OWNER,
            self::GITHUB_REPO,
            $tag,
            self::SWAGGER_SPEC_PATH
        );

        /** @var Swagger $openApi */
        try {
            $openApi = $this->serializer->deserialize($gitContent->getContent(), Swagger::class);
        } catch (Throwable $exception) {
            $output->writeln('<error>Unable to parse the OpenAPI specification:</error>');
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return self::FAILURE;
        }

        if (!$openApi instanceof Swagger) {
            $output->writeln('<error>Unable to parse the OpenAPI specification</error>');

            return self::FAILURE;
        }

        $codeOptions =  new CodeOptions(
            $tag->getCommonName(),
            $rootNamespace,
            $srcDir
        );
        $metadata = $this->metadataParser->parse($openApi);

        if (!$input->getOption('no-delete')) {
            $this->codeRemover->removeCode($output, $codeOptions);
        }

        $output->writeln(sprintf(
            "<info>Generating API data for version %s</info>",
            $tag->getCommonName()
        ));

        $this->codeGenerator->generateCode(
            $metadata,
            $codeOptions
        );

        if ($config) {
            $config->setApiVersion($apiVersion);
            $config->setGeneratorVersion($this->getAppVersion());
        } else {
            $config = $this->configManager->newConfig($apiVersion, $this->getAppVersion());
        }

        $this->configManager->write($config);

        $output->writeln("<info>Finished generating API data!</info>");

        return self::SUCCESS;
    }

    protected function getTagFromRepo(OutputInterface $output, ?string $version): GitTag
    {
        $output->writeln("<info>Fetching version tags for K8s...</info>");

        $gitTags = $this->githubClient->getTags(
            self::GITHUB_OWNER,
            self::GITHUB_REPO
        );

        return $gitTags->getLatestStableTag($version);
    }

    private function getAppVersion(): string
    {
        return $this->getApplication()->getVersion();
    }

    private function shouldGenerateApi(InputInterface $input, Configuration $config, string $apiVersion): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return version_compare($this->getAppVersion(), $config->getGeneratorVersion(), 'gt')
            || version_compare($apiVersion, $config->getApiVersion(), 'gt');
    }
}
