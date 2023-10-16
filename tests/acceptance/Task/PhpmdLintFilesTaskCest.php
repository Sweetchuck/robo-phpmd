<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Tests\Acceptance\Task;

use Sweetchuck\Robo\PhpMessDetector\Tests\AcceptanceTester;
use Sweetchuck\Robo\PhpMessDetector\Tests\Helper\RoboFiles\PhpmdRoboFile;

class PhpmdLintFilesTaskCest
{
    public function runPhpmdLintFiles(AcceptanceTester $tester): void
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
            " [PHP Mess Detector - Lint files]",
            "cd './tests/_data/fixtures'",
            "&&",
            "../../../vendor/bin/phpmd",
            "'../../../tests/_data/fixtures/a.php'",
            "'text'",
            "'../../../vendor/phpmd/phpmd/src/main/resources/rulesets/codesize.xml'\n",
        ]);

        $tester->assertSame(0, $exitCode);
        $tester->assertSame('', $stdOutput);
        $tester->assertStringContainsString($expectedStdError, $stdError);
    }
}
