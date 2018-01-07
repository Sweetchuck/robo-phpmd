# Robo task wrapper for PHPMD (PHP Mess Detector)

[![CircleCI](https://circleci.com/gh/Sweetchuck/robo-phpmd.svg?style=svg)](https://circleci.com/gh/Sweetchuck/robo-phpmd)
[![codecov](https://codecov.io/gh/Sweetchuck/robo-phpmd/branch/master/graph/badge.svg)](https://codecov.io/gh/Sweetchuck/robo-phpmd)

## Usage

```php
<?php

use Robo\Tasks;
use Sweetchuck\Robo\PhpMessDetector\PhpmdTaskLoader;

class RoboFile extends Tasks
{
    use PhpmdTaskLoader;

    public function phpmd()
    {
        return $this
            ->taskPhpmdLintFiles()
            ->setPaths(['src/', 'tests/'])
            ->setExcludePaths(['src/foo.php'])
            ->setReportFormat('text')
            ->setRuleSetFileNames(['path/to/custom.xml']);
    }
}
```
