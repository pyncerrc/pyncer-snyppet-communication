<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Message\Email\EmailMessage;
use Pyncer\Snyppet\Communication\Message\MessageInterface;
use Pyncer\Snyppet\Communication\Message\Sms\SmsMessage;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

abstract class AbstractSenderProvider implements SenderProviderInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
    ) {}

    public function getMessage(
        ContentModel $contentModel,
        CommunicationType $type,
        ?int $organizationId = null,
    ): ?MessageInterface
    {
        if ($type === CommunicationType::EMAIL) {
            return EmailMessage::fromContentModel(
                $this->connection,
                $contenModel,
            );
        } elseif ($type === CommunicationType::SMS) {
            return SmsMessage::fromContentModel(
                $this->connection,
                $contenModel,
            );
        }

        return null;
    }

    public function getData(
        CommunicationType $type,
        ?int $organizationId = null,
    ): array
    {
        return [];
    }
}
