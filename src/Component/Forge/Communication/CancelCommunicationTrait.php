<?php
namespace Pyncer\Snyppet\Communication\Component\Forge\Communication;

use Pyncer\App\Identifier as ID;
use Pyncer\Database\Record\SelectQueryInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;

use function Pyncer\date_time as pyncer_date_time;

trait CancelCommunicationTrait
{
    protected function cancelCommunication(ContentModel $contentModel): bool
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
            return false;
        }

        if ($model->getStatus() === CommunicationStatus::QUEUED ||
            $model->getStatus() === CommunicationStatus::SCHEDULED ||
            $model->getStatus() === CommunicationStatus::SENDING
        ) {
            $model->setStatus(CommunicationStatus::CANCELED);
            $model->setUpdateDateTime(pyncer_date_time());

            $connection->update('communication__queue')
                ->values([
                    'status' => 'canceled',
                ])
                ->where([
                    'status' => 'queued',
                    'communication_id' => $model->getId(),
                ])
                ->execute();

            return $mapper->update($model);
        }

        return false;
    }
}
