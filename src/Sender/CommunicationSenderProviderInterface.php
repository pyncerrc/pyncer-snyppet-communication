<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;

interface CommunicationSenderProviderInterface extends SenderProviderInterface
{
    public function getContactProfileData(
        CommunicationType $type,
        ?int $contactProfileId = null,
    ): array;
}
