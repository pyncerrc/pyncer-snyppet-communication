<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\Queue;

use Pyncer\Data\Model\AbstractModel;
use Pyncer\Snyppet\Communication\Queue\QueueStatus;

class QueueModel extends AbstractModel
{
    public function getCommunicationId(): ?int
    {
        return $this->get('communication_id');
    }
    public function setCommunicationId(?int $value): static
    {
        $this->set('communication_id', $this->nullify($value));
        return $this;
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }
    public function setName(?string $value): static
    {
        $this->set('name', $this->nullify($value));
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }
    public function setEmail(?string $value): static
    {
        $this->set('email', $this->nullify($value));
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->get('phone');
    }
    public function setPhone(?string $value): static
    {
        $this->set('phone', $this->nullify($value));
        return $this;
    }

    public function getMessageData(): ?array
    {
        $value = $this->get('message_data');

        if (is_string($value)) {
            if (!json_validate($value)) {
                $value = null;
            } else {
                $value = json_decode($value, true);
            }
        }

        return $value;
    }
    public function setMessageData(null|string|array $value): static
    {
        $this->set('message_data', $this->nullify($value));
        return $this;
    }

    public function getStatus(): QueueStatus
    {
        $value = $this->get('status');
        return QueueStatus::from($value);
    }
    public function setStatus(string|QueueStatus $value): static
    {
        if ($value instanceof QueueStatus) {
            $value = $value->value;
        }

        $this->set('status', $value);
        return $this;
    }

    public function getAttempts(): int
    {
        return $this->get('attempts');
    }
    public function setAttempts(int $value): static
    {
        $this->set('attempts', $value);
        return $this;
    }

    public function getOpened(): bool
    {
        return $this->get('opened');
    }
    public function setOpened(bool $value): static
    {
        $this->set('opened', $value);
        return $this;
    }

    public function getUnsubscribed(): bool
    {
        return $this->get('unsubscribed');
    }
    public function setUnsubscribed(bool $value): static
    {
        $this->set('unsubscribed', $value);
        return $this;
    }

    public function getComplained(): bool
    {
        return $this->get('complained');
    }
    public function setComplained(bool $value): static
    {
        $this->set('complained', $value);
        return $this;
    }

    public static function getDefaultData(): array
    {
        return [
            'id' => 0,
            'communication_id' => 0,
            'name' => null,
            'email' => null,
            'phone' => null,
            'message_data' => null,
            'status' => 'queued',
            'attempts' => 0,
            'opened' => false,
            'unsubscribed' => false,
            'complained' => false,
        ];
    }
}
