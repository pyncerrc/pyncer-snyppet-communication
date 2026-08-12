<?php
namespace Pyncer\Snyppet\Communication\Component\Forge\Communication;

use Pyncer\App\Identifier as ID;
use Pyncer\Database\Record\SelectQueryInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

trait CommunicationStatusTrait
{
    protected function getCommunicationStatus(
        ContentModel $contentModel,
    ): ?CommunicationStatus
    {
        $connection = $this->get(ID::DATABASE);

        $mapper = new CommunicationMapper($connection);

        $model = $mapper->selectByQuery(
            function(SelectQueryInterface $query) use($contentModel) {
                $type = CommunicationType::tryFrom($contentModel->getType())?->value;

                $query->getWhere()
                ->compare('content_id', $contentModel->getId())
                ->compare('enabled', true)
                ->compare('type', $type)
                ->getQuery()
                ->orderBy(['id', '<']);
            }
        );

        if ($model === null) {
            return null;
        }

        if ($model->getStatus() === CommunicationStatus::SCHEDULED) {
            if ($model->getScheduleDateTime() === null) {
                return CommunicationStatus::QUEUED;
            }
        }

        return $model->getStatus();
    }
}
