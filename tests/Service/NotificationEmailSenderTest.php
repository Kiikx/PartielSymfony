<?php

namespace App\Tests\Service;

use App\Entity\GuardUser;
use App\Entity\Notification;
use App\Service\NotificationEmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class NotificationEmailSenderTest extends TestCase
{
    public function testSendsEmailAndMarksNotificationAsSent(): void
    {
        $notification = $this->createNotification();
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (RawMessage $message): bool {
                return $message instanceof Email
                    && $message->getSubject() === 'Transfert interne prepare'
                    && $message->getTo()[0]->getAddress() === 'guard@pas.test';
            }));

        (new NotificationEmailSender($mailer, 'no-reply@pas.test'))->send($notification);

        self::assertSame(Notification::STATUS_SENT, $notification->getStatus());
        self::assertNotNull($notification->getSentAt());
    }

    public function testMarksNotificationAsFailedWhenMailerFails(): void
    {
        $notification = $this->createNotification();
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('SMTP unavailable'));

        (new NotificationEmailSender($mailer, 'no-reply@pas.test'))->send($notification);

        self::assertSame(Notification::STATUS_FAILED, $notification->getStatus());
        self::assertNull($notification->getSentAt());
    }

    public function testFailsNonEmailNotificationWithoutSending(): void
    {
        $notification = $this->createNotification()->setChannel(Notification::CHANNEL_SYSTEM);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        (new NotificationEmailSender($mailer, 'no-reply@pas.test'))->send($notification);

        self::assertSame(Notification::STATUS_FAILED, $notification->getStatus());
    }

    private function createNotification(): Notification
    {
        $recipient = new GuardUser();
        $recipient
            ->setEmail('guard@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $recipient->setBadgeNumber('PAS-G-001');

        return (new Notification())
            ->setRecipient($recipient)
            ->setSubject('Transfert interne prepare')
            ->setChannel(Notification::CHANNEL_EMAIL)
            ->setStatus(Notification::STATUS_PENDING);
    }
}
