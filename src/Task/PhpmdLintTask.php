<?php

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Robo\Common\InflectionTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Contract\InflectionInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;
use Stringy\StaticStringy;
use Sweetchuck\Robo\PhpMessDetector\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

abstract class PhpmdLintTask extends RoboBaseTask implements
    CommandInterface,
    OutputAwareInterface,
    InflectionInterface
{
    use OutputAwareTrait;
    use InflectionTrait;

    /**
     * @var string
     */
    protected $taskName = 'PHP Mess Detector';

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var string
     */
    protected $processClass = Process::class;

    /**
     * @var int
     */
    protected $processExitCode = 0;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fileSystem;

    /**
     * @var string
     */
    protected $processStdOutput = '';

    /**
     * @var string
     */
    protected $processStdError = '';

    /**
     * @var array
     */
    protected $assets = [];

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
    }

    // region Options

    // region assetNamePrefix.
    /**
     * @var string
     */
    protected $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    /**
     * @return $this
     */
    public function setAssetNamePrefix(string $value)
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion

    // region workingDirectory.
    /**
     * @var string
     */
    protected $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return $this
     */
    public function setWorkingDirectory(string $value)
    {
        $this->workingDirectory = $value;

        return $this;
    }
    // endregion

    // region phpExecutable
    /**
     * @var string
     */
    protected $phpExecutable = '';

    public function getPhpExecutable(): string
    {
        return $this->phpExecutable;
    }

    /**
     * @return $this
     */
    public function setPhpExecutable(string $value)
    {
        $this->phpExecutable = $value;

        return $this;
    }
    // endregion

    // region phpmdExecutable.
    /**
     * @var string
     */
    protected $phpmdExecutable = '';

    public function getPhpmdExecutable(): string
    {
        return $this->phpmdExecutable;
    }

    /**
     * @return $this
     */
    public function setPhpmdExecutable(string $value)
    {
        $this->phpmdExecutable = $value;

        return $this;
    }
    // endregion

    // region paths
    /**
     * @var array
     */
    protected $paths = [];

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return $this
     */
    public function setPaths(array $value)
    {
        $this->paths = Utils::normalizeBooleanMap($value);

        return $this;
    }
    // endregion

    // region reportFormat
    /**
     * @var string
     */
    protected $reportFormat = 'text';

    public function getReportFormat(): string
    {
        return $this->reportFormat;
    }

    /**
     * @return $this
     */
    public function setReportFormat(string $value)
    {
        $this->reportFormat = $value;

        return $this;
    }
    // endregion

    // region ruleSetFileNames
    /**
     * @var array
     */
    protected $ruleSetFileNames = [];

    public function getRuleSetFileNames(): array
    {
        return $this->ruleSetFileNames;
    }

    /**
     * @return $this
     */
    public function setRuleSetFileNames(array $value)
    {
        $this->ruleSetFileNames = $value;

        return $this;
    }
    // endregion

    // region minimumPriority
    /**
     * @var int
     */
    protected $minimumPriority = 0;

    public function getMinimumPriority(): int
    {
        return $this->minimumPriority;
    }

    /**
     * @return $this
     */
    public function setMinimumPriority(int $value)
    {
        $this->minimumPriority = $value;

        return $this;
    }
    // endregion

    // region inputFile
    /**
     * @var string
     */
    protected $inputFile = '';

    public function getInputFile(): string
    {
        return $this->inputFile;
    }

    /**
     * @return $this
     */
    public function setInputFile(string $value)
    {
        $this->inputFile = $value;

        return $this;
    }
    // endregion

    // region coverage
    /**
     * @var string
     */
    protected $coverage = '';

    public function getCoverage(): string
    {
        return $this->coverage;
    }

    /**
     * @return $this
     */
    public function setCoverage(string $value)
    {
        $this->coverage = $value;

        return $this;
    }
    // endregion

    // region reportFile
    /**
     * @var string
     */
    protected $reportFile = '';

    public function getReportFile(): string
    {
        return $this->reportFile;
    }

    /**
     * @return $this
     */
    public function setReportFile(string $value)
    {
        $this->reportFile = $value;

        return $this;
    }
    // endregion

    // region reportFileHtml
    /**
     * @var string
     */
    protected $reportFileHtml = '';

    public function getReportFileHtml(): string
    {
        return $this->reportFileHtml;
    }

    /**
     * @return $this
     */
    public function setReportFileHtml(string $value)
    {
        $this->reportFileHtml = $value;

        return $this;
    }
    // endregion

    // region reportFileText
    /**
     * @var string
     */
    protected $reportFileText = '';

    public function getReportFileText(): string
    {
        return $this->reportFileText;
    }

    /**
     * @return $this
     */
    public function setReportFileText(string $value)
    {
        $this->reportFileText = $value;

        return $this;
    }
    // endregion

    // region reportFileXml
    /**
     * @var string
     */
    protected $reportFileXml = '';

    public function getReportFileXml(): string
    {
        return $this->reportFileXml;
    }

    /**
     * @return $this
     */
    public function setReportFileXml(string $value)
    {
        $this->reportFileXml = $value;

        return $this;
    }
    // endregion

    // region suffixes
    /**
     * @var array
     */
    protected $suffixes = [];

    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    /**
     * @return $this
     */
    public function setSuffixes(array $value)
    {
        $this->suffixes = Utils::normalizeBooleanMap($value);

        return $this;
    }

    public function addSuffix(string $suffix)
    {
        $this->suffixes[$suffix] = true;

        return $this;
    }

    public function removeSuffix(string $suffix)
    {
        unset($this->suffixes[$suffix]);

        return $this;
    }
    // endregion

    // region excludePaths
    /**
     * @var array
     */
    protected $excludePaths = [];

    public function getExcludePaths(): array
    {
        return $this->excludePaths;
    }

    /**
     * @return $this
     */
    public function setExcludePaths(array $value)
    {
        $this->excludePaths = Utils::normalizeBooleanMap($value);

        return $this;
    }

    /**
     * @return $this
     */
    public function addExcludePathsFromFile(string $fileName)
    {
        $lines = array_map('trim', file($fileName));
        $this->excludePaths = array_fill_keys(array_filter($lines), true) + $this->excludePaths;

        return $this;
    }

    /**
     * @return $this
     */
    public function addExcludePath(string $path)
    {
        $this->excludePaths[$path] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeExcludePath(string $path)
    {
        unset($this->excludePaths[$path]);

        return $this;
    }
    // endregion

    // region strict
    /**
     * @var bool
     */
    protected $strict = false;

    public function getStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @return $this
     */
    public function setStrict(bool $value)
    {
        $this->strict = $value;

        return $this;
    }
    // endregion

    // region ignoreViolationsOnExit
    /**
     * @var bool
     */
    protected $ignoreViolationsOnExit = false;

    public function getIgnoreViolationsOnExit(): bool
    {
        return $this->ignoreViolationsOnExit;
    }

    /**
     * @return $this
     */
    public function setIgnoreViolationsOnExit(bool $value)
    {
        $this->ignoreViolationsOnExit = $value;

        return $this;
    }
    // endregion

    // endregion

    /**
     * @return $this
     */
    public function setOptions(array $option)
    {
        foreach ($option as $name => $value) {
            switch ($name) {
                case 'assetNamePrefix':
                    $this->setAssetNamePrefix($value);
                    break;

                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'phpExecutable':
                    $this->setPhpExecutable($value);
                    break;

                case 'phpmdExecutable':
                    $this->setPhpmdExecutable($value);
                    break;

                case 'paths':
                    $this->setPaths($value);
                    break;

                case 'reportFormat':
                    $this->setReportFormat($value);
                    break;

                case 'ruleSetFileNames':
                    $this->setRuleSetFileNames($value);
                    break;

                case 'minimumPriority':
                    $this->setMinimumPriority($value);
                    break;

                case 'inputFile':
                    $this->setInputFile($value);
                    break;

                case 'coverage':
                    $this->setCoverage($value);
                    break;

                case 'reportFile':
                    $this->setReportFile($value);
                    break;

                case 'reportFileHtml':
                    $this->setReportFileHtml($value);
                    break;

                case 'reportFileText':
                    $this->setReportFileText($value);
                    break;

                case 'reportFileXml':
                    $this->setReportFileXml($value);
                    break;

                case 'suffixes':
                    $this->setSuffixes($value);
                    break;

                case 'excludePaths':
                    $this->setExcludePaths($value);
                    break;

                case 'strict':
                    $this->setStrict($value);
                    break;

                case 'ignoreViolationsOnExit':
                    $this->setIgnoreViolationsOnExit($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        $cmdPattern = [];
        $cmdArgs = [];

        $commandOptions = $this->getCommandOptions();

        $workingDirectory = $this->getWorkingDirectory();

        if (!empty($commandOptions['phpExecutable']['value'])) {
            $cmdPattern[] = '%s';
            $cmdArgs[] = escapeshellcmd($commandOptions['phpExecutable']['value']);
        }

        $cmdPattern[] = '%s';
        $cmdArgs[] = escapeshellcmd($commandOptions['phpmdExecutable']['value']);

        foreach ($commandOptions as $optionName => $option) {
            $optionNameCli = $option['name'] ?? StaticStringy::toLowerCase($optionName);
            switch ($option['type']) {
                case 'arg:value':
                case 'option:value':
                    if ($option['value']) {
                        $cmdPattern[] = $option['type'] === 'option:value' ? "--$optionNameCli %s" : '%s';
                        $cmdArgs[] = escapeshellarg($option['value']);
                    }
                    break;

                case 'option:flag':
                    if ($option['value']) {
                        $cmdPattern[] = "--$optionNameCli";
                    }
                    break;

                case 'arg:list':
                case 'option:list':
                    $items = Utils::filterEnabled($option['value']);
                    if ($items) {
                        $cmdPattern[] = $option['type'] === 'option:list' ? "--$optionNameCli %s" : '%s';
                        $cmdArgs[] = escapeshellarg(implode($option['separator'] ?? ',', $items));
                    }
                    break;
            }
        }

        $chDir = $workingDirectory ? sprintf('cd %s &&', escapeshellarg($workingDirectory)) : '';
        $cmd = vsprintf(implode(' ', $cmdPattern), $cmdArgs);

        return implode(' ', array_filter([$chDir, $cmd]));
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->command = $this->getCommand();

        return $this
            ->runHeader()
            ->runDoIt()
            ->runProcessOutputs()
            ->runReturn();
    }

    /**
     * @return $this
     */
    protected function runHeader()
    {
        $this->printTaskInfo($this->command);

        return $this;
    }

    /**
     * @return $this
     */
    protected function runDoIt()
    {
        $this->prepareDirectoryReportFiles();

        /** @var \Symfony\Component\Process\Process $process */
        $process = new $this->processClass($this->command);
        $this->processExitCode = $process->run(function ($type, $data) {
            $this->processRunCallback($type, $data);
        });
        $this->processStdOutput = $process->getOutput();
        $this->processStdError = $process->getErrorOutput();

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareDirectoryReportFiles()
    {
        $fileNames = [
            $this->getReportFile(),
            $this->getReportFileHtml(),
            $this->getReportFileText(),
            $this->getReportFileXml(),
        ];

        $workingDirectory = $this->getWorkingDirectory() ?: '.';
        foreach ($fileNames as $fileName) {
            if (!$fileName) {
                continue;
            }

            if (Path::isRelative($fileName)) {
                $fileName = Path::join($workingDirectory, $fileName);
            }

            $this->prepareDirectory(Path::getDirectory($fileName));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareDirectory(string $directory)
    {
        if ($directory && !$this->fileSystem->exists($directory)) {
            $this->fileSystem->mkdir($directory, 0777 - umask());
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runProcessOutputs()
    {
        return $this;
    }

    protected function runReturn(): Result
    {
        return new Result(
            $this,
            $this->getTaskResultCode(),
            $this->getTaskResultMessage(),
            $this->getAssetsWithPrefixedNames()
        );
    }

    protected function processRunCallback(string $type, string $data): void
    {
        switch ($type) {
            case Process::OUT:
                $this->output()->write($data);
                break;

            case Process::ERR:
                $this->printTaskError($data);
                break;
        }
    }

    protected function getTaskResultCode(): int
    {
        return $this->processExitCode;
    }

    protected function getTaskResultMessage(): string
    {
        return $this->processStdError;
    }

    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $assets = [];
        foreach ($this->assets as $key => $value) {
            $assets["{$prefix}{$key}"] = $value;
        }

        return $assets;
    }

    protected function getCommandOptions(): array
    {
        return [
            'workingDirectory' => [
                'type' => 'other',
                'value' => $this->getWorkingDirectory(),
            ],
            'phpExecutable' => [
                'type' => 'other',
                'value' => $this->getPhpExecutable(),
            ],
            'phpmdExecutable' => [
                'type' => 'other',
                'value' => $this->getPhpmdExecutable() ?: $this->findPhpmdExecutable(),
            ],
            'paths' => [
                'type' => 'arg:list',
                'value' => $this->getPaths(),
            ],
            'reportFormat' => [
                'type' => 'arg:value',
                'value' => $this->getReportFormat(),
            ],
            'ruleSetFileNames' => [
                'type' => 'arg:list',
                'value' => $this->getRuleSetFileNames(),
            ],
            'minimumPriority' => [
                'type' => 'option:value',
                'value' => $this->getMinimumPriority(),
            ],
            'inputFile' => [
                'type' => 'option:value',
                'value' => $this->getInputFile(),
            ],
            'coverage' => [
                'type' => 'option:flag',
                'value' => $this->getCoverage(),
            ],
            'reportFile' => [
                'type' => 'option:value',
                'value' => $this->getReportFile(),
            ],
            'reportFileHtml' => [
                'type' => 'option:value',
                'name' => 'reportfile-html',
                'value' => $this->getReportFileHtml(),
            ],
            'reportFileText' => [
                'type' => 'option:value',
                'name' => 'reportfile-text',
                'value' => $this->getReportFileText(),
            ],
            'reportFileXml' => [
                'type' => 'option:value',
                'name' => 'reportfile-xml',
                'value' => $this->getReportFileXml(),
            ],
            'suffixes' => [
                'type' => 'option:list',
                'value' => $this->getSuffixes(),
            ],
            'excludePaths' => [
                'type' => 'option:list',
                'name' => 'exclude',
                'value' => $this->getExcludePaths(),
            ],
            'strict' => [
                'type' => 'option:flag',
                'value' => $this->getStrict(),
            ],
            'ignoreViolationsOnExit' => [
                'type' => 'option:flag',
                'name' => 'ignore-violations-on-exit',
                'value' => $this->getIgnoreViolationsOnExit(),
            ],
        ];
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        if (!$context) {
            $context = [];
        }

        if (empty($context['name'])) {
            $context['name'] = $this->getTaskName();
        }

        return parent::getTaskContext($context);
    }

    protected function findPhpmdExecutable(): string
    {
        $suggestions = [
            dirname($_SERVER['argv'][0]) . '/phpmd',
            'vendor/bin/phpmd',
            'bin/phpmd',
        ];

        foreach ($suggestions as $suggestion) {
            if (is_executable($suggestion)) {
                return $suggestion;
            }
        }

        return 'phpmd';
    }
}
