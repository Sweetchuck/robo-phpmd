<?php

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use org\bovigo\vfs\vfsStream;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask;
use Webmozart\PathUtil\Path;

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
                [
                    'exitCode' => 0,
                ],
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
    public function testRunSuccess(array $expected, array $options)
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdLintFilesTask $task */
        $task = Stub::construct(
            PhpmdLintFilesTask::class,
            [],
            [
                'processClass' => DummyProcess::class,
            ]
        );
        $task->setOptions($options);

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => 0,
            'stdOutput' => '',
            'stdError' => '',
        ];

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
