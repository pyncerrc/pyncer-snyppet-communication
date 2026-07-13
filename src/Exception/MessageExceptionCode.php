<?php
namespace Pyncer\Snyppet\Communication\Exception;

enum SenderExceptionCode: int
{
    case UNKNOWN = 0;
    case CONTENT = 1;
}
