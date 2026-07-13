<?php
namespace Pyncer\Snyppet\Communication\Transport;

interface EmailTransportInterface
{
    public function send(
        string|array $to,
        EmailMessage $message,
        array $data = [],
        array $params = [],
    ): void;
}
