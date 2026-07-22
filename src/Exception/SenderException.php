<?php
namespace Pyncer\Snyppet\Communication\Exception;

use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Exception\SenderExceptionCode;
use Throwable;

class SenderException extends RuntimeException
{
    public function getExceptionCode(): SenderExceptionCode
    {
        $queueExceptionCode = SenderExceptionCode::tryFrom($this->getCode());

        if ($queueExceptionCode === null) {
            return SenderExceptionCode::UNKNOWN;
        }

        return $queueExceptionCode;
    }
}
