<?php
namespace Pyncer\Snyppet\Communication\Message;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Snyppet\Communication\Exception\MessageException;
use Pyncer\Snyppet\Communication\Exception\MessageExceptionCode;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Content\Table\Content\DataManager as ContentDataManager;
use Pyncer\Snyppet\Content\Table\Content\ValueManager as ContentValueManager;

use function Pyncer\he as pyncer_he;
use function Pyncer\Snyppet\Communication\html_to_text;
use function Pyncer\Snyppet\Communication\text_to_html;

class EmailMessage
{
    protected array $attachments = [];

    public function __construct(
        protected string $subject,
        protected ?string $htmlBody = null,
        protected ?string $textBody = null,
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

    public function getHtmlBody(bool $convert = false): ?string:
    {
        if ($this->htmlBody === null &&
            $this->textBody !== null &&
            $convert
        ) {
            return text_to_html($this->textBody);
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

    public function getTextBody(bool $convert = false): ?string
    {
        if ($this->textBody === null &&
            $this->htmlBody !== null &&
            $convert
        ) {
            return html_to_text($this->htmlBody);
        }

        return $this->textBody;
    }
    public function setTextBody(?string $value): static
    {
        if (trim($value) === '') {
            $value = null;
        }

        $this->textBody = $value;

        return $this;
    }

    public function getFromEmail(): string
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

    public function getFromName(): string
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

    public function getReplyToEmail(): string
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

    public function getReplyToName(): string
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

    public function setReplyTo(?string $email ?string $name = null): static
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
        ConnectionInterface, $connection,
        int $contentId,
    ): EmailMessage
    {
        $mapper = new ContentMapper($connection);
        $conentModel = $mapper->selectById($contentId);

        if ($contendModel === null) {
            throw new MessageException(
                'Communication content not found.'
                MessageExceptionCode::CONTENT->value,
            );
        }

        return static::fromContentModel($connection, $contentModel);
    }
    public static function fromContentModel(
        ConnectionInterface, $connection,
        ContentModel $contentModel,
    ): EmailMessage
    {
        if (!static::isValidEmailContent($contentModel)) {
            throw new MessageException(
                'Communication content is invalid.'
                MessageExceptionCode::CONTENT->value,
            );
        }

        $dataManager = new ContentDataManager($connection, $contentModel->getId());
        $dataManager->load(
            'body',
            'email_body',
            'text_body',
            'text_email_body',
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
                'Communication content has no subject.'
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

        $textBody = $dataManager->getString('text_body', null);
        $textEmailBody = $dataManager->getString('text_email_body', null);
        $textBody = $textEmailBody ?? $textBody;

        if ($body !== null && $htmlBody === null && $textBody === null) {
            // TODO: Support markdown
            if ($type === 'text/plaintext') {
                $textBody = $body;
            } else {
                $htmlBody = $body;
            }
        }

        $message = new EmailMessage(
            subject: $subject,
            htmlBody: $htmlBody,
            textBody: $textBody,
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

    protected static function isValidEmailContent(ContentModel $contentModel): bool
    {
        if ($contentModel->getType() !== 'email' &&
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
