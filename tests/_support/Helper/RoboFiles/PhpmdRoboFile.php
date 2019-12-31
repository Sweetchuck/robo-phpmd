<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Test\Helper\RoboFiles;

use Robo\State\Data;
use Robo\Tasks;
use Sweetchuck\Robo\PhpMessDetector\PhpmdTaskLoader;
use Robo\Contract\TaskInterface;

class PhpmdRoboFile extends Tasks
{
    use PhpmdTaskLoader;

    public function phpmdVersion(): TaskInterface
    {
        return $this
            ->collectionBuilder()
            ->addTask($this->taskPhpmdVersion())
            ->addCode(function (Data $data) {
                $this
                    ->output()
                    ->writeln("The version of the Php Mess Detector is: '{$data['version']}'");
            });
    }

    public function phpmdLintFiles(
        string $paths = '',
        string $reportFormat = '',
        string $ruleSetNames = '',
        array $options = [
            'workingDirectory' => '',
            'phpExecutable' => '',
            'phpmdExecutable' => '../../../bin/phpmd',
            'minimumPriority' => 0,
            'reportFile' => '',
            'suffixes' => '',
            'excludePaths' => '',
            'strict' => false,
            'ignoreViolationsOnExit' => false,
        ]
    ): TaskInterface {
        $options['workingDirectory'] = $options['workingDirectory'] ?: './tests/_data/fixtures';
        $options['suffixes'] = $options['suffixes'] ? explode(',', $options['suffixes']) : [];
        $options['excludePaths'] = $options['excludePaths'] ? explode(',', $options['excludePaths']) : [];

        return $this
            ->taskPhpmdLintFiles($options)
            ->setPaths(explode(',', $paths))
            ->setReportFormat($reportFormat)
            ->setRuleSetFileNames(explode(',', $ruleSetNames))
            ->setOutput($this->output());
    }
}
