<?php

namespace Sweetchuck\Robo\PhpMessDetector\Test\Helper\Dummy;

use Codeception\Lib\Console\Output as ConsoleOutput;

class DummyOutput extends ConsoleOutput
{

    /**
     * @var int
     */
    protected static $instanceCounter = 0;

    /**
     * @var string
     */
    public $output = '';

    /**
     * @var int
     */
    public $instanceId = 0;

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->instanceId = static::$instanceCounter++;

        if (empty($config['stdErr'])) {
            $config['stdErr'] = true;
            $this->setErrorOutput(new static($config));

            return;
        }

        $this->setErrorOutput($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
