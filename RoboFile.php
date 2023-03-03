<?php

declare(strict_types = 1);

use Consolidation\AnnotatedCommand\CommandData;
use League\Container\Container as LeagueContainer;
use NuvoleWeb\Robo\Task\Config\Robo\loadTasks as ConfigLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Sweetchuck\Robo\PhpMessDetector\PhpmdTaskLoader;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ConfigLoader;
    use GitTaskLoader;
    use PhpcsTaskLoader;
    use PhpmdTaskLoader;

    protected array $composerInfo = [];

    protected array $codeceptionInfo = [];

    /**
     * @var string[]
     */
    protected array $codeceptionSuiteNames = [];

    protected string $packageVendor = '';

    protected string $packageName = '';

    protected string $binDir = 'vendor/bin';

    protected string $gitHook = '';

    protected string $envVarNamePrefix = '';

    /**
     * Allowed values: dev, ci, prod.
     */
    protected string $environmentType = '';

    /**
     * Allowed values: local, jenkins, travis, circleci.
     */
    protected string $environmentName = '';

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        $this
            ->initComposerInfo()
            ->initEnvVarNamePrefix()
            ->initEnvironmentTypeAndName();
    }

    /**
     * @hook pre-command @initLintReporters
     */
    public function initLintReporters()
    {
        $container = $this->getContainer();
        if (!($container instanceof LeagueContainer)) {
            return;
        }

        foreach (BaseReporter::getServices() as $name => $class) {
            if ($container->has($name)) {
                continue;
            }

            $container
                ->add($name, $class)
                ->setShared(false);
        }
    }

    /**
     * Git "pre-commit" hook callback.
     *
     * @initLintReporters
     */
    public function githookPreCommit(): CollectionBuilder
    {
        $this->gitHook = 'pre-commit';

        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskCodeceptRunSuites());
    }

    /**
     * Run code style checkers.
     *
     * @initLintReporters
     */
    public function lint(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskPhpmdLint());
    }

    /**
     * @initLintReporters
     */
    public function lintPhpcs(): CollectionBuilder
    {
        return $this->getTaskPhpcsLint();
    }

    /**
     * @initLintReporters
     */
    public function lintPhpmd(): CollectionBuilder
    {
        return $this->getTaskPhpmdLint();
    }

    /**
     * @hook validate test
     */
    public function inputSuitNamesValidateOptionalArg(CommandData $commandData)
    {
        $args = $commandData->arguments();
        $this->validateArgCodeceptionSuiteNames($args['suiteNames']);
    }

    /**
     * Run the Robo unit tests.
     */
    public function test(
        array $suiteNames,
        array $options = [
            'debug' => false,
        ]
    ): CollectionBuilder {
        return $this->getTaskCodeceptRunSuites($suiteNames, $options);
    }

    protected function errorOutput(): ?OutputInterface
    {
        $output = $this->output();

        return ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;
    }

    protected function initEnvVarNamePrefix(): static
    {
        $this->envVarNamePrefix = strtoupper(str_replace('-', '_', $this->packageName));

        return $this;
    }

    protected function initEnvironmentTypeAndName(): static
    {
        $this->environmentType = getenv($this->getEnvVarName('environment_type')) ?: '';
        $this->environmentName = getenv($this->getEnvVarName('environment_name')) ?: '';

        if (!$this->environmentType) {
            if (getenv('CI') === 'true') {
                // Travis, GitLab and CircleCI.
                $this->environmentType = 'ci';
            } elseif (getenv('JENKINS_HOME')) {
                $this->environmentType = 'ci';
                if (!$this->environmentName) {
                    $this->environmentName = 'jenkins';
                }
            }
        }

        if (!$this->environmentName && $this->environmentType === 'ci') {
            if (getenv('GITLAB_CI') === 'true') {
                $this->environmentName = 'gitlab';
            } elseif (getenv('TRAVIS') === 'true') {
                $this->environmentName = 'travis';
            } elseif (getenv('CIRCLECI') === 'true') {
                $this->environmentName = 'circleci';
            }
        }

        if (!$this->environmentType) {
            $this->environmentType = 'dev';
        }

        if (!$this->environmentName) {
            $this->environmentName = 'local';
        }

        return $this;
    }

    protected function getEnvVarName(string $name): string
    {
        return "{$this->envVarNamePrefix}_" . strtoupper($name);
    }

    protected function initComposerInfo(): static
    {
        if ($this->composerInfo) {
            return $this;
        }

        $composerFile = getenv('COMPOSER') ?: 'composer.json';
        $composerContent = file_get_contents($composerFile);
        if ($composerContent === false) {
            return $this;
        }

        $this->composerInfo = json_decode($composerContent, true);
        [$this->packageVendor, $this->packageName] = explode('/', $this->composerInfo['name']);

        if (!empty($this->composerInfo['config']['bin-dir'])) {
            $this->binDir = $this->composerInfo['config']['bin-dir'];
        }

        return $this;
    }

    protected function initCodeceptionInfo(): static
    {
        if ($this->codeceptionInfo) {
            return $this;
        }

        $default = [
            'paths' => [
                'tests' => 'tests',
                'envs' => 'tests/_envs',
                'log' => 'tests/_log',
                'output' => 'tests/_log',
            ],
        ];
        $dist = is_readable('codeception.dist.yml') ?
            Yaml::parse(file_get_contents('codeception.dist.yml') ?: '{}')
            : [];
        $local = is_readable('codeception.yml') ?
            Yaml::parse(file_get_contents('codeception.yml') ?: '{}')
            : [];

        $this->codeceptionInfo = array_replace_recursive($default, $dist, $local);

        return $this;
    }

    protected function getTaskCodeceptRunSuites(array $suiteNames = [], array $options = []): CollectionBuilder
    {
        if (!$suiteNames) {
            $suiteNames = ['all'];
        }

        $phpExecutables = $this->getEnabledPhpExecutables();
        $cb = $this->collectionBuilder();
        foreach ($suiteNames as $suiteName) {
            foreach ($phpExecutables as $phpExecutable) {
                $cb->addTask($this->getTaskCodeceptRunSuite($suiteName, $phpExecutable));
            }
        }

        return $cb;
    }

    protected function getTaskCodeceptRunSuite(string $suite, array $php): CollectionBuilder
    {
        $this->initCodeceptionInfo();

        $withCoverageHtml = $this->environmentType === 'dev';
        $withCoverageXml = $this->environmentType === 'ci';

        $withUnitReportHtml = $this->environmentType === 'dev';
        $withUnitReportXml = $this->environmentType === 'ci';

        $logDir = $this->getLogDir();

        $cmdPattern = '';
        $cmdArgs = [];
        foreach ($php['envVars'] ?? [] as $envName => $envValue) {
            $cmdPattern .= "{$envName}";
            if ($envValue === null) {
                $cmdPattern .= ' ';
            } else {
                $cmdPattern .= '=%s ';
                $cmdArgs[] = escapeshellarg($envValue);
            }
        }

        $cmdPattern .= '%s';
        $cmdArgs[] = $php['command'];

        $cmdPattern .= ' %s';
        $cmdArgs[] = escapeshellcmd("{$this->binDir}/codecept");

        $cmdPattern .= ' --ansi';
        $cmdPattern .= ' --verbose';
        $cmdPattern .= ' --debug';

        $cb = $this->collectionBuilder();
        if ($withCoverageHtml) {
            $cmdPattern .= ' --coverage-html=%s';
            $cmdArgs[] = escapeshellarg("human/coverage/$suite/html");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/human/coverage/$suite")
            );
        }

        if ($withCoverageXml) {
            $cmdPattern .= ' --coverage-xml=%s';
            $cmdArgs[] = escapeshellarg("machine/coverage/$suite/coverage.xml");
        }

        if ($withCoverageHtml || $withCoverageXml) {
            $cmdPattern .= ' --coverage=%s';
            $cmdArgs[] = escapeshellarg("machine/coverage/$suite/coverage.serialized");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/machine/coverage/$suite")
            );
        }

        if ($withUnitReportHtml) {
            $cmdPattern .= ' --html=%s';
            $cmdArgs[] = escapeshellarg("human/junit/junit.$suite.html");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/human/junit")
            );
        }

        if ($withUnitReportXml) {
            $cmdPattern .= ' --xml=%s';
            $cmdArgs[] = escapeshellarg("machine/junit/junit.$suite.xml");

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir("$logDir/machine/junit")
            );
        }

        $cmdPattern .= ' run';
        if ($suite !== 'all') {
            $cmdPattern .= ' %s';
            $cmdArgs[] = escapeshellarg($suite);
        }

        $envDir = $this->codeceptionInfo['paths']['envs'];
        $envFileName = "{$this->environmentType}.{$this->environmentName}";
        if (file_exists("$envDir/$envFileName.yml")) {
            $cmdPattern .= ' --env %s';
            $cmdArgs[] = escapeshellarg($envFileName);
        }

        if ($this->environmentType === 'ci' && $this->environmentName === 'jenkins') {
            // Jenkins has to use a post-build action to mark the build "unstable".
            $cmdPattern .= ' || [[ "${?}" == "1" ]]';
        }

        $command = vsprintf($cmdPattern, $cmdArgs);

        return $cb
            ->addCode(function () use ($command, $php) {
                $this->output()->writeln(strtr(
                    '<question>[{name}]</question> runs <info>{command}</info>',
                    [
                        '{name}' => 'Codeception',
                        '{command}' => $command,
                    ]
                ));

                $process = Process::fromShellCommandline(
                    $command,
                    null,
                    $php['envVars'] ?? null,
                    null,
                    null,
                );

                return $process->run(function ($type, $data) {
                    switch ($type) {
                        case Process::OUT:
                            $this->output()->write($data);
                            break;

                        case Process::ERR:
                            $this->errorOutput()->write($data);
                            break;
                    }
                });
            });
    }

    protected function getTaskPhpcsLint(): CollectionBuilder
    {
        $options = [
            'failOn' => 'warning',
            'lintReporters' => [
                'lintVerboseReporter' => null,
            ],
        ];

        if ($this->environmentType === 'ci' && $this->environmentName === 'jenkins') {
            $options['failOn'] = 'never';
            $options['lintReporters']['lintCheckstyleReporter'] = $this
                ->getContainer()
                ->get('lintCheckstyleReporter')
                ->setDestination('tests/_log/machine/checkstyle/phpcs.psr2.xml');
        }

        if ($this->gitHook === 'pre-commit') {
            $phpcsTask = $this->taskPhpcsLintInput($options);
            $phpcsTask->setOutput($this->output());
            $phpcsTask
                ->deferTaskConfiguration('setFiles', 'files')
                ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns');

            return $this
                ->collectionBuilder()
                ->addTask($this
                    ->taskPhpcsParseXml()
                    ->setAssetNamePrefix('phpcsXml.'))
                ->addTask($this
                    ->taskGitListStagedFiles()
                    ->setPaths(['*.php']))
                ->addTask($this
                    ->taskGitReadStagedFiles()
                    ->setCommandOnly(true)
                    ->deferTaskConfiguration('setPaths', 'fileNames'))
                ->addTask($phpcsTask);
        }

        return $this->taskPhpcsLintFiles($options);
    }

    protected function getTaskPhpmdLint()
    {
        $task = $this
            ->taskPhpmdLintFiles()
            ->setInputFile('./rulesets/custom.include-pattern.txt')
            ->addExcludePathsFromFile('./rulesets/custom.exclude-pattern.txt')
            ->setRuleSetFileNames(['custom']);
        $task->setOutput($this->output());

        return $task;
    }

    protected function getLogDir(): string
    {
        $this->initCodeceptionInfo();

        return !empty($this->codeceptionInfo['paths']['log']) ?
            $this->codeceptionInfo['paths']['log']
            : 'tests/_log';
    }

    protected function getCodeceptionSuiteNames(): array
    {
        if (!$this->codeceptionSuiteNames) {
            $this->initCodeceptionInfo();

            $suiteFiles = Finder::create()
                ->in($this->codeceptionInfo['paths']['tests'])
                ->files()
                ->name('*.suite.yml')
                ->name('*.suite.dist.yml')
                ->depth(0);

            foreach ($suiteFiles as $suiteFile) {
                $this->codeceptionSuiteNames[] = preg_replace(
                    '/\.suite(\.dist)?\.yml$/',
                    '',
                    $suiteFile->getBasename()
                );
            }
        }

        return $this->codeceptionSuiteNames;
    }

    protected function validateArgCodeceptionSuiteNames(array $suiteNames): static
    {
        $invalidSuiteNames = array_diff($suiteNames, $this->getCodeceptionSuiteNames());
        if ($invalidSuiteNames) {
            throw new InvalidArgumentException(
                'The following Codeception suite names are invalid: ' . implode(', ', $invalidSuiteNames),
                1
            );
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEnabledPhpExecutables(): array
    {
        return array_filter(
            $this->getConfig()->get('php.executables'),
            new ArrayFilterEnabled(),
        );
    }
}
