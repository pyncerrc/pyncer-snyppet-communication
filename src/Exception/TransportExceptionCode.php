<?php
namespace Pyncer\Snyppet\Communication\Exception;

enum TransportExceptionCode: int
{
    case UNKNOWN = 0;
    case MESSAGE = 1;
}
