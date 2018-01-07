<?php

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use Codeception\Test\Unit;
use Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask;

class PhpmdTaskTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\PhpMessDetector\Test\UnitTester
     */
    protected $tester;

    public function casesGetCommand(): array
    {
        return [
            'all-in-one' => [
                implode(' ', [
                    "cd 'my-dir'",
                    "&&",
                    "my-php my-phpmd",
                    "'./src/,./bar.php'",
                    "'html'",
                    "'a.xml,c.xml'",
                    "--minimumpriority '4'",
                    "--reportfile 'd.html'",
                    "--suffixes 'php,inc'",
                    "--exclude 'd.php,f.php'",
                    '--strict',
                    '--ignore-violations-on-exit',
                ]),
                [
                    'workingDirectory' => 'my-dir',
                    'phpExecutable' => 'my-php',
                    'phpmdExecutable' => 'my-phpmd',
                    'paths' => [
                        './src/' => true,
                        './foo.php' => false,
                        './bar.php' => true,
                    ],
                    'reportFormat' => 'html',
                    'ruleSetFileNames' => [
                        'a.xml' => true,
                        'b.xml' => false,
                        'c.xml' => true,
                    ],
                    'minimumPriority' => 4,
                    'reportFile' => 'd.html',
                    'suffixes' => [
                        'php' => true,
                        'phtml' => false,
                        'inc' => true,
                    ],
                    'excludePaths' => [
                        'd.php' => true,
                        'e.php' => false,
                        'f.php' => true,
                    ],
                    'strict' => true,
                    'ignoreViolationsOnExit' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options)
    {
        $task = new PhpmdLintFilesTask();
        $task->setOptions($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }
}
