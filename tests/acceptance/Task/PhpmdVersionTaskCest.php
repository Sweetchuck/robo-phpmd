<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpMessDetector\Tests\AcceptanceTester;
use Sweetchuck\Robo\PhpMessDetector\Tests\Helper\RoboFiles\PhpmdRoboFile;

class PhpmdVersionTaskCest
{
    public function runPhpmdVersion(AcceptanceTester $tester): void
    {
        $id = 'version';
        $tester->runRoboTask(
            $id,
            PhpmdRoboFile::class,
            'phpmd:version'
        );

        $expectedExitCode = 0;
        $exitCode = $tester->getRoboTaskExitCode($id);
        $tester->assertSame($expectedExitCode, $exitCode);

        $expectedStdOutput = "PHPMD 2.15.0\nThe version of the Php Mess Detector is: '2.15.0'\n";
        $stdOutput = $tester->getRoboTaskStdOutput($id);
        $tester->assertSame($expectedStdOutput, $stdOutput);

        $expectedStdError = " [PHP Mess Detector - Version] vendor/bin/phpmd --version\n";
        $stdError = $tester->getRoboTaskStdError($id);
        $tester->assertStringContainsString($expectedStdError, $stdError);
    }
}
