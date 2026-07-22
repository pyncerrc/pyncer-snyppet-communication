<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Exception\SenderException;
use Pyncer\Snyppet\Communication\Exception\SenderExceptionCode;
use Pyncer\Snyppet\Communication\Message\MessageInterface;
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

        $message = $this->senderProvider->getMessage(
            $contentModel,
            CommunicationType::EMAIL,
            $this->organizationId,
        );

        if ($message === null) {
            throw new SenderException(
                'Sender provider returned no email message.'
                SenderExceptionCode::MESSAGE->value,
            );
        }

        $transport = $this->senderProvider->getTransport(
            CommunicationType::EMAIL,
            $this->organizationId,
        );

        if ($transport === null) {
            throw new SenderException(
                'Sender provider returned no email transport.'
                SenderExceptionCode::TRANSPORT->value,
            );
        }

        $transport->send(
            $to,
            $message,
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

        $message = $this->senderProvider->getMessage(
            $contentModel,
            CommunicationType::SMS,
            $this->organizationId,
        );

        if ($message === null) {
            throw new SenderException(
                'Sender provider returned no SMS message.'
                SenderExceptionCode::MESSAGE->value,
            );
        }

        $transport = $this->senderProvider->getTransport(
            CommunicationType::SMS,
            $this->organizationId,
        );

        if ($transport === null) {
            throw new SenderException(
                'Sender provider returned no SMS transport.'
                SenderExceptionCode::TRANSPORT->value,
            );
        }

        $transport->send(
            $to,
            $message,
            $data,
            $params,
        );
    }
}
