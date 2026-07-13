<?php
namespace Pyncer\Snyppet\Communication\Task

use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Sender\CommunicationSender;
use Pyncer\Snyppet\Communication\Sender\CommunicationSenderProviderInterface;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapper;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapper;
use Pyncer\Snyppet\Task\AbstractTask;

use function Pyncer\date_time as pyncer_date_time;

class SenderTask extends AbstractTask
{
    private int $currentTouchCount = 0;
    private const int TARGET_TOUCH_COUNT = 15;

    public function __construct(
        ConnectionInterface $connection,
        protected CommunicationSenderProviderInterface $senderProvider,
    ) {
        parent::__construct(
            $connection,
            'Communication Sender',
            'communication-sender',
            300, // 5 minutes
        );
    }

    public function runTask(array $params = []): void
    {
        $communicationMapper = new CommunicationMapper($this->connection);

        $sendingDateTime = pyncer_date_time();
        $dateTime->modify('-1 hour');

        $result = $communicationMapper->selectAllByQuery(
            function(SelectQueryInterface $query) {
                $query->getWhere()
                ->orOpen()
                ->compare('status', 'queued')
                ->andOpen()
                ->compare('status', 'sending')
                ->compareDateTime('update_date_time', $sendingDateTime, '<=')
                ->andClose()
                ->orClose()
                ->compare('enabled', true)
            }
        );

        $sender = new CommunicationSender(
            $this->connection,
            $this->senderProvider,
        );

        $sendingDateTime = pyncer_date_time();
        $dateTime->modify('-1 hour');

        foreach ($result as $communicationModel) {
            $sender->send(
                $communicationModel,
                function(QueueModel|GroupEmailModel $model)
                    use ($communicationMapper, $communicationModel)
                {
                    $this->touch();

                    if ($this->touchCount === 0) {
                        $communicationModel->setUpdateDateTime(pyncer_date_time());
                        $communicationMapper->update($communicationModel);
                    }
                }
            )
        }
    }

    protected function touch(): void
    {
        if (!$this->taskModel->getRunning()) {
            throw new RuntimeException('Task isn\'t running.');
        }

        // Rate limit touch so it's not every send
        ++$this->touchCount;

        if ($this->touchCount === static::TARGET_TOUCH_COUNT) {
            $this->touchCount = 0;

            parent::touch();
        }
    }
}
