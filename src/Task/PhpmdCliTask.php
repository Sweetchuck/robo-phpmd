<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\CommandInterface;
use Sweetchuck\Robo\PhpMessDetector\Utils;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Process\Process;

abstract class PhpmdCliTask extends PhpmdBaseTask implements
    CommandInterface,
    ContainerAwareInterface,
    OutputAwareInterface
{
    use ContainerAwareTrait;
    use OutputAwareTrait;

    protected string $command = '';

    protected string $processStdOutput = '';

    protected string $processStdError = '';

    protected int $processExitCode = 0;

    // region Options

    // region phpExecutable
    protected string $phpExecutable = '';

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
    protected string $phpmdExecutable = '';

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
            $optionNameCli = $option['name'] ?? mb_strtolower($optionName);
            switch ($option['type']) {
                case 'arg:value':
                case 'option:value':
                    if ($option['value']) {
                        $cmdPattern[] = $option['type'] === 'option:value' ? "--$optionNameCli %s" : '%s';
                        $cmdArgs[] = escapeshellarg((string) $option['value']);
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
        $process = $this
            ->getProcessHelper()
            ->run(
                $this->output(),
                [
                    'bash',
                    '-c',
                    $this->command,
                ],
                null,
                $this->getProcessRunCallbackWrapper()
            );

        $this->processExitCode = (int) $process->getExitCode();
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

    protected function getTaskResultCode(): int
    {
        return $this->processExitCode;
    }

    protected function getTaskResultMessage(): string
    {
        return $this->processStdError;
    }

    protected function getProcessRunCallbackWrapper(): callable
    {
        return function (string $type, string $data): void {
            $this->processRunCallback($type, $data);
        };
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

    protected function getProcessHelper(): ProcessHelper
    {
        // @todo Check that everything is available.
        return  $this
            ->getContainer()
            ->get('application')
            ->getHelperSet()
            ->get('process');
    }
}
