<?php

namespace Vinelab\Bowler;

class Handler {

	public function handle($msg)
	{
		echo "Handler from library: ".$msg->body;
	}
}