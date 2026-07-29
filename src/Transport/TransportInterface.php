<?php
namespace Pyncer\Snyppet\Communication\Transport;

use Pyncer\Snyppet\Communication\Message\MessageInterface;

interface TransportInterface
{
    public function send(
        string|array $to,
        MessageInterface $message,
        array $data = [],
        array $params = [],
    ): void;
}
