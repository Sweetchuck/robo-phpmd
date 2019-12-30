<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use Codeception\Test\Unit;
use Robo\Robo;
use Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput as DummyOutput;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Process\Process;

class PhpmdVersionTaskTest extends Unit
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
                    '--version',
                ]),
                [
                    'workingDirectory' => 'my-dir',
                    'phpExecutable' => 'my-php',
                    'phpmdExecutable' => 'my-phpmd',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options)
    {
        $task = new PhpmdVersionTask();
        $task->setOptions($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }

    public function casesRunSuccess(): array
    {
        return [
            'basic' => [
                [
                    'exitCode' => 0,
                    'version' => '42.84.21',
                ],
                [
                    'assetNamePrefix' => 'my-prefix.'
                ],
                [
                    'exitCode' => 0,
                    'stdOutput' => "PHPMD 42.84.21\n",
                    'stdError' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRunSuccess
     */
    public function testRunSuccess(array $expected, array $options, array $std)
    {
        $std += [
            'exitCode' => 0,
            'stdOutput' => '',
            'stdError' => '',
        ];

        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $process = $this->make(
            Process::class,
            [
                'run' => $std['exitCode'],
                'getExitCode' => $std['exitCode'],
                'getOutput' => $std['stdOutput'],
                'getErrorOutput' => $std['stdError'],
            ]
        );

        $processHelper = $this->make(
            ProcessHelper::class,
            [
                'run' => $process,
            ]
        );

        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask $task */
        $task = $this->construct(
            PhpmdVersionTask::class,
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

        $assertNamePrefix = $options['assetNamePrefix'] ?? '';

        $this->tester->assertEquals(
            $expected['version'],
            $result["{$assertNamePrefix}version"],
            'PHPMD version'
        );
    }
}
