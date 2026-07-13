<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Message\EmailMessage;
use Pyncer\Snyppet\Communication\Message\SmsMessage;
use Pyncer\Snyppet\Communication\Transport\EmailTransportInterface;
use Pyncer\Snyppet\Communication\Transport\SmsTransportInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

interface SenderProviderInterface
{
    public function getEmailTransport(
        ?int $organizationId = null
    ): EmailTransportInterface;

    public function getSmsTransport(
        ?int $organizationId = null
    ): SmsTransportInterface;

    public function getEmailMessage(ContentModel $contentModel): EmailMessage;
    public function getSmsMessage(ContentModel $contentModel): SmsMessage;

    public function getData(
        CommunicationType $type,
        ?int $organizationId = null,
    ): array;
}
