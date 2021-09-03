# Robo task wrapper for PHPMD (PHP Mess Detector)

[![CircleCI](https://circleci.com/gh/Sweetchuck/robo-phpmd/tree/1.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/robo-phpmd/?branch=1.x)
[![codecov](https://codecov.io/gh/Sweetchuck/robo-phpmd/branch/1.x/graph/badge.svg?token=HSF16OGPyr)](https://app.codecov.io/gh/Sweetchuck/robo-phpmd/branch/1.x)


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
