<?php

namespace App\Service;

use App\Entity\Notification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NotificationEmailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderAddress,
    ) {
    }

    public function send(Notification $notification): void
    {
        if ($notification->getChannel() !== Notification::CHANNEL_EMAIL || $notification->getRecipient() === null) {
            $notification->setStatus(Notification::STATUS_FAILED);

            return;
        }

        try {
            $this->mailer->send($this->createEmail($notification));
            $notification
                ->setStatus(Notification::STATUS_SENT)
                ->setSentAt(new \DateTimeImmutable());
        } catch (TransportExceptionInterface) {
            $notification->setStatus(Notification::STATUS_FAILED);
        }
    }

    private function createEmail(Notification $notification): Email
    {
        $recipient = $notification->getRecipient();
        if ($recipient === null) {
            throw new \LogicException('Notification recipient is required to create an email.');
        }

        return (new Email())
            ->from($this->senderAddress)
            ->to($recipient->getEmail())
            ->subject($notification->getSubject())
            ->text($this->createTextBody($notification));
    }

    private function createTextBody(Notification $notification): string
    {
        return sprintf(
            "Bonjour %s,\n\n%s\n\nVeuillez vous connecter a PAS pour consulter le detail.",
            $notification->getRecipient()?->getFullName() ?: 'utilisateur',
            $notification->getSubject(),
        );
    }
}
