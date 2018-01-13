<?php

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Unit\Task;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask;

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
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        /** @var \Sweetchuck\Robo\PhpMessDetector\Task\PhpmdVersionTask $task */
        $task = Stub::construct(
            PhpmdVersionTask::class,
            [],
            [
                'processClass' => DummyProcess::class,
            ]
        );
        $task->setOptions($options);

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = $std;

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
