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
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

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
        $lintServices = BaseReporter::getServices();
        $container = $this->getContainer();
        foreach ($lintServices as $name => $class) {
            if ($container->has($name)) {
                continue;
            }

            if ($container instanceof LeagueContainer) {
                $container->share($name, $class);
            }
        }
    }

    /**
     * Git "pre-commit" hook callback.
     *
     * @command githook:pre-commit
     *
     * @hidden
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
     * @hook validate test
     */
    public function inputSuitNamesValidateOptionalArg(CommandData $commandData)
    {
        $args = $commandData->arguments();
        $this->validateArgCodeceptionSuiteNames($args['suiteNames']);
    }

    /**
     * Run the Robo unit tests.
     *
     * @command test
     */
    public function test(
        array $suiteNames,
        array $options = [
            'debug' => false,
        ]
    ): CollectionBuilder {
        return $this->getTaskCodeceptRunSuites($suiteNames, $options);
    }

    /**
     * Run code style checkers.
     *
     * @command lint
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
     * @command lint:phpcs
     */
    public function lintPhpcs(): CollectionBuilder
    {
        return $this->getTaskPhpcsLint();
    }

    /**
     * @command lint:phpmd
     */
    public function lintPhpmd(): CollectionBuilder
    {
        return $this->getTaskPhpmdLint();
    }

    protected function errorOutput(): ?OutputInterface
    {
        $output = $this->output();

        return ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;
    }

    /**
     * @return $this
     */
    protected function initEnvVarNamePrefix()
    {
        $this->envVarNamePrefix = strtoupper(str_replace('-', '_', $this->packageName));

        return $this;
    }

    /**
     * @return $this
     */
    protected function initEnvironmentTypeAndName()
    {
        $this->environmentType = (string) getenv($this->getEnvVarName('environment_type'));
        $this->environmentName = (string) getenv($this->getEnvVarName('environment_name'));

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

    protected function getPhpExecutable(): string
    {
        return getenv($this->getEnvVarName('php_executable')) ?: PHP_BINARY;
    }

    protected function getPhpdbgExecutable(): string
    {
        return getenv($this->getEnvVarName('phpdbg_executable')) ?: Path::join(PHP_BINDIR, 'phpdbg');
    }

    /**
     * @return $this
     */
    protected function initComposerInfo()
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

    /**
     * @return $this
     */
    protected function initCodeceptionInfo()
    {
        if ($this->codeceptionInfo) {
            return $this;
        }

        $default = [
            'paths' => [
                'tests' => 'tests',
                'output' => 'tests/_output',
            ],
        ];
        $dist = [];
        $local = [];

        if (is_readable('codeception.dist.yml')) {
            $dist = Yaml::parse(file_get_contents('codeception.dist.yml'));
        }

        if (is_readable('codeception.yml')) {
            $local = Yaml::parse(file_get_contents('codeception.yml'));
        }

        $this->codeceptionInfo = array_replace_recursive($default, $dist, $local);

        return $this;
    }

    protected function getTaskCodeceptRunSuites(array $suiteNames = [], array $options = []): CollectionBuilder
    {
        if (!$suiteNames) {
            $suiteNames = ['all'];
        }

        $cb = $this->collectionBuilder();
        foreach ($suiteNames as $suiteName) {
            $cb->addTask($this->getTaskCodeceptRunSuite($suiteName, $options));
        }

        return $cb;
    }

    protected function getTaskCodeceptRunSuite(string $suite, array $options = []): CollectionBuilder
    {
        $php = $this->getPhpExecutableWithCoverage();
        $envVars = [
            'COLUMNS' => getenv('COLUMNS') ?: '80',
        ];
        $envVars += $php['envVar'];

        $this->initCodeceptionInfo();

        $withCoverageHtml = $this->environmentType === 'dev';
        $withCoverageXml = $this->environmentType === 'ci';

        $withUnitReportHtml = $this->environmentType === 'dev';
        $withUnitReportXml = $this->environmentType === 'ci';

        $logDir = $this->getLogDir();

        $cmdArgs = [];
        if ($this->isPhpDbgAvailable()) {
            $cmdPattern = '%s -qrr';
            $cmdArgs[] = escapeshellcmd($this->getPhpdbgExecutable());
        } else {
            $cmdPattern = '%s';
            $cmdArgs[] = escapeshellcmd($this->getPhpExecutable());
        }

        $cmdPattern .= ' %s';
        $cmdArgs[] = escapeshellcmd("{$this->binDir}/codecept");

        $cmdPattern .= ' --ansi';
        $cmdPattern .= ' --verbose';
        if (!empty($options['debug'])) {
            $cmdPattern .= ' --debug';
        }

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
            $cmdArgs[] = escapeshellarg("machine/coverage/$suite/coverage.php");

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
            $jUnitFilePath = "machine/junit/$suite/junit.$suite.xml";
            $dirToCreate = Path::getDirectory("$logDir/$jUnitFilePath");

            $cmdPattern .= ' --xml=%s';
            $cmdArgs[] = escapeshellarg($jUnitFilePath);

            $cb->addTask(
                $this
                    ->taskFilesystemStack()
                    ->mkdir($dirToCreate)
            );
        }

        $cmdPattern .= ' run';
        if ($suite !== 'all') {
            $cmdPattern .= ' %s';
            $cmdArgs[] = escapeshellarg($suite);
        }

        if ($this->environmentType === 'ci' && $this->environmentName === 'jenkins') {
            // Jenkins has to use a post-build action to mark the build "unstable".
            $cmdPattern .= ' || [[ "${?}" == "1" ]]';
        }

        $command = [
            'bash',
            '-c',
            vsprintf($cmdPattern, $cmdArgs),
        ];

        return $cb
            ->addCode(function () use ($envVars, $command) {
                $this->output()->writeln(strtr(
                    '<question>[{name}]</question> runs <info>{command}</info>',
                    [
                        '{name}' => 'Codeception',
                        '{command}' => implode(' ', $command),
                    ]
                ));
                $process = new Process(
                    $command,
                    null,
                    $envVars,
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
                ->setDestination('tests/_output/machine/checkstyle/phpcs.psr2.xml');
        }

        if ($this->gitHook === 'pre-commit') {
            return $this
                ->collectionBuilder()
                ->addTask($this
                    ->taskPhpcsParseXml()
                    ->setAssetNamePrefix('phpcsXml.'))
                ->addTask($this
                    ->taskGitReadStagedFiles()
                    ->setCommandOnly(true)
                    ->setWorkingDirectory('.')
                    ->deferTaskConfiguration('setPaths', 'phpcsXml.files'))
                ->addTask($this
                    ->taskPhpcsLintInput($options)
                    ->deferTaskConfiguration('setFiles', 'files')
                    ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
        }

        return $this->taskPhpcsLintFiles($options);
    }

    protected function getTaskPhpmdLint()
    {
        return $this
            ->taskPhpmdLintFiles()
            ->setInputFile('./rulesets/custom.include-pattern.txt')
            ->addExcludePathsFromFile('./rulesets/custom.exclude-pattern.txt')
            ->setRuleSetFileNames(['custom'])
            ->setOutput($this->output());
    }

    protected function isPhpDbgAvailable(): bool
    {
        return (new Process([$this->getPhpdbgExecutable(), '-qrr']))->run() === 0;
    }

    protected function getLogDir(): string
    {
        $this->initCodeceptionInfo();

        return !empty($this->codeceptionInfo['paths']['log']) ?
            $this->codeceptionInfo['paths']['log']
            : 'tests/_output';
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

    /**
     * @return $this
     */
    protected function validateArgCodeceptionSuiteNames(array $suiteNames)
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

    protected function getPhpExecutableWithCoverage(): array
    {
        $default = [
            'available' => true,
            'envVar' => [],
            'command' => 'php',
        ];
        foreach ($this->getConfig()->get('php.executable') as $php) {
            if (!empty($php['available'])) {
                return $php + $default;
            }
        }

        return $default;
    }
}
