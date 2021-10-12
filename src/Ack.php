<?php

namespace Vinelab\Bowler;

/**
 * Class Ack
 *
 * @property-read string $mode
 * @property-read bool $requeue
 * @property-read bool $multiple
 */
class Ack
{
    const MODE_ACK = 'basic.ack';
    const MODE_NACK = 'basic.nack';
    const MODE_REJECT = 'basic.reject';

    /**
     * @var string
     */
    public $mode;

    /**
     * @var bool
     */
    public $requeue;

    /**
     * @var bool
     */
    public $multiple;

    /**
     * Ack constructor.
     *
     * @param  string  $mode
     * @param  bool  $requeue
     * @param  bool  $multiple
     */
    public function __construct(string $mode, bool $requeue, bool $multiple)
    {
        $this->mode = $mode;
        $this->requeue = $requeue;
        $this->multiple = $multiple;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }
}
