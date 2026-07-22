<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Sender\AbstractSenderProvider;
use Pyncer\Snyppet\Communication\Sender\CommunicationSenderProviderInterface;

abstract class AbstractCommunicationSenderProvider extends AbstractSenderProvider implements
    CommunicationSenderProviderInterface
{
    public function getContactProfileData(
        CommunicationType $type,
        ?int $contactProfileId = null,
    ): array
    {
        return [];
    }
}
