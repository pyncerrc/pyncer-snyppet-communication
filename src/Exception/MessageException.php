<?php
namespace Pyncer\Snyppet\Communication\Exception;

use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Exception\MessageExceptionCode;
use Throwable;

class MessageException extends RuntimeException
{
    public function getExceptionCode(): MessageExceptionCode
    {
        $queueExceptionCode = MessageExceptionCode::tryFrom($this->getCode());

        if ($queueExceptionCode === null) {
            return MessageExceptionCode::UNKNOWN;
        }

        return $queueExceptionCode;
    }
}
