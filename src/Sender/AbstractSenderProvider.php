<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Message\EmailMessage;
use Pyncer\Snyppet\Communication\Message\SmsMessage;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

abstract class AbstractSenderProvider implements SenderProviderInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
    ) {}

    public function getEmailMessage(ContentModel $contentModel): EmailMessage
    {
        return EmailMessage::fromContentModel(
            $this->connection,
            $contenModel,
        );
    }

    public function getSmsMessage(ContentModel $contentModel): SmsMessage
    {
        return SmsMessage::fromContentModel(
            $this->connection,
            $contenModel,
        );
    }

    public function getData(
        CommunicationType $type,
        ?int $organizationId = null,
    ): array
    {
        return [];
    }
}
