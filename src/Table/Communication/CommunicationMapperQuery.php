<?php
namespace Pyncer\Snyppet\Communication\Table\Communication;

use Pyncer\Data\MapperQuery\AbstractRequestMapperQuery;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;

class CommunicationMapperQuery extends AbstractRequestMapperQuery
{
    protected function isValidFilter(
        string $left,
        mixed $right,
        string $operator,
    ): bool
    {
        if ($left === 'uid' &&
            is_string($right) &&
            ($operator === '=' || $operator === '!=')
        ) {
            return true;
        }

        if ($left === 'content_id' &&
            is_int($right) &&
            $operator === '='
        ) {
            return true;
        }

        if ($left === 'enabled' &&
            is_bool($right) &&
            ($operator === '=' || $operator === '!=')
        ) {
            return true;
        }

        if ($left === 'type' &&
            is_string($right) &&
            ($operator === '=' || $operator === '!=')
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

    protected function isValidOrderBy(string $key, string $direction): bool
    {
        switch ($key) {
            case 'insert_date_time':
            case 'update_date_time':
            case 'type':
            case 'enabled':
                return true;
        }

       return parent::isValidOrderBy($key, $direction);
    }

    protected function getOrderByColumn(
        SelectQueryInterface $query,
        $key,
        $direction
    ): array
    {
        switch ($key) {
            case 'update_date_time':
                $function = $this->getConnection()->functions(
                    'communication',
                    'Coalesce'
                )->arguments('update_date_time', 'insert_date_time');
                return [$function, $direction];
            case 'random':
                $connection = $query->getDatabase();
                return ['@', $connection->functions($query->getTable(), 'Rand'), $direction];
        }

        return parent::getOrderByColumn($query, $key, $direction);
    }
}
