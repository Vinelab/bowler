<?php

namespace Vinelab\Bowler\Traits;

use Storage;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
trait ConsumerTagTrait
{
    public function getConsumerTagFilePath()
    {
        return storage_path().'/app/rabbitmq-consumer.tag';
    }

    private function writeConsumerTag($tag)
    {
        Storage::disk('local')->put($this->getConsumerTagFilePath(), $tag);
    }

    public function readConsumerTag()
    {
        return Storage::disk('local')->get($this->getConsumerTagFilePath());
    }
}
