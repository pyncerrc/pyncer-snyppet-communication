<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Message\MessageInterface;
use Pyncer\Snyppet\Communication\Transport\TransportInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

interface SenderProviderInterface
{
    public function getTransport(
        CommunicationType $type,
        ?int $organizationId = null,
    ): ?TransportInterface;

    public function getMessage(
        ContentModel $contentModel,
        CommunicationType $type,
        ?int $organizationId = null,
    ): ?MessageInterface;

    public function getData(
        CommunicationType $type,
        ?int $organizationId = null,
    ): array;
}
