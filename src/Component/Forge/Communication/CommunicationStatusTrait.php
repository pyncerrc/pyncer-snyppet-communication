<?php
namespace Pyncer\Snyppet\Communication\Component\Forge\Communication;

use DateTimeInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

use function Pyncer\Snyppet\Communication\is_valid_communication_content;

trait CommunicationStatusTrait
{
    protected function getCommunicationStatus(
        ContentModel $contentModel,
    ): bool
    {
        if (is_valid_communication_content($contentModel)) {
            return 'draft';
        }

        $connection = $this->get(ID::DATABASE);

        $communicationMapper = new CommunicationMapper($connection);

        $model = $mapper->selectByQuery(
            function(SelectQueryInterface $query) use($contentModel) {
                $query->getWhere()
                ->compare('content_id', $contentModel->getId())
                ->compare('enabled', true)
                ->compare('type', CommunicationType::tryFrom($contentModel->getType()))
                ->getQuery()
                ->orderBy(['id', '<']);
            }
        );

        if ($model === null) {
            return 'draft';
        }

        if ($model->getStatus() === CommunicationStatus::SCHEDULED) {
            if ($model->getScheduleDateTime() === null) {
                return 'queued';
            }
        }

        return $model->getStatus()->value;
    }
}
