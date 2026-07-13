<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Sender\AbstractSenderProvider;
use Pyncer\Snyppet\Communication\Sender\CommunicationSenderProviderInterface;

abstract class AbstractCommunicationSenderProvider extends AbstractSenderProvider implements
    CommunicationSenderProviderInterface
{
    public function getContactData(
        CommunicationType $type,
        int $contactId,
    ): array
    {
        return [];
    }
}
