<?php
namespace Pyncer\Snyppet\Communication\Component\Forge\Communication;

use DateTimeInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;
use Pyncer\Snyppet\Communication\CommunicationType;

trait ScheduleCommunicationTrait
{
    protected function scheduleCommunication(
        ContentModel $contentModel,
        null|string|DateTimeInterface $scheduleDateTime,
    ): bool
    {
        if (!$this->isValidCommunicationContent($contentModel)) {
            return false;
        }

        $connection = $this->get(ID::DATABASE);

        $communicationMapper = new CommunicationMapper($connection);

        $communicationModel = new CommunicationModel([
            'content_id' => $contentModel->getId(),
            'schedule_date_time' => $scheduleDateTime,
            'type' => CommunicationType::tryFrom($contentModel->getType()),
            'enabled' => true,
        ]);

        return $communicationMapper->insert($communicationModel);
    }

    protected function isValidCommunicationContent(ContentModel $contentModel): bool
    {
        if ($contentModel->getType() !== 'email' &&
            $contentModel->getType() !== 'sms' &&
            $contentModel->getType() !== 'communication'
        ) {
            return false;
        }

        if ($contentModel->getDeleted() ||
            !$contentModel->getEnabled()
        ) {
            return false;
        }

        return true;
    }
}
