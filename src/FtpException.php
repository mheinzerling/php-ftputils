<?php
declare(strict_types = 1);

namespace mheinzerling\commons;


class FtpException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}