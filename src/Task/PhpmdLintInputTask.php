<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpMessDetector\Task;

use Sweetchuck\Robo\PhpMessDetector\Option\LintOptionTrait;

class PhpmdLintInputTask extends PhpmdBaseTask
{
    use LintOptionTrait;

    /**
     * {@inheritdoc}
     */
    protected $taskName = 'PHP Mess Detector - Lint input';

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        $this->setOptionsLint($options);

        return $this;
    }
}
