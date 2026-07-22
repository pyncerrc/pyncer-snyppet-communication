<?php
namespace Pyncer\Snyppet\Communication\Message\Email;

use Pyncer\Snyppet\Communication\Message\MessageInterface;

interface EmailMessageInterface extends MessageInterface
{
    public function getSubject(): string;
    public function getReplyTo(): null|string|array;
    public function getAttachments(): array;
}
