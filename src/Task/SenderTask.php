<?php
namespace Pyncer\Snyppet\Communication\Task;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;
use Pyncer\Exception\RuntimeException;
use Pyncer\Snyppet\Communication\Exception\MessageException;
use Pyncer\Snyppet\Communication\Exception\MessageExceptionCode;
use Pyncer\Snyppet\Communication\Exception\SenderException;
use Pyncer\Snyppet\Communication\Exception\SenderExceptionCode;
use Pyncer\Snyppet\Communication\Exception\TransportException;
use Pyncer\Snyppet\Communication\Exception\TransportExceptionCode;
use Pyncer\Snyppet\Communication\Sender\CommunicationSender;
use Pyncer\Snyppet\Communication\Sender\CommunicationSenderProviderInterface;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapper;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapper;
use Pyncer\Snyppet\Task\AbstractTask;

use function Pyncer\date_time as pyncer_date_time;

class SenderTask extends AbstractTask
{
    protected array $senderErrors = [];
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

    public function getSenderErrors(): array
    {
        return $this->senderErrors;
    }

    public function runTask(array $params = []): void
    {
        $communicationMapper = new CommunicationMapper($this->connection);

        $sendingDateTime = pyncer_date_time();
        $sendingDateTime->modify('-1 hour');

        $result = $communicationMapper->selectAllByQuery(
            function(SelectQueryInterface $query) use($sendingDateTime) {
                $query->getWhere()
                    ->orOpen()
                    ->compare('status', 'queued')
                    ->andOpen()
                    ->compare('status', 'sending')
                    ->dateTimeCompare('update_date_time', $sendingDateTime, '<=')
                    ->andClose()
                    ->orClose()
                    ->compare('enabled', true);
            }
        );

        $sender = new CommunicationSender(
            $this->connection,
            $this->senderProvider,
        );

        foreach ($result as $communicationModel) {
            try {
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
                );
            } catch (SenderException $error) {
                if (!in_array('sender', $this->errors)) {
                    $this->errors[] = 'sender';
                }

                $code = match($error->getExceptionCode()) {
                    SenderExceptionCode::MESSAGE => 'message',
                    SenderExceptionCode::TRANSPORT => 'transport',
                    default => 'unknown',
                };

                $this->senderErrors[] = $code . ': ' . $error->getMessage() . ' (' . $communicationModel->getId() . ')';
            } catch (MessageException $error) {
                if (!in_array('sender', $this->errors)) {
                    $this->errors[] = 'sender';
                }

                $code = match($error->getExceptionCode()) {
                    MessageExceptionCode::CONTENT => 'content',
                    default => 'unknown',
                };

                $this->senderErrors[] = $code . ': ' . $error->getMessage() . ' (' . $communicationModel->getId() . ')';
            } catch (TransportException $error) {
                if (!in_array('sender', $this->errors)) {
                    $this->errors[] = 'sender';
                }

                $code = match($error->getExceptionCode()) {
                    TransportExceptionCode::MESSAGE => 'message',
                    TransportExceptionCode::FROM => 'from',
                    default => 'unknown',
                };

                $this->senderErrors[] = $code . ': ' . $error->getMessage() . ' (' . $communicationModel->getId() . ')';
            }
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
