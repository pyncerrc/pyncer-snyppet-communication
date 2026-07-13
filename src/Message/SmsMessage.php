<?php
namespace Pyncer\Snyppet\Communication\Message;

use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Communication\Exception\MessageException;
use Pyncer\Snyppet\Communication\Exception\MessageExceptionCode;
use Pyncer\Snyppet\Content\Table\Content\ContentMapper;
use Pyncer\Snyppet\Content\Table\Content\ContentModel;
use Pyncer\Snyppet\Content\Table\Content\DataManager as ContentDataManager;
use Pyncer\Snyppet\Content\Table\Content\ValueManager as ContentValueManager;

use function Pyncer\he as pyncer_he;
use function Pyncer\Snyppet\Communication\html_to_text;

class SmsMessage
{
    protected array $attachments = [];

    public function __construct(
        protected ?string $body = null,
        protected ?string $fromPhone = null,
    ) {}

    public function getBody(): ?string:
    {
        return $this->body;
    }
    public function setBody(?string $value): static
    {
        if (trim($value) === '') {
            $value = null;
        }

        $this->body = $value;

        return $this;
    }

    public function getFromPhone(): string
    {
        return $this->fromPhone;
    }
    public function setFromPhone(?string $value): static
    {
        if (trim($value ?? '') == '') {
            $value = null;
        }

        $this->fromPhone = $value;

        return $this;
    }

    public static function fromContentId(
        ConnectionInterface, $connection,
        int $contentId,
    ): SmsMessage
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
    ): SmsMessage
    {
        if (!static::isValidSmsContent($contentModel)) {
            throw new MessageException(
                'Communication content is invalid.'
                MessageExceptionCode::CONTENT->value,
            );
        }

        $dataManager = new ContentDataManager($connection, $contentModel->getId());
        $dataManager->load(
            'body',
            'text_body',
            'text_sms_body',
        );

        $valueManager = new ContentValueManager($connection, $contentModel->getId());
        $valueManager->load(
            'from_phone',
        );

        $fromPhone = $valueManager->getString('from_phone', null);

        $body = $dataManager->getString('body', null);
        $textBody = $dataManager->getString('text_body', null);
        $textSmsBody = $dataManager->getString('text_sms_body', null);

        $textBody = $textSmsBody ?? $textBody;

        if ($body !== null && $textBody === null) {
            if ($dataManager->getType('body') === 'text/html') {
                $textBody = html_to_text($body);
            }
        }

        $message = new SmsMessage(
            body: $htmlBody,
            fromPhone: $fromPhone,
        );

        return $message;
    }

    protected static function isValidSmsContent(ContentModel $contentModel): bool
    {
        if ($contentModel->getType() !== 'sms' &&
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
