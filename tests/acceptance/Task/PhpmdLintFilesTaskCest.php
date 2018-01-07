<?php

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpMessDetector\Test\AcceptanceTester;
use Sweetchuck\Robo\PhpMessDetector\Test\Helper\RoboFiles\PhpmdRoboFile;

class PhpmdLintFilesTaskCest
{
    public function runList(AcceptanceTester $tester)
    {
        $id = 'lintFiles';
        $tester->runRoboTask(
            $id,
            PhpmdRoboFile::class,
            'phpmd:lint-files',
            '../../../tests/_data/fixtures/a.php',
            'text',
            '../../../vendor/phpmd/phpmd/src/main/resources/rulesets/codesize.xml'
        );
        $exitCode = $tester->getRoboTaskExitCode($id);
        $stdOutput = $tester->getRoboTaskStdOutput($id);
        $stdError = $tester->getRoboTaskStdError($id);

        $expectedStdError = implode(' ', [
            " [PHP Mess Detector]",
            "cd './tests/_data/fixtures'",
            "&&",
            "../../../bin/phpmd",
            "'../../../tests/_data/fixtures/a.php'",
            "'text'",
            "'../../../vendor/phpmd/phpmd/src/main/resources/rulesets/codesize.xml'\n",
        ]);

        $tester->assertEquals(0, $exitCode);
        $tester->assertEquals('', $stdOutput);
        $tester->assertEquals($expectedStdError, $stdError);
    }
}
