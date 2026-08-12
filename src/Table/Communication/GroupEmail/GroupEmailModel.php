<?php
namespace Pyncer\Snyppet\Communication\Table\Communication\GroupEmail;

use Pyncer\Data\Model\AbstractModel;
use Pyncer\Snyppet\Communication\GroupEmail\GroupEmailStatus;

class GroupEmailModel extends AbstractModel
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

    public function getEmails(): string
    {
        return $this->get('emails');
    }
    public function setEmails(string $value): static
    {
        $this->set('emails', $value);
        return $this;
    }

    public function getCcEmails(): ?string
    {
        return $this->get('cc_emails');
    }
    public function setCcEmails(?string $value): static
    {
        $this->set('cc_emails', $this->nullify($value));
        return $this;
    }

    public function getBccEmails(): ?string
    {
        return $this->get('bcc_emails');
    }
    public function setbccEmails(?string $value): static
    {
        $this->set('bcc_emails', $this->nullify($value));
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

    public function getSent(): bool
    {
        return $this->get('sent');
    }
    public function setSent(bool $value): static
    {
        $this->set('sent', $value);
        return $this;
    }

    public static function getDefaultData(): array
    {
        return [
            'id' => 0,
            'communication_id' => 0,
            'emails' => '',
            'cc_emails' => null,
            'bcc_emails' => null,
            'message_data' => null,
            'sent' => false,
        ];
    }
}
