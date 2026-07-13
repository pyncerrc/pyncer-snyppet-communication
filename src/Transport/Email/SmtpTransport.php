<?php
namespace Pyncer\Snyppet\Communication\Transport\Email;

use Pyncer\Snyppet\Communication\Transport\Email\EmailTransportInterface;

class SmtpTransport implements EmailTransportInterface
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected string $username,
        protected string $password,
    ) {}

    public function send(
        string|array $to,
        EmailMessage $message,
        array $data = [],
        array $params = [],
    ): void {

    }
}
