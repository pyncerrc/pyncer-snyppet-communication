<?php
namespace Pyncer\Snyppet\Communication\Task

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\Queue\Queue;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Task\AbstractTask;

class QueueTask extends AbstractTask
{
    public function __construct(
        ConnectionInterface $connection,
    ) {
        parent::__construct(
            $connection,
            'Communication Queue',
            'communication-queue',
            300, // 5 minutes
        );
    }

    public function runTask(array $params = []): void
    {
        $mapper = new CommunicationMapper($this->connection);

        $result = $mapper->selectAllByQuery(
            function(SelectQueryInterface $query) {
                $query->getWhere()
                ->compare('status', 'scheduled')
                ->compare('enabled', true)
                ->dateTimeCompare('schedule_date_time', pyncer_date_time(), '<=');
            }
        );

        foreach ($result as $model) {
            $queue = new Queue($this->connection, $model);

            try {
                $queue->queue();
            } catch(QueueException $error) {
                $this->errors[] = 'queue';
            }

            $this->touch();
        }
    }
}
