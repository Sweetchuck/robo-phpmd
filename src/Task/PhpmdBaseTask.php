<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Robo\Common\InflectionTrait;
use Robo\Contract\InflectionInterface;
use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;

abstract class PhpmdBaseTask extends RoboBaseTask implements
    InflectionInterface
{
    use InflectionTrait;

    /**
     * @abstract
     */
    protected string $taskName = 'Php Mess Detector';

    protected array $assets = [];

    // region Options

    // region Option - workingDirectory.
    protected string $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function setWorkingDirectory(string $value): static
    {
        $this->workingDirectory = $value;

        return $this;
    }
    // endregion

    // region Option - assetNamePrefix.
    protected string $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    public function setAssetNamePrefix(string $value): static
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion

    // endregion

    public function setOptions(array $options): static
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'assetNamePrefix':
                    $this->setAssetNamePrefix($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return $this
            ->runHeader()
            ->runDoIt()
            ->runPrepareAssets()
            ->runReturn();
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo('Running');

        return $this;
    }

    abstract protected function runDoIt(): static;

    protected function runPrepareAssets(): static
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

    abstract protected function getTaskResultCode(): int;

    abstract protected function getTaskResultMessage(): string;

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
            'assetNamePrefix' => [
                'type' => 'other',
                'value' => $this->getAssetNamePrefix(),
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
}
