<?php

namespace Sweetchuck\Robo\PhpMessDetector;

use League\Container\ContainerAwareInterface;
use Robo\Collection\CollectionBuilder;

trait PhpmdTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpmdVersion(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask $task */
        $task = $this->task(Task\PhpmdVersionTask::class);
        if ($this instanceof ContainerAwareInterface) {
            $container = $this->getContainer();
            if ($container) {
                $task->setContainer($this->getContainer());
            }
        }

        return $task->setOptions($options);
    }

    /**
     * @return \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpmdLintFiles(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask $task */
        $task = $this->task(Task\PhpmdLintFilesTask::class);
        if ($this instanceof ContainerAwareInterface) {
            $container = $this->getContainer();
            if ($container) {
                $task->setContainer($this->getContainer());
            }
        }

        return $task->setOptions($options);
    }

    /**
     * @return \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintInputTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpmdLintInput(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintInputTask $task */
        $task = $this->task(Task\PhpmdLintInputTask::class);

        return $task->setOptions($options);
    }
}
