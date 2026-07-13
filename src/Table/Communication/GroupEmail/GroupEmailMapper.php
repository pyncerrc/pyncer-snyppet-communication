<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\GroupEmail;

use Pyncer\Data\Mapper\AbstractMapper;
use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapperQuery;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailModel;

class GroupEmailMapper extends AbstractMapper
{
    public function getTable(): string
    {
        return 'communication__queue';
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return new GroupEmailModel($data);
    }

    public function isValidModel(ModelInterface $model): bool
    {
        return ($model instanceof GroupEmailModel);
    }

    public function isValidMapperQuery(MapperQueryInterface $mapperQuery): bool
    {
        return ($mapperQuery instanceof GroupEmailMapperQuery);
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
