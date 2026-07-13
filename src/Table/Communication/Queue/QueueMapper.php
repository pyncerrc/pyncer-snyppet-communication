<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\Queue;

use Pyncer\Data\Mapper\AbstractMapper;
use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapperQuery;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueModel;

class QueueMapper extends AbstractMapper
{
    public function getTable(): string
    {
        return 'communication__queue';
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return new QueueModel($data);
    }

    public function isValidModel(ModelInterface $model): bool
    {
        return ($model instanceof QueueModel);
    }

    public function isValidMapperQuery(MapperQueryInterface $mapperQuery): bool
    {
        return ($mapperQuery instanceof QueueMapperQuery);
    }

    public function selectAllByCommunicationId(
        int $communicationId,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        return $this->selectAllByColumns(
            ['communication_id' => $communicationId],
            $mapperQuery
        );
    }
}
