<?php
namespace Pyncer\Snyppet\Communication\Message;

interface MessageInterface
{
    public function getBody(): null|string|array;
    public function getFrom(): null|string|array;
}
