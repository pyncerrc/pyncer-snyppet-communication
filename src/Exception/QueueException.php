<?php
namespace Pyncer\Snyppet\Communication\Exception;

use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Exception\QueueExceptionCode;
use Throwable;

class QueueException extends RuntimeException
{
    public function getExceptionCode(): QueueExceptionCode
    {
        $queueExceptionCode = QueueExceptionCode::tryFrom($this->getCode());

        if ($queueExceptionCode === null) {
            return QueueExceptionCode::UNKNOWN;
        }

        return $queueExceptionCode;
    }
}
