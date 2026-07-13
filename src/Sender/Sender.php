<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Message\EmailMessage;
use Pyncer\Snyppet\Communication\Message\SmsMessage;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

class Sender
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected SenderProviderInterface $senderProvider,
        protected ?int $organizationId = null,
    ) {}

    public function sendEmail(
        string|array $to,
        ContentModel $contentModel,
        array $data = [],
        array $params = [],
    ): void
    {
        $data = [
            $this->senderProvider->getData(
                CommunicationType::EMAIL,
                $this->organizationId,
            ),
            ...$data,
        ];

        $emailMessage = $this->senderProvider->getEmailMessage($contentModel);

        $emailTransport = $this->senderProvider->getEmailTransport(
            $this->organizationId,
        );

        $emailTransport->send(
            $to,
            $emailMessage,
            $data,
            $params,
        );
    }

    public function sendSms(
        string|array $to,
        ContentModel $contentModel,
        array $data = [],
        array $params = [],
    ): void
    {
        $data = [
            $this->senderProvider->getData(
                CommunicationType::SMS,
                $this->organizationId,
            ),
            ...$data,
        ];

        $smsMessage = $this->senderProvider->getSmsMessage($contentModel);

        $smsTransport = $this->senderProvider->getSmsTransport(
            $this->organizationId,
        );

        $smsTransport->send(
            $to,
            $smsMessage,
            $data,
            $params,
        );
    }
}
