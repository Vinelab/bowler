<?php

namespace Vinelab\Bowler\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
trait ConsumerTagTrait
{
    public function getConsumerTagFilename()
    {
        return 'rabbitmq-consumer.tag';
    }

    private function writeConsumerTag($tag)
    {
        Storage::disk('local')->put($this->getConsumerTagFilename(), $tag);
    }

    public function readConsumerTag()
    {
        return Storage::disk('local')->get($this->getConsumerTagFilename());
    }
}
