<?php
namespace Pyncer\Snyppet\Communication\Exception;

use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Exception\TransportExceptionCode;
use Throwable;

class TransportException extends RuntimeException
{
    public function getExceptionCode(): TransportExceptionCode
    {
        $queueExceptionCode = TransportExceptionCode::tryFrom($this->getCode());

        if ($queueExceptionCode === null) {
            return TransportExceptionCode::UNKNOWN;
        }

        return $queueExceptionCode;
    }
}
