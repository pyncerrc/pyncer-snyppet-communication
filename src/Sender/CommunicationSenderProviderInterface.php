<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;

interface CommunicationSenderProviderInterface extends SenderProviderInterface
{
    public function getContactData(
        CommunicationType $type,
        int $contactId
    ): array;
}
