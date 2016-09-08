<?php

namespace App\Messaging;

class Handler {

	public function handle($msg)
	{
		echo "Handler: ".$msg->body;
	}
}