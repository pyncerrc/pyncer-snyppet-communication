<?php
namespace Pyncer\Snyppet\Communication\Exception;

enum QueueExceptionCode: int
{
    case UNKNOWN = 0;
    case STATUS = 1;
    case CONTENT = 2;
    case CONTACTS = 3;
}
