<?php
namespace Pyncer\Snyppet\Communication\Exception;

enum MessageExceptionCode: int
{
    case UNKNOWN = 0;
    case CONTENT = 1;
}
