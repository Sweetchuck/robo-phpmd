<?php

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use Codeception\Test\Unit;
use org\bovigo\vfs\vfsStream;
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
                    "--inputfile 'if.php'",
                    "--coverage",
                    "--reportfile 'd.html'",
                    "--reportfile-html 'rf.html'",
                    "--reportfile-text 'rf.txt'",
                    "--reportfile-xml 'rf.xml'",
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
                    'inputFile' => 'if.php',
                    'coverage' => true,
                    'reportFile' => 'd.html',
                    'reportFileHtml' => 'rf.html',
                    'reportFileText' => 'rf.txt',
                    'reportFileXml' => 'rf.xml',
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

    public function testSuffixAddRemove()
    {
        $task = new PhpmdLintFilesTask();
        $task
            ->setSuffixes(['a', 'b', 'c'])
            ->removeSuffix('b')
            ->addSuffix('d');
        $expected = [
            'a' => true,
            'c' => true,
            'd' => true,
        ];
        $this->tester->assertEquals($expected, $task->getSuffixes());
    }

    public function testExcludePaths()
    {
        $task = new PhpmdLintFilesTask();
        $task
            ->setPhpmdExecutable('phpmd')
            ->setExcludePaths(['a', 'b', 'c'])
            ->removeExcludePath('b')
            ->addExcludePath('d');

        $this->tester->assertEquals(
            ['a' => true, 'c' => true, 'd' => true],
            $task->getExcludePaths()
        );

        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => [
                    'exclude-pattern.txt' => implode("\n", [
                        'src/',
                        'a',
                        'b',
                        '',
                    ])
                ]
            ]
        );

        $fileName = $vfs->url() . '/' . __FUNCTION__ . '/exclude-pattern.txt';
        $task->addExcludePathsFromFile($fileName);

        $this->tester->assertEquals(
            "phpmd 'text' --exclude 'src/,a,b,c,d'",
            $task->getCommand()
        );
    }
}
