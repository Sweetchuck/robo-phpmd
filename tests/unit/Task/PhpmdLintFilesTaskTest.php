<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use org\bovigo\vfs\vfsStream;
use Robo\Robo;
use Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput as DummyOutput;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class PhpmdLintFilesTaskTest extends TaskTestBase
{
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
    public function testGetCommand(string $expected, array $options): void
    {
        $task = $this->taskBuilder->taskPhpmdLintFiles($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }

    public function testSuffixAddRemove(): void
    {
        $task = $this->taskBuilder->taskPhpmdLintFiles();
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

    public function testExcludePaths(): void
    {
        $task = $this->taskBuilder->taskPhpmdLintFiles();
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

    public function casesRunSuccess(): array
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => [],
            ]
        );

        return [
            'basic' => [
                [],
                [
                    'workingDirectory' => $vfs->url(),
                    'reportFile' => __FUNCTION__ . '/basic/foo/phpmd.txt',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRunSuccess
     */
    public function testRunSuccess(array $expected, array $options): void
    {
        $expected += [
            'exitCode' => 0,
            'stdOutput' => '',
            'stdError' => '',
        ];

        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $process = $this->make(
            Process::class,
            [
                'run' => $expected['exitCode'],
                'getExitCode' => $expected['exitCode'],
                'getOutput' => $expected['stdOutput'],
                'getErrorOutput' => $expected['stdError'],
            ]
        );

        $processHelper = $this->make(
            ProcessHelper::class,
            [
                'run' => $process,
            ]
        );

        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask $task */
        $task = $this->construct(
            PhpmdLintFilesTask::class,
            [],
            [
                'getProcessHelper' => $processHelper,
            ]
        );

        $dummyOutputConfig = [];
        $dummyOutput = new DummyOutput($dummyOutputConfig);

        $task->setOutput($dummyOutput);
        $task->setOptions($options);

        $result = $task->run();

        $this->tester->assertEquals(
            $expected['exitCode'],
            $result->getExitCode(),
            'Result "exitCode"'
        );

        $workingDirectory = $options['workingDirectory'] ?? '.';
        $reportFileOptions = [
            'reportFile',
            'reportFileHtml',
            'reportFileText',
            'reportFileXml',
        ];
        foreach ($reportFileOptions as $reportFileOption) {
            if (empty($options[$reportFileOption])) {
                continue;
            }

            $fileName = Path::join($workingDirectory, $options[$reportFileOption]);
            $dirName = Path::getDirectory($fileName);
            $this->tester->assertFileExists(
                $dirName,
                "Directory is prepared for file; '$reportFileOption' =  '$fileName'"
            );
        }
    }
}
