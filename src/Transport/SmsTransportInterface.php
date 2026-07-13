<?php
namespace Pyncer\Snyppet\Communication\Transport;

interface SmsTransportInterface
{
    public function send(
        string|array $to,
        SmsMessage $message,
        array $data = [],
        array $params = [],
    ): void;
}
