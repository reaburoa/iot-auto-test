<?php
namespace App\Exceptions;

use Exception;

class BaseException extends Exception
{
    protected $code = 1;
    protected $message = 'Unknown Error';

    public function __construct($message = "")
    {
        $this->message = $message;
    }
}
