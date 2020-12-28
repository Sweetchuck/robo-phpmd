<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpMessDetector\Test\AcceptanceTester;
use Sweetchuck\Robo\PhpMessDetector\Test\Helper\RoboFiles\PhpmdRoboFile;

class PhpmdVersionTaskCest
{
    public function runPhpmdVersion(AcceptanceTester $tester)
    {
        $id = 'version';
        $tester->runRoboTask(
            $id,
            PhpmdRoboFile::class,
            'phpmd:version'
        );

        $expectedExitCode = 0;
        $exitCode = $tester->getRoboTaskExitCode($id);
        $tester->assertEquals($expectedExitCode, $exitCode);

        $expectedStdOutput = "The version of the Php Mess Detector is: '2.9.1'\n";
        $stdOutput = $tester->getRoboTaskStdOutput($id);
        $tester->assertEquals($expectedStdOutput, $stdOutput);

        $expectedStdError = " [PHP Mess Detector - Version] bin/phpmd --version\n";
        $stdError = $tester->getRoboTaskStdError($id);
        $tester->assertEquals($expectedStdError, $stdError);
    }
}
