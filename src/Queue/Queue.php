<?php
namespace Pyncer\Snyppet\Communication\Queue;

use Exception;
use Pyncer\Data\MapperQuery\FiltersQueryParam;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Exception\QueueException;
use Pyncer\Snyppet\Communication\Exception\QueueExceptionCode;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationMapper;
use Pyncer\Snyppet\Communication\Table\Communication\CommunicationModel;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailMapper;
use Pyncer\Snyppet\Communication\Table\Communication\GroupEmail\GroupEmailModel;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueMapper;
use Pyncer\Snyppet\Communication\Table\Communication\Queue\QueueModel;
use Pyncer\Snyppet\Contact\Table\Contact\Profile\ProfileMapper as ContactProfileMapper;
use Pyncer\Snyppet\Contact\Table\Contact\Profile\ProfileMapperQuery as ContactProfileMapperQuery;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Content\Table\Content\DataManager as ContentDataManager;
use Pyncer\Snyppet\Content\Table\Content\ValueManager as ContentValueManager;
use Pyncer\Snyppet\SnyppetManager;

use function Pyncer\date_time as pyncer_date_time;
use function Pyncer\Snyppet\Communication\Email\clean_email;
use function Pyncer\Snyppet\Communication\Email\explode_emails;
use function Pyncer\Snyppet\Communication\Email\implode_emails;
use function Pyncer\Snyppet\Communication\Email\unique_emails;
use function Pyncer\Snyppet\Communication\is_valid_communication_content;
use function Pyncer\Snyppet\Communication\Sms\clean_phone;
use function Pyncer\Snyppet\Communication\Sms\explode_phones;
use function Pyncer\Snyppet\Communication\Sms\unique_phones;

use const Pyncer\Snyppet\Communication\PHONE_ALLOW_NANP as PYNCER_COMMUNICATION_PHONE_ALLOW_NANP;

class Queue
{
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
                'Communication has already sent.',
                QueueExceptionCode::STATUS->value,
            );
        }

        if ($this->communicationModel->getStatus() === CommunicationStatus::FAILED) {
            throw new QueueException(
                'Communication has previously failed.',
                QueueExceptionCode::STATUS->value,
            );
        }

        $this->connection->delete('communication__queue')
            ->where([
                'communication_id' => $this->communicationModel->getId()
            ])
            ->execute();

        $this->connection->delete('communication__group_email')
            ->where([
                'communication_id' => $this->communicationModel->getId()
            ])
            ->execute();

        $communicationMapper = new CommunicationMapper($this->connection);
        $contentMapper = new ContentMapper($this->connection);
        $contentModel = $contentMapper->selectById($this->communicationModel->getContentId());

        if (!is_valid_communication_content($contentModel)) {
            $this->communicationModel->setUpdateDateTime(pyncer_date_time());
            $this->communicationModel->setStatus(CommunicationStatus::FAILED);
            $communicationMapper->update($this->communicationModel);

            throw new QueueException(
                'Communication content is invalid.',
                QueueExceptionCode::CONTENT->value,
            );
        }

        $contentDataManager = new ContentValueManager($this->connection, $contentModel->getId());

        $contentValueManager = new ContentValueManager($this->connection, $contentModel->getId());
        $contentValueManager->load(
            'to_contact_id',
            'group_email',
        );

        $groupEmail = $contentValueManager->getBool('group_email');

        $queueEmails = (
            $this->communicationModel->getType() === null ||
            $this->communicationModel->getType() === CommunicationType::EMAIL
        );

        $queuePhones = (
            $this->communicationModel->getType() === null ||
            $this->communicationModel->getType() === CommunicationType::SMS
        );

        $hasContacts = false;

        if ($groupEmail && $queueEmails) {
            try {
                if ($this->insertGroupEmail(
                    $this->communicationModel,
                    $contentModel,
                )) {
                    $hasContacts = true;
                }
            } catch (Exception $error) {
                $this->communicationModel->setUpdateDateTime(pyncer_date_time());
                $this->communicationModel->setStatus(CommunicationStatus::FAILED);
                $communicationMapper->update($this->communicationModel);

                throw new QueueException(
                    'Error inserting group email.',
                    QueueExceptionCode::UNKNOWN->value,
                    $error,
                );
            }
        } elseif ($queueEmails) {
            $contentDataManager->load('to_emails');

            $toEmails = $contentDataManager->getString('to_emails');
            $toEmails = explode_emails($toEmails);

            $toContactId = $contentValueManager->getInt('to_contact_id', null);
            if ($toContactId !== null) {
                $contactToEmails = $this->getContactEmails($toContactId);
                $toEmails = [...$contactToEmails, ...$toEmails];
            }

            $usedEmails = [];

            foreach ($toEmails as $toEmail) {
                try {
                    $contactProfileId = $toEmail[2] ?? null;
                    $toEmail = clean_email($toEmail);

                    if ($toEmail === null) {
                        continue;
                    }

                    $normalizedEmail = strtolower($toEmail[1]);

                    if (in_array($normalizedEmail, $usedEmails)) {
                        continue;
                    }

                    $usedEmails[] = $normalizedEmail;

                    $hasContacts = true;

                    $this->insertQueue(
                        $this->communicationModel,
                        $toEmail[1],
                        $toEmail[0],
                        null,
                        $contactProfileId,
                    );
                } catch (Exception $error) {
                    $this->communicationModel->setUpdateDateTime(pyncer_date_time());
                    $this->communicationModel->setStatus(CommunicationStatus::FAILED);
                    $communicationMapper->update($this->communicationModel);

                    throw new QueueException(
                        'Error inserting email into queue.',
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
                $toPhones = [...$contactToPhones, ...$toPhones];
            }

            $usedPhones = [];

            foreach ($toPhones as $toPhone) {
                try {
                    if (is_string($toPhone)) {
                        $toPhone = [$toPhone, null];
                        $contactProfileId = null;
                    } else {
                        $contactProfileId = $toEmail[2] ?? null;
                    }
                    $toPhone = clean_phone($toPhone);

                    if ($toPhone === null) {
                        continue;
                    }

                    $normalizedPhone = preg_replace('/[^\d\+]/', '', $value);

                    if (PYNCER_COMMUNICATION_PHONE_ALLOW_NANP) {
                        if (strlen($normalizedPhone) === 11 &&
                            str_starts_with($normalizedPhone, '1')
                        ) {
                            $normalizedPhone = substr($normalizedPhone, 1);
                        }
                    }

                    if (in_array($normalizedPhone, $usedPhones)) {
                        continue;
                    }

                    $usedPhones[] = $normalizedPhone;

                    $hasContacts = true;

                    $this->insertQueue(
                        $this->communicationModel,
                        $toPhone[1],
                        null,
                        $toPhone[0],
                        $contactProfileId,
                    );
                } catch (Exception $error) {
                    $this->communicationModel->setUpdateDateTime(pyncer_date_time());
                    $this->communicationModel->setStatus(CommunicationStatus::FAILED);
                    $communicationMapper->update($this->communicationModel);

                    throw new QueueException(
                        'Error inserting phone into queue.',
                        QueueExceptionCode::UNKNOWN->value,
                        $error,
                    );
                }
            }
        }

        if (!$hasContacts) {
            $this->communicationModel->setUpdateDateTime(pyncer_date_time());
            $this->communicationModel->setStatus(CommunicationStatus::FAILED);
            $communicationMapper->update($this->communicationModel);

            throw new QueueException(
                'No emails to send to.',
                QueueExceptionCode::CONTACTS->value,
            );
        }

        $this->communicationModel->setUpdateDateTime(pyncer_date_time());
        $this->communicationModel->setStatus(CommunicationStatus::QUEUED);
        $communicationMapper->update($this->communicationModel);
    }

    protected function insertGroupEmail(
        CommunicationModel $communicationModel,
        ContentModel $contentModel,
    ): bool
    {
        $contentDataManager = new ContentValueManager(
            $this->connection,
            $contentModel->getId()
        );
        $contentDataManager->load(
            'to_emails',
            'to_cc_emails',
            'to_bcc_emails',
        );

        $contentValueManager = new ContentValueManager(
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

        $model = new GroupEmailModel();
        $model->setCommunicationId($communicationModel->getId());
        $model->setEmails($toEmails);
        $model->setCcEmails($toCcEmails);
        $model->setBccEmails($toBccEmails);

        $mapper = new GroupEmailMapper($this->connection);
        return $mapper->insert($model);
    }

    protected function insertQueue(
        CommunicationModel $communicationModel,
        ?string $name,
        ?string $email,
        ?string $phone,
        ?int $contactProfileId = null,
    ): bool
    {
        $model = new QueueModel();
        $model->setCommunicationId($communicationModel->getId());
        $model->setName($name);
        $model->setEmail($email);
        $model->setPhone($phone);

        $mapper = new QueueMapper($this->connection);
        if (!$mapper->insert($model)) {
            return false;
        }

        if ($contactProfileId !== null) {
            $this->connection->insert('communication__queue__contact_profile')
                ->values([
                    'communication_queue_id' => $model->getId(),
                    'contact_profile_id' => $contactProfileId,
                ])
                ->execute();
        }

        return true;
    }

    protected function getContactEmails(int $contactId): array
    {
        if (!SnyppetManager::getInstance()->has('contact')) {
            return [];
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

            $emails[] = [$profile->getEmail(), $profile->getName(), $profile->getId()];
        }

        return $emails;
    }

    protected function getContactPhones(int $contactId): array
    {
        if (!SnyppetManager::getInstance()->has('contact')) {
            return [];
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

            $phones[] = [$profile->getPhone(), $profile->getName(), $profile->getId()];
        }

        return $phones;
    }
}
