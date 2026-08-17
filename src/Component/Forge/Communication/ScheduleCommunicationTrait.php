<?php
namespace Pyncer\Snyppet\Communication\Component\Forge\Communication;

use DateTimeInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;
use Pyncer\Snyppet\SnyppetManager;

use function Pyncer\Snyppet\Communication\is_valid_communication_content;

trait ScheduleCommunicationTrait
{
    protected function scheduleCommunication(
        ContentModel $contentModel,
        null|string|DateTimeInterface $scheduleDateTime,
    ): bool
    {
        if (!is_valid_communication_content($contentModel)) {
            return false;
        }

        $connection = $this->get(ID::DATABASE);

        $communicationMapper = new CommunicationMapper($connection);

        $communicationModel = new CommunicationModel([
            'content_id' => $contentModel->getId(),
            'schedule_date_time' => $scheduleDateTime,
            'type' => CommunicationType::tryFrom($contentModel->getType()),
            'status' => CommunicationStatus::SCHEDULED,
            'enabled' => true,
        ]);

        if (!$communicationMapper->insert($communicationModel)) {
            return false;
        }

        if (SnyppetManager::getInstance()->has('organization') &&
            $this->has(ID::organization())
        ) {
            $organizationModel = $this->get(ID::organization());

            $connection->insert('communication__organization')
                ->values([
                    'communication_id' => $communicationModel->getId(),
                    'organization_id' => $organizationModel->getId(),
                ])
                ->execute();
        }

        return true;
    }
}
