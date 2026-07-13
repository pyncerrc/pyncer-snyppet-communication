<?php
namespace Pyncer\Snyppet\Communication\Queue;

use Exception;
use Pyncer\Data\MapperQuery\FiltersQueryParam;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\Exception\QueueException;
use Pyncer\Snyppet\Communication\Exception\QueueExceptionCode;
use Pyncer\Snyppet\Communication\Table\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\CommunicationModel;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapper;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailModel;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapper;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueModel;
use Pyncer\Snyppet\Contact\Table\Contact\Profile\ProfileMapper as ContactProfileMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Content\Table\Content\DataManager as ContentDataManager;
use Pyncer\Snyppet\Content\Table\Content\ValueManager as ContentValueManager;
use Pyncer\Snyppet\SnyppetManager;

use function Pyncer\date_time as pyncer_date_time;
use function Pyncer\Snyppet\Communication\Email\explode_emails;
use function Pyncer\Snyppet\Communication\Email\implode_emails;
use function Pyncer\Snyppet\Communication\Email\unique_emails;
use function Pyncer\Snyppet\Communication\Sms\explode_phones;
use function Pyncer\Snyppet\Communication\Sms\unique_phones;

class Queue
{
    use SnyppetTrait;

    public function __construct(
        protected ConnectionInterface $connection,
        protected CommunicationModel $communicationModel,
    ) {}

    public function queue(): void
    {
        if ($this->communicationModel->getStatus() === CommunicationStatus::QUEUED) {
            return;
        }

        if ($this->communicationModel->getStatus() === CommunicationStatus::SENDING) {
            throw new QueueException(
                'Communication is currently sending.',
                QueueExceptionCode::STATUS->value,
            );
        }

        if ($this->communicationModel->getStatus() === CommunicationStatus::SENT) {
            throw new QueueException(
                'Communication has already sent.'
                QueueExceptionCode::STATUS->value,
            );
        }

        if ($this->communicationModel->getStatus() === CommunicationStatus::FAILED) {
            throw new QueueException(
                'Communication has previously failed.'
                QueueExceptionCode::STATUS->value,
            );
        }

        $this->connection->delete('communication__queue')
            ->where([
                'communication_id' => $communicationModel->getId()
            ])
            ->execute();

        $this->connection->delete('communication__group_email')
            ->where([
                'communication_id' => $communicationModel->getId()
            ])
            ->execute();

        $communicationMapper = new CommunicationMapper($this->connection);
        $contentMapper = new ContentMapper($this->connection);
        $contentModel = $contentMapper->selectById($this->communicationModel->getContentId());

        if (!$this->isValidCommunicationContent($contentModel)) {
            $communicationModel->setUpdateDateTime(pyncer_date_time());
            $communicationModel->setStatus(CommunicationStatus::FAILED);
            $communicationMapper->update($communicationModel);

            throw new QueueException(
                'Communication content is invalid.'
                QueueExceptionCode::CONTENT->value,
            );
        }

        $contentDataManager = new ContentValueMananger($this->connection, $contentModel->getId());

        $contentValueManager = new ContentValueMananger($this->connection, $contentModel->getId());
        $contentValueManager->load(
            'to_contact_id',
            'group_email',
        );

        $groupEmail = $contentValueManager->getBool('group_email');

        $queueEmails = (
            $communicationModel->getType() === null ||
            $communicationModel->getType() === 'email'
        );

        $queuePhones = (
            $communicationModel->getType() === null ||
            $communicationModel->getType() === 'sms'
        );

        $hasContacts = false;

        if ($groupEmail && $queueEmails) {
            try {
                if ($this->insertGroupEmail(
                    $communicationModel,
                    $contentModel,
                )) {
                    $hasContacts = true;
                }
            } catch (Exception $error) {
                $communicationModel->setUpdateDateTime(pyncer_date_time());
                $communicationModel->setStatus(CommunicationStatus::FAILED);
                $communicationMapper->update($communicationModel);

                throw new QueueException(
                    'Error inserting group email.'
                    QueueExceptionCode::UNKNOWN->value,
                    $error,
                );
            }
        } elseif ($queueEmails) {
            $contentDataManager->load('to_phones');

            $toEmails = $contentDataManager->getString('to_emails');
            $toEmails = explode_emails($toEmails);

            $toContactId = $contentDataManager->getInt('to_contact_id', null);
            if ($toContactId !== null) {
                $contactToEmails = $this->getContactEmails($contactId);
                $toEmails = [...$toEmails, ...$contactToEmails]
            }

            $toEmails = unique_emails($toEmails);

            if ($toEmails) {
                $hasContacts = true;
            }

            foreach ($toEmails as $toEmail) {
                try {
                    $this->insertQueue(
                        $communicationModel,
                        $toEmail[1],
                        $toEmail[0],
                        null,
                    );
                } catch (Exception $error) {
                    $communicationModel->setUpdateDateTime(pyncer_date_time());
                    $communicationModel->setStatus(CommunicationStatus::FAILED);
                    $communicationMapper->update($communicationModel);

                    throw new QueueException(
                        'Error inserting email into queue.'
                        QueueExceptionCode::UNKNOWN->value,
                        $error,
                    );
                }
            }
        }

        if ($queuePhones) {
            $contentDataManager->load('to_phones');

            $toPhones = $contentDataManager->getString('to_phones');
            $toPhones = explode_phones($toPhones);

            $toContactId = $contentDataManager->getInt('to_contact_id', null);
            if ($toContactId !== null) {
                $contactToPhones = $this->getContactPhones($contactId);
                $toPhones = [...$toEmails, ...$contactToPhones]
            }

            $toPhones = unique_phones($toPhones);

            if ($toPhones) {
                $hasContacts = true;
            }

            foreach ($toPhones as $toPhone) {
                try {
                    if (is_array($toPhone)) {
                        $this->insertQueue(
                            $communicationModel,
                            $toPhone[1],
                            null,
                            $toPhone[0],
                        );
                    } else {
                        $this->insertQueue(
                            $communicationModel,
                            null,
                            null,
                            $toPhone,
                        );
                    }
                } catch (Exception $error) {
                    $communicationModel->setUpdateDateTime(pyncer_date_time());
                    $communicationModel->setStatus(CommunicationStatus::FAILED);
                    $communicationMapper->update($communicationModel);

                    throw new QueueException(
                        'Error inserting phone into queue.'
                        QueueExceptionCode::UNKNOWN->value,
                        $error,
                    );
                }
            }
        }

        if (!$hasContacts) {
            $communicationModel->setUpdateDateTime(pyncer_date_time());
            $communicationModel->setStatus(CommunicationStatus::FAILED);
            $communicationMapper->update($communicationModel);

            throw new QueueException(
                'No emails to send to.'
                QueueExceptionCode::CONTACTS->value,
            );
        }

        $communicationModel->setUpdateDateTime(pyncer_date_time());
        $communicationModel->setStatus(CommunicationStatus::QUEUED);
        $communicationMapper->update($communicationModel);
    }

    protected function insertGroupEmails(
        CommunicationModel $communicationModel,
        ContentModel $contentModel,
    ): void
    {
        $contentDataManager = new ContentValueMananger(
            $this->connection,
            $contentModel->getId()
        );
        $contentDataManager->load(
            'to_emails',
            'to_cc_emails',
            'to_bcc_emails',
        );

        $contentValueManager = new ContentValueMananger(
            $this->connection,
            $contentModel->getId()
        );
        $contentValueManager->load(
            'to_contact_id',
        );

        $toContactId = $contentDataManager->getInt('to_contact_id', null);

        $toEmails = $contentDataManager->getString('to_emails', null);
        $toEmails = explode_emails($toEmails);

        if ($toContactId !== null) {
            $toContactEmails = $this->getContactEmails($toContactId);
            $toEmails = [...$toEmails, ...$toContactEmails];
        }

        $toEmails = unique_emails($toEmails);
        $toEmails = implode_emails($toEmails);

        // TODO: Remove cc/bcc to emails that exist in to emails.

        $toCcEmails = $contentDataManager->getString('to_cc_emails', null);
        $toCcEmails = unique_emails($toCcEmails);

        $toBccEmails = $contentDataManager->getString('to_bcc_emails', null);
        $toBccEmails = unique_emails($toBccEmails);

        if ($toEmails === null) {
            return false;
        }

        $this->insertGroupEmail(
            $communicationModel,
            $toEmails,
            $toCcEmails,
            $toBccEmails,
        );

        return true;
    }

    protected function insertGroupEmail(
        CommunicationModel $communicationModel,
        string $emails,
        ?string $ccEmails,
        ?string $bccEmails,
    ): bool
    {
        $model = new GroupEmailModel();
        $model->setCommunicationId($communicationModel->getId());
        $model->setEmails($emails);
        $model->setCcEmails($ccEmails);
        $model->setBccEmails($bccEmails);

        $mapper = new GroupEmailMapper($this->connection);
        return $mapper->insert($model);
    }

    protected function insertQueue(
        CommunicationModel $communicationModel,
        ?string $name,
        ?string $email,
        ?string $phone,
    ): bool
    {
        $model = new QueueModel();
        $model->setCommunicationId($communicationModel->getId());
        $model->setName($name);
        $model->setEmail($email);
        $model->setPhone($phone);

        $mapper = new QueueMapper($this->connection);
        return $mapper->insert($model);
    }

    protected function getContactEmails(int $contactId): array
    {
        if (!SnyppetManager::getInstance()->has('contact')) {
            return []
        }

        $emails = [];

        $mapper = new ContactProfileMapper($this->connection);
        $mapperQuery = new ContactProfileMapperQuery($this->connection);
        $filters = new FiltersQueryParam(
            'email_verified eq true and enabled eq true'
        );
        $mapperQuery->setFilters($filters);

        $result = $mapper->selectAllByContactId($contactId, $mapperQuery);

        foreach ($result as $profile) {
            if ($profile->getEmail() === null) {
                continue;
            }

            $emails[] = [$profile->getEmail(), $profile->getName()];
        }

        return $emails;
    }

    protected function getContactPhones(int $contactId): array
    {
        if (!SnyppetManager::getInstance()->has('contact')) {
            return []
        }

        $phones = [];

        $mapper = new ContactProfileMapper($this->connection);
        $mapperQuery = new ContactProfileMapperQuery($this->connection);
        $filters = new FiltersQueryParam(
            'phone_verified eq true and enabled eq true'
        );
        $mapperQuery->setFilters($filters);

        $result = $mapper->selectAllByContactId($contactId, $mapperQuery);

        foreach ($result as $profile) {
            if ($profile->getPhone() === null) {
                continue;
            }

            $phones[] = [$profile->getPhone(), $profile->getName()];
        }

        return $phones;
    }

    protected function isValidCommunicationContent(ContentModel $contentModel): bool
    {
        if ($contentModel->getType() !== 'email' &&
            $contentModel->getType() !== 'sms' &&
            $contentModel->getType() !== 'communication'
        ) {
            return false;
        }

        if ($contentModel->getDeleted() ||
            !$contentModel->getEnabled()
        ) {
            return false;
        }

        return true;
    }
}
