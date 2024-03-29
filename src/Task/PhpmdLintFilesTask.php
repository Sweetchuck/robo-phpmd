<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Sweetchuck\Robo\PhpMessDetector\Option\LintOptionTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PhpmdLintFilesTask extends PhpmdCliTask
{
    use LintOptionTrait;

    /**
     * {@inheritdoc}
     */
    protected string $taskName = 'PHP Mess Detector - Lint files';

    protected Filesystem $fileSystem;

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
    }

    public function setOptions(array $options): static
    {
        parent::setOptions($options);
        $this->setOptionsLint($options);

        return $this;
    }

    protected function runDoIt(): static
    {
        $this->prepareDirectoryReportFiles();

        return parent::runDoIt();
    }

    protected function prepareDirectoryReportFiles(): static
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

    protected function prepareDirectory(string $directory): static
    {
        if ($directory && !$this->fileSystem->exists($directory)) {
            $this->fileSystem->mkdir($directory, 0777 - umask());
        }

        return $this;
    }

    protected function getCommandOptions(): array
    {
        return $this->getCommandOptionsLint() + parent::getCommandOptions();
    }
}
