<?php
namespace Pyncer\Snyppet\Communication\Transport;

interface TransportInterface
{
    public function send(
        string|array $to,
        MessageInterface $message,
        array $data = [],
        array $params = [],
    ): void;
}
