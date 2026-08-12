<?php
namespace Pyncer\Snyppet\Communication\Task;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;
use Pyncer\Snyppet\Communication\Exception\QueueException;
use Pyncer\Snyppet\Communication\Exception\QueueExceptionCode;
use Pyncer\Snyppet\Communication\Queue\Queue;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Task\AbstractTask;

use function Pyncer\date_time as pyncer_date_time;

class QueueTask extends AbstractTask
{
    protected array $queueErrors = [];

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

    public function getQueueErrors(): array
    {
        return $this->queueErrors;
    }

    public function runTask(array $params = []): void
    {
        $this->queueErrors = [];

        $mapper = new CommunicationMapper($this->connection);

        $result = $mapper->selectAllByQuery(
            function(SelectQueryInterface $query) {
                $query->getWhere()
                ->compare('status', 'scheduled')
                ->compare('enabled', true)
                ->orOpen()
                ->compare('schedule_date_time', null)
                ->dateTimeCompare('schedule_date_time', pyncer_date_time(), '<=')
                ->orClose();
            }
        );

        foreach ($result as $model) {
            $queue = new Queue($this->connection, $model);

            try {
                $queue->queue();
            } catch(QueueException $error) {
                if (!in_array('queue', $this->errors)) {
                    $this->errors[] = 'queue';
                }

                $code = match($error->getExceptionCode()) {
                    QueueExceptionCode::STATUS => 'status',
                    QueueExceptionCode::CONTENT => 'content',
                    QueueExceptionCode::CONTACTS => 'contacts',
                    default => 'unknown',
                };

                $this->queueErrors[] = $code . ': ' . $error->getMessage() . ' (' . $model->getId() . ')';
            }

            $this->touch();
        }
    }
}
