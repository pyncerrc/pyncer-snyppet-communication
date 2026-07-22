<?php
namespace Pyncer\Snyppet\Communication\Transport\Email;

use Exception;
use Pyncer\Snyppet\Communication\Exception\TransportException;
use Pyncer\Snyppet\Communication\Exception\TransportExceptionCode;
use Pyncer\Snyppet\Communication\Message\MessageInterface;
use Pyncer\Snyppet\Communication\Transport\ReplaceMessageDataTrait;
use Pyncer\Snyppet\Communication\Transport\TransportInterface;

use function Pyncer\Snyppet\Communicatoin\Email\explode_email;
use function Pyncer\Snyppet\Communicatoin\Email\explode_emails;
use function Pyncer\Snyppet\Communicatoin\Email\clean_emails;

class SmtpTransport implements TransportInterface
{
    use ReplaceMessageDataTrait;

    public function __construct(
        protected string $host,
        protected int $port,
        protected string $username,
        protected string $password,
    ) {}

    public function send(
        string|array $to,
        MessageInterface $message,
        array $data = [],
        array $params = [],
    ): void
    {
        if (!($message instanceof EmailMessageInterface)) {
            throw new TransportException(
                'Expected email message.',
                TransportExceptionCode::MESSAGE->value,
                $e,
            );
        }

        $mailer = new PHPMailer(
            exceptions: true
        );

        // Enable verbose debug output
        // $message->SMTPDebug = SMTP::DEBUG_SERVER;

        // Send using SMTP
        $mailer->isSMTP();

        $mailer->Host = $this->host;
        $mailer->Port = $this->port;

        $mailer->SMTPAuth = true;
        $mailer->Username = $this->username;
        $mailer->Password = $this->password;

        $mailer->SMTPSecure = (
            $this->port === 587 ?
            PHPMailer::ENCRYPTION_STARTTLS :
            PHPMailer::ENCRYPTION_SMTPS
        );

        $mailer->CharSet = PHPMailer::CHARSET_UTF8;

        $subject = $message->getSubject();
        if ($subject !== null) {
            $mailer->Subject = $this->replaceMessageData($subject, $data);
        }

        $body = $message->getBody();
        if ($body !== null) {
            if ($body['html'] ?? null !== null) {
                $mailer->isHTML(true);
                $mailer->Body = $this->replaceMessageData($body['html'], $data, true);

                if ($body['text'] ?? null !== null) {
                    $mailer->AltBody = $this->replaceMessageData($body['text'], $data);
                }
            } elseif ($body['text'] ?? null !== null) {
                $mailer->Body = $this->replaceMessageData($body['text'], $data);
            }
        }

        $from = $message->getFrom();
        if ($from !== null) {
            if (is_string($from)) {
                $from = explode_email($from);
            }

            $mailer->setFrom(
                $from[0],
                $from[1] ?? '',
            );
        }

        $replyTo = $message->getReplyTo();
        if ($replyTo !== null) {
            if (is_string($replyTo)) {
                $replyTo = explode_email($replyTo);
            }

            $mailer->addReplyTo(
                $replyTo[0],
                $replyTo[1] ?? '',
            );
        }

        foreach ($message->getAttachments() as $attachment) {
            if (str_starts_with($attachment[0], 'https://') ||
                str_starts_with($attachment[0], 'http://')
            ) {
                $fileContent = file_get_contents($attachment[0]);
                if ($fileContent !== false) {
                    $mail->addStringAttachment(
                        $fileContent,
                        $attachment[1],
                    );
                }
            } else {
                $message->addAttachment($attachment[0], $attachment[1]);
            }
        }

        if (is_string($to)) {
            $to = explode_emails($to);
        }
        $to = clean_emails($to);

        foreach ($to as $email) {
            $mailer->addAddress($email[0], $email[1] ?? '');
        }

        $ccEmails = $params['cc_emails'] ?? null;
        if ($ccEmails !== null) {
            if (is_string($ccEmails)) {
                $ccEmails = explode_emails($ccEmails);
            }
            $ccEmails = clean_emails($ccEmails);

            foreach ($ccEmails as $email) {
                $mailer->addCC($email[0], $email[1] ?? '');
            }
        }

        $bccEmails = $params['bcc_emails'] ?? null;
        if ($bccEmails !== null) {
            if (is_string($bccEmails)) {
                $bccEmails = explode_emails($bccEmails);
            }
            $bccEmails = clean_emails($bccEmails);

            foreach ($bccEmails as $email) {
                $mailer->addBCC($email[0], $email[1] ?? '');
            }
        }

        try {
            if (!$mailer->send()) {
                throw new TransportException(
                    'Email could not be sent.',
                    TransportExceptionCode::UNKNOWN->value,
                );
            }
        } catch(Exception $e) {
            throw new TransportException(
                'Email could not be sent.',
                TransportExceptionCode::UNKNOWN->value,
                $e,
            );
        }
    }
}
