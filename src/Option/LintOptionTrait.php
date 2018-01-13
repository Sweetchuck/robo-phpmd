<?php

namespace Sweetchuck\Robo\PhpMessDetector\Option;

use Sweetchuck\Robo\PhpMessDetector\Utils;

trait LintOptionTrait
{
    // region Options

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
    protected function setOptionsLint(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
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
    protected function getCommandOptionsLint(): array
    {
        return [
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
}
