<?php

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Contract\OutputAwareInterface;
use Stringy\StaticStringy;
use Sweetchuck\Robo\PhpMessDetector\Utils;
use Symfony\Component\Process\Process;

abstract class PhpmdCliTask extends PhpmdBaseTask implements
    CommandInterface,
    OutputAwareInterface
{
    use OutputAwareTrait;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var string
     */
    protected $processClass = Process::class;

    /**
     * @var string
     */
    protected $processStdOutput = '';

    /**
     * @var string
     */
    protected $processStdError = '';

    /**
     * @var int
     */
    protected $processExitCode = 0;

    // region Options

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

    // endregion

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (isset($options['phpExecutable'])) {
            $this->setPhpExecutable($options['phpExecutable']);
        }

        if (isset($options['phpmdExecutable'])) {
            $this->setPhpmdExecutable($options['phpmdExecutable']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(): array
    {
        return [
            'phpExecutable' => [
                'type' => 'other',
                'value' => $this->getPhpExecutable(),
            ],
            'phpmdExecutable' => [
                'type' => 'other',
                'value' => $this->getPhpmdExecutable() ?: $this->findPhpmdExecutable(),
            ],
        ] + parent::getCommandOptions();
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

        return parent::run();
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $this->printTaskInfo($this->command);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
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
     * {@inheritdoc}
     */
    protected function runPrepareAssets()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskResultCode(): int
    {
        return $this->processExitCode;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskResultMessage(): string
    {
        return $this->processStdError;
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

    protected function findPhpmdExecutable(): string
    {
        $suggestions = [
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
