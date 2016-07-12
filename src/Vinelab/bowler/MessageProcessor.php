<?php

namespace Vinelab\Bowler;

class MessageProcessor
{
    private $message;

    public function handle($msg)
	{
		$this->setMessage($msg);
		echo "Handler: ".$msg->body;
		//
		//return $this->getMessage();
	}

    // public function __construct($message)
    // {
    //     $this->message = $message;
    //     echo $this->message;
    // }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($value)
    {
        $this->message = $value;
    }
}
