<?php
namespace Pyncer\Snyppet\Communication\Table\Communication;

use DateTime;
use DateTimeInterface;
use Pyncer\Data\Model\AbstractModel;
use Pyncer\Snyppet\Communication\CommunicationStatus;
use Pyncer\Snyppet\Communication\CommunicationType;

use function Pyncer\uid as pyncer_uid;
use function Pyncer\date_time as pyncer_date_time;

use const Pyncer\DATE_TIME_FORMAT as PYNCER_DATE_TIME_FORMAT;

class CommunicationModel extends AbstractModel
{
    public function getUid(): string
    {
        return $this->get('uid');
    }
    public function setUid(string $value): static
    {
        $this->set('uid', $value);
        return $this;
    }

    public function getContentId(): ?int
    {
        return $this->get('content_id');
    }
    public function setContentId(?int $value): static
    {
        $this->set('content_id', $this->nullify($value));
        return $this;
    }

    public function getInsertDateTime(): DateTime
    {
        $value = $this->get('insert_date_time');
        return pyncer_date_time($value);
    }
    public function setInsertDateTime(string|DateTimeInterface $value): static
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format(PYNCER_DATE_TIME_FORMAT);
        }
        $this->set('insert_date_time', $value);
        return $this;
    }

    public function getUpdateDateTime(): ?DateTime
    {
        $value = $this->get('update_date_time');
        return pyncer_date_time($value);
    }
    public function setUpdateDateTime(null|string|DateTimeInterface $value): static
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format(PYNCER_DATE_TIME_FORMAT);
        }
        $this->set('update_date_time', $this->nullify($value));
        return $this;
    }

    public function getScheduleDateTime(): ?DateTime
    {
        $value = $this->get('schedule_date_time');
        return pyncer_date_time($value);
    }
    public function setScheduleDateTime(null|string|DateTimeInterface $value): static
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format(PYNCER_DATE_TIME_FORMAT);
        }
        $this->set('schedule_date_time', $this->nullify($value));
        return $this;
    }

    public function getType(): ?CommunicationType
    {
        $value = $this->get('type');

        if ($value === null) {
            return null;
        }

        return UserType::from($value);
    }
    public function setType(null|string|CommunicationType $value): static
    {
        if ($value instanceof CommunicationType) {
            $value = $value->value;
        }

        $this->set('type', $value);
        return $this;
    }

    public function getStatus(): CommunicationStatus
    {
        $value = $this->get('status');
        return UserType::from($value);
    }
    public function setStatus(string|CommunicationStatus $value): static
    {
        if ($value instanceof CommunicationStatus) {
            $value = $value->value;
        }

        $this->set('status', $value);
        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->get('enabled');
    }
    public function setEnabled(bool $value): static
    {
        $this->set('enabled', $value);
        return $this;
    }

    public static function getDefaultData(): array
    {
        $dateTime = pyncer_date_time()->format(PYNCER_DATE_TIME_FORMAT);

        return [
            'id' => 0,
            'uid' => pyncer_uid(),
            'content_id' => 0,
            'insert_date_time' => $dateTime,
            'update_date_time' => null,
            'schedule_date_time' => null,
            'type' => null,
            'status' => 'scheduled',
            'enabled' => false,
        ];
    }
}
