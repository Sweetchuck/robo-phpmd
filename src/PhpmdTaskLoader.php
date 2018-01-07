<?php

namespace Sweetchuck\Robo\PhpMessDetector;

use Robo\Collection\CollectionBuilder;

trait PhpmdTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskPhpmdLintFiles(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask $task */
        $task = $this->task(Task\PhpmdLintFilesTask::class);

        return $task->setOptions($options);
    }
}
