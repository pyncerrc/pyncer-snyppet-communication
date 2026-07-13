<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\GroupEmail;

use Pyncer\Data\MapperQuery\AbstractRequestMapperQuery;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;

class GroupEmailMapperQuery extends AbstractRequestMapperQuery
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

        if ($left === 'sent' &&
            is_bool($right) &&
            ($operator === '=' || $operator === '!=')
        ) {
            return true;
        }

        return parent::isValidFilter($left, $right, $operator);
    }
}
