<?php
namespace Pyncer\Snyppet\Communication\Table\Communication;

use Pyncer\Data\Mapper\AbstractMapper;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapperQuery;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;

class CommunicationMapper extends AbstractMapper
{
    public function getTable(): string
    {
        return 'communication';
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return new CommunicationModel($data);
    }

    public function isValidModel(ModelInterface $model): bool
    {
        return ($model instanceof CommunicationModel);
    }

    public function isValidMapperQuery(MapperQueryInterface $mapperQuery): bool
    {
        return ($mapperQuery instanceof CommunicationMapperQuery);
    }

    public function selectByUid(
        string $uid,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        return $this->selectByColumns(['uid' => $uid], $mapperQuery);
    }
}
