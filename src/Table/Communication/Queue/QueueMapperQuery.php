<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\Queue;

use Pyncer\Data\MapperQuery\AbstractRequestMapperQuery;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;

class QueueMapperQuery extends AbstractRequestMapperQuery
{
    protected function isValidFilter(
        string $left,
        mixed $right,
        string $operator,
    ): bool
    {
        if ($left === 'communication_id' &&
            is_int($right) &&
            $operator === '='
        ) {
            return true;
        }

        if ($left === 'status' &&
            is_string($right) &&
            ($operator === '=' || $operator === '!=')
        ) {
            return true;
        }

        return parent::isValidFilter($left, $right, $operator);
    }
}
