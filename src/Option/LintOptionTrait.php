<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Option;

use Sweetchuck\Robo\PhpMessDetector\Utils;

trait LintOptionTrait
{
    // region Options

    // region paths
    protected array $paths = [];

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setPaths(array $value): static
    {
        $this->paths = Utils::normalizeBooleanMap($value);

        return $this;
    }
    // endregion

    // region reportFormat
    protected string $reportFormat = 'text';

    public function getReportFormat(): string
    {
        return $this->reportFormat;
    }

    public function setReportFormat(string $value): static
    {
        $this->reportFormat = $value;

        return $this;
    }
    // endregion

    // region ruleSetFileNames
    protected array $ruleSetFileNames = [];

    public function getRuleSetFileNames(): array
    {
        return $this->ruleSetFileNames;
    }

    public function setRuleSetFileNames(array $value): static
    {
        $this->ruleSetFileNames = $value;

        return $this;
    }
    // endregion

    // region minimumPriority
    protected int $minimumPriority = 0;

    public function getMinimumPriority(): int
    {
        return $this->minimumPriority;
    }

    public function setMinimumPriority(int $value): static
    {
        $this->minimumPriority = $value;

        return $this;
    }
    // endregion

    // region inputFile
    protected string $inputFile = '';

    public function getInputFile(): string
    {
        return $this->inputFile;
    }

    public function setInputFile(string $value): static
    {
        $this->inputFile = $value;

        return $this;
    }
    // endregion

    // region coverage
    protected bool $coverage = false;

    public function getCoverage(): bool
    {
        return $this->coverage;
    }

    public function setCoverage(bool $value): static
    {
        $this->coverage = $value;

        return $this;
    }
    // endregion

    // region reportFile
    protected string $reportFile = '';

    public function getReportFile(): string
    {
        return $this->reportFile;
    }

    public function setReportFile(string $value): static
    {
        $this->reportFile = $value;

        return $this;
    }
    // endregion

    // region reportFileHtml
    protected string $reportFileHtml = '';

    public function getReportFileHtml(): string
    {
        return $this->reportFileHtml;
    }

    public function setReportFileHtml(string $value): static
    {
        $this->reportFileHtml = $value;

        return $this;
    }
    // endregion

    // region reportFileText
    protected string $reportFileText = '';

    public function getReportFileText(): string
    {
        return $this->reportFileText;
    }

    public function setReportFileText(string $value): static
    {
        $this->reportFileText = $value;

        return $this;
    }
    // endregion

    // region reportFileXml
    protected string $reportFileXml = '';

    public function getReportFileXml(): string
    {
        return $this->reportFileXml;
    }

    public function setReportFileXml(string $value): static
    {
        $this->reportFileXml = $value;

        return $this;
    }
    // endregion

    // region suffixes
    protected array $suffixes = [];

    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    public function setSuffixes(array $value): static
    {
        $this->suffixes = Utils::normalizeBooleanMap($value);

        return $this;
    }

    public function addSuffix(string $suffix): static
    {
        $this->suffixes[$suffix] = true;

        return $this;
    }

    public function removeSuffix(string $suffix): static
    {
        unset($this->suffixes[$suffix]);

        return $this;
    }
    // endregion

    // region excludePaths
    protected array $excludePaths = [];

    public function getExcludePaths(): array
    {
        return $this->excludePaths;
    }

    public function setExcludePaths(array $value): static
    {
        $this->excludePaths = Utils::normalizeBooleanMap($value);

        return $this;
    }

    public function addExcludePathsFromFile(string $fileName): static
    {
        $lines = array_map('trim', file($fileName));
        $this->excludePaths = array_fill_keys(array_filter($lines), true) + $this->excludePaths;

        return $this;
    }

    public function addExcludePath(string $path): static
    {
        $this->excludePaths[$path] = true;

        return $this;
    }

    public function removeExcludePath(string $path): static
    {
        unset($this->excludePaths[$path]);

        return $this;
    }
    // endregion

    // region strict
    protected bool $strict = false;

    public function getStrict(): bool
    {
        return $this->strict;
    }

    public function setStrict(bool $value): static
    {
        $this->strict = $value;

        return $this;
    }
    // endregion

    // region ignoreViolationsOnExit
    protected bool $ignoreViolationsOnExit = false;

    public function getIgnoreViolationsOnExit(): bool
    {
        return $this->ignoreViolationsOnExit;
    }

    public function setIgnoreViolationsOnExit(bool $value): static
    {
        $this->ignoreViolationsOnExit = $value;

        return $this;
    }
    // endregion

    // endregion

    protected function setOptionsLint(array $options): static
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
