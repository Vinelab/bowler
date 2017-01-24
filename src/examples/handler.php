<?php

namespace App\Messaging\Handlers;

use App\Exceptions\WhatEverException;
use App\Exceptions\WhatElseException;
use App\Exceptions\InvalidInputException;

class AuthorHandler
{
    public function handle($msg)
    {
        echo "Author: ".$msg->body;
    }

    public function handleError($e, $broker)
    {
        if($e instanceof InvalidInputException) {
            $broker->rejectMessage();
        } elseif($e instanceof WhatEverException) {
            $broker->ackMessage();
        } elseif($e instanceof WhatElseException) {
            $broker->nackMessage();
        } else {
            $msg = $borker->getMessage();
            if($msg->body) {
                //
            }
        }
    }
}
