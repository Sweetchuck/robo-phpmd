<?php

namespace Sweetchuck\Robo\PhpMessDetector\Task;

class PhpmdVersionTask extends PhpmdCliTask
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'PHP Mess Detector - Version';

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(): array
    {
        return [
            'version' => [
                'type' => 'option:flag',
                'value' => true,
            ],
        ] + parent::getCommandOptions();
    }

    /**
     * {@inheritdoc}
     */
    protected function runPrepareAssets()
    {
        $parts = explode(' ', $this->processStdOutput) + [1 => ''];
        $this->assets['version'] = trim($parts[1]);

        return $this;
    }
}
