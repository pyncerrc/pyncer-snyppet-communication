<?php
namespace Pyncer\Snyppet\Communication\Message\Email;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Snyppet\Communication\CommunicationType;
use Pyncer\Snyppet\Communication\Exception\MessageException;
use Pyncer\Snyppet\Communication\Exception\MessageExceptionCode;
use Pyncer\Snyppet\Communication\Message\Email\EmailMessageInterface;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Content\Table\Content\DataManager as ContentDataManager;
use Pyncer\Snyppet\Content\Table\Content\ValueManager as ContentValueManager;

use function Pyncer\he as pyncer_he;
use function Pyncer\Snyppet\Communication\html_to_plain;
use function Pyncer\Snyppet\Communication\is_valid_communication_content;
use function Pyncer\Snyppet\Communication\plain_to_html;

class EmailMessage implements EmailMessageInterface
{
    protected array $attachments = [];

    public function __construct(
        protected string $subject,
        protected ?string $htmlBody = null,
        protected ?string $plainBody = null,
        protected ?string $fromEmail = null,
        protected ?string $fromName = null,
        protected ?string $replyToEmail = null,
        protected ?string $replyToName = null,
    ) {
        if (trim($subject) === '') {
            throw new InvalidArgumentException('Subject is empty.');
        }
    }

    public function addAttachment(string $uri, string $filename): static
    {
        $this->attachments[] = [$uri, $filename];
        return $this;
    }
    public function clearAttachments(): static
    {
        $this->attachments = [];

        return $this;
    }
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
    public function setSubject(string $value): static
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('Subject is empty.');
        }

        $this->subject = $value;
        return $this;
    }

    public function getBody(): null|string|array
    {
        if ($this->htmlBody === null && $this->plainBody === null) {
            return null;
        }

        return [
            'text/html' => $this->htmlBody,
            'text/plain' => $this->plainBody,
        ];
    }

    public function getHtmlBody(bool $convert = false): ?string
    {
        if ($this->htmlBody === null &&
            $this->plainBody !== null &&
            $convert
        ) {
            return plain_to_html($this->plainBody);
        }

        return $this->htmlBody;
    }
    public function setHtmlBody(?string $value): static
    {
        if (trim($value) === '') {
            $value = null;
        }

        $this->htmlBody = $value;

        return $this;
    }

    public function getPlainBody(bool $convert = false): ?string
    {
        if ($this->plainBody === null &&
            $this->htmlBody !== null &&
            $convert
        ) {
            return html_to_plain($this->htmlBody);
        }

        return $this->plainBody;
    }
    public function setPlainBody(?string $value): static
    {
        if (trim($value) === '') {
            $value = null;
        }

        $this->plainBody = $value;

        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }
    public function setFromEmail(?string $value): static
    {
        if (trim($value ?? '') == '') {
            $value = null;
        }

        $this->fromEmail = $value;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }
    public function setFromName(?string $value): static
    {
        if (trim($value ?? '') == '') {
            $value = null;
        }

        $this->fromName = $value;

        return $this;
    }

    public function getFrom(): null|string|array
    {
        if ($this->fromEmail === null) {
            return null;
        }

        return [$this->fromEmail, $this->fromName];
    }
    public function setFrom(?string $email, ?string $name = null): static
    {
        if ($email === '') {
            $email = null;
        }

        $this->fromEmail = $email;

        if ($name === '') {
            $name = null;
        }

        $this->fromName = $name;

        return $this;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }
    public function setReplyToEmail(?string $value): static
    {
        if (trim($value ?? '') == '') {
            $value = null;
        }

        $this->replyToEmail = $value;

        return $this;
    }

    public function getReplyToName(): ?string
    {
        return $this->replyToName;
    }
    public function setReplyToName(?string $value): static
    {
        if (trim($value ?? '') == '') {
            $value = null;
        }

        $this->replyToName = $value;

        return $this;
    }

    public function getReplyTo(): null|string|array
    {
        if ($this->replyToEmail === null) {
            return null;
        }

        return [$this->replyToEmail, $this->replyToName];
    }
    public function setReplyTo(?string $email, ?string $name = null): static
    {
        if ($email === '') {
            $email = null;
        }

        $this->replyToEmail = $email;

        if ($name === '') {
            $name = null;
        }

        $this->replyToName = $name;

        return $this;
    }

    public static function fromContentId(
        ConnectionInterface $connection,
        int $contentId,
    ): EmailMessage
    {
        $mapper = new ContentMapper($connection);
        $conentModel = $mapper->selectById($contentId);

        if ($contendModel === null) {
            throw new MessageException(
                'Communication content not found.',
                MessageExceptionCode::CONTENT->value,
            );
        }

        return static::fromContentModel($connection, $contentModel);
    }
    public static function fromContentModel(
        ConnectionInterface $connection,
        ContentModel $contentModel,
    ): EmailMessage
    {
        if (!is_valid_communication_content(
            $contentModel,
            CommunicationType::EMAIL,
        )) {
            throw new MessageException(
                'Communication content is invalid.',
                MessageExceptionCode::CONTENT->value,
            );
        }

        $dataManager = new ContentDataManager($connection, $contentModel->getId());
        $dataManager->load(
            'body',
            'email_body',
            'plain_body',
            'plain_email_body',
            'html_body',
            'html_email_body',
        );

        $valueManager = new ContentValueManager($connection, $contentModel->getId());
        $valueManager->load(
            'subject',
            'from_email',
            'from_name',
            'reply_to_email',
            'reply_to_name',
        );

        $subject = $valueManager->getString('subject', null);
        if ($subject === null) {
            throw new MessageException(
                'Communication content has no subject.',
                MessageExceptionCode::CONTENT->value,
            );
        }

        $fromEmail = $valueManager->getString('from_email', null);
        $fromName = $valueManager->getString('from_name', null);

        $body = $dataManager->getString('body', null);
        $emailBody = $dataManager->getString('email_body', null);

        if ($emailBody !== null) {
            $body = $emailBody;
            $type = $dataManager->getType('email_body');
        } else {
            $type = $dataManager->getType('body');
        }

        $htmlBody = $dataManager->getString('html_body', null);
        $htmlEmailBody = $dataManager->getString('html_email_body', null);
        $htmlBody = $htmlEmailBody ?? $htmlBody;

        $plainBody = $dataManager->getString('plain_body', null);
        $textEmailBody = $dataManager->getString('plain_email_body', null);
        $plainBody = $textEmailBody ?? $plainBody;

        if ($body !== null && $htmlBody === null && $plainBody === null) {
            // TODO: Support markdown
            if ($type === 'text/plain') {
                $plainBody = $body;
            } else {
                $htmlBody = $body;
            }
        }

        $message = new EmailMessage(
            subject: $subject,
            htmlBody: $htmlBody,
            plainBody: $plainBody,
            fromEmail: $fromEmail,
            fromName: $fromName,
        );

        $replyToEmail = $valueManager->getString('reply_to_email', null);
        if ($replyToEmail !== null) {
            $replyToName = $valueManager->getString('reply_to_email', null);
            $message->setReplyTo($replyToEmail, $replyToName);
        }

        #TODO: Attachments

        return $message;
    }
}
