<?php
namespace Pyncer\Snyppet\Communication\Sender;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Exception\SenderException;
use Pyncer\Snyppet\Communication\Exception\SenderExceptionCode;
use Pyncer\Snyppet\Communication\Message\MessageInterface;
use Pyncer\Snyppet\Communication\Sender\SenderProviderInterface;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapper;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapperQuery;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailModel;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapper;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapperQuery;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueModel;
use Pyncer\Snyppet\Communication\Transport\TransportInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\SnyppetManager;

use function Pyncer\date_time as pyncer_date_time;
use function Pyncer\Snyppet\Communication\Email\explode_emails;

class CommunicationSender
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected SenderProviderInterface $senderProvider,
    ) {}

    public function send(
        CommunicationModel $communicationModel,
        ?callable $callback = null,
    ): void
    {
        $communicationMapper = new CommunicationMapper($this->connection);

        $communicationModel->setUpdateDateTime(pyncer_date_time());
        $communicationModel->setStatus(CommunicationStatus::SENDING);
        $communicationMapper->update($communicationModel);

        $emailData = [];
        $emailTransport = null;
        $emailMessage = null;

        $smsData = [];
        $smsTransport = null;
        $smsMessage = null;

        $organizationId = $this->getOrganizationId(
            $communicationModel->getId(),
        );

        $contentMapper = new ContentMapper($this->connection);
        $contentModel = $contentMapper->selectById($communicationModel->getContentId());

        if ($communicationModel->getType() === 'email' ||
            $communicationModel->getType() === null
        ) {
            $emailData = $this->senderProvider->getData(
                CommunicationType::EMAIL,
                $organizationId,
            );

            $emailMessage = $this->senderProvider->getMessage(
                $contentModel,
                CommunicationType::EMAIL,
                $organizationId,
            );

            if ($emailMessage === null) {
                throw new SenderException(
                    'Sender provider returned no email message.'
                    SenderExceptionCode::MESSAGE->value,
                );
            }

            $emailTransport = $this->senderProvider->getTransport(
                CommunicationType::EMAIL,
                $organizationId,
            );

            if ($emailTransport === null) {
                throw new SenderException(
                    'Sender provider returned no email transport.'
                    SenderExceptionCode::TRANSPORT->value,
                );
            }

            $groupEmailMapper = new GroupEmailMapper($this->connection);
            $groupEmailMapperQuery = new GroupEmailMapperQuery($this->connection);
            $filters = new FiltersQueryParam(
                'sent eq false'
            );
            $groupEmailMapperQuery->setFilters($filters);

            $result = $groupEmailMapper->selectAllByCommunicationId(
                $communicationModel->getId(),
                $groupEmailMapperQuery,
            );

            foreach ($result as $groupEmailModel) {
                $this->sendGroupEmail(
                    $groupEmailModel,
                    $emailTransport,
                    $emailMessage,
                    $emailData,
                );

                $groupEmailModel->setSent(true);
                $groupEmailMapper->update($groupEmailModel);

                if ($callback !== null) {
                    call_user_func($callback, $groupEmailModel);
                }
            }
        }

        if ($communicationModel->getType() === 'phone' ||
            $communicationModel->getType() === null
        ) {
            $smsData = $this->senderProvider->getData(
                CommunicationType::SMS,
                $organizationId,
            );

            $smsMessage = $this->senderProvider->getMessage(
                $contentModel,
                CommunicationType::SMS,
                $organizationId,
            );

            if ($smsMessage === null) {
                throw new SenderException(
                    'Sender provider returned no SMS message.'
                    SenderExceptionCode::MESSAGE->value,
                );
            }

            $smsTransport = $this->senderProvider->getTransport(
                CommunicationType::SMS,
                $organizationId,
            );

            if ($smsTransport === null) {
                throw new SenderException(
                    'Sender provider returned no SMS transport.'
                    SenderExceptionCode::TRANSPORT->value,
                );
            }
        }

        $queueMapper = new QueueMapper($this->connection);
        $queueMapperQuery = new QueueMapperQuery($this->connection);
        $filters = new FiltersQueryParam(
            'status eq \'queued\''
        );
        $queueMapperQuery->setFilters($filters);
        $result = $queueMapper->selectAllByCommunicationId(
            $communicationModel->getId(),
            $queueMapperQuery,
        );

        foreach ($result as $queueModel) {
            $contactProfileId = $this->connection->select('communication__queue__contact')
                ->columns('contact_profile_id')
                ->where([
                    'communication_queue_id' => $queueModel->getId(),
                ])
                ->value();

            if ($communicationModel->getType() === 'email' ||
                $communicationModel->getType() === null
            ) {
                if ($queueModel->getEmail() !== null) {
                    $contactProfileData = $this->senderProvider->getContactProfileData(
                        CommunicationType::EMAIL,
                        $contactProfileId,
                    );

                    $thsi->sendQueueEmail(
                        $queueModel,
                        $emailTransport,
                        $emailMessage,
                        [...$emailData, ...$contactProfileData],
                    )

                    $queueModel->setStatus(QueueStatus::SENT);
                    $queueMapper->update($queueModel);
                }
            }

            if ($communicationModel->getType() === 'phone' ||
                $communicationModel->getType() === null
            ) {
                if ($queueModel->getPhone() !== null) {
                    $thsi->sendQueueSms(
                        $contactProfileData = $this->senderProvider->getContactProfileData(
                            CommunicationType::SMS,
                            $contactProfileId,
                        );

                        $queueModel,
                        $smsTransport,
                        $smsMessage,
                        [...$smsData, ...$contactProfileData],
                    )

                    $queueModel->setStatus(QueueStatus::SENT);
                    $queueMapper->update($queueModel);
                }
            }
        }

        $communicationModel->setUpdateDateTime(pyncer_date_time());
        $communicationModel->setStatus(CommunicationStatus::SENT);
        $communicationMapper->update($communicationModel);
    }

    protected function getOrganizationId(int $communicationId): ?int
    {
        if (!SnyppetManager::getInstance()->has('organization')) {
            return null;
        }

        $this->connection->select()
    }

    protected function sendGroupEmail(
        GroupEmailModel $groupEmailModel,
        TransportInterface $transport,
        MessageInterface $message,
        array $data,
    ): void
    {
        $emails = $groupEmailModel->getEmails();
        $emails = explode_emails($emails);

        $ccEmails = $groupEmailModel->getCcEmails();
        if ($ccEmails !== null) {
            $ccEmails = explode_emails($ccEmails);
        }

        $bccEmails = $groupEmailModel->getBccEmails();
        if ($bccEmails !== null) {
            $bccEmails = explode_emails($bccEmails);
        }

        $transport->send(
            $toEmails,
            $message,
            $data,
            [
                'cc_emails' => $ccEmails,
                'bcc_emails' => $bccEmails,
            ]
        );
    }

    protected function sendQueueEmail(
        QueueModel $queueModel,
        TransportInterface $transport,
        MessageInterface $message,
        array $data,
    ): void
    {
        $transport->send(
            [$queueModel->getEmail(), $queueModel->getName()],
            $message,
            $data,
        );
    }

    protected function sendQueueSms(
        QueueModel $queueModel,
        TransportInterface $transport,
        MessageInterface $message,
        array $data = [],
    ): void
    {
        $smsTransport->send(
            [$queueModel->getPhone(), $queueModel->getName()],
            $message,
            $data,
        );
    }
}
