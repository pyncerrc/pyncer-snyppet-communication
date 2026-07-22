<?php
namespace Pyncer\Snyppet\Communication\Exception;

enum SenderExceptionCode: int
{
    case UNKNOWN = 0;
    case MESSAGE = 1;
    case TRANSPORT = 2;
}
