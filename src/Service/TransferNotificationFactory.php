<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Transfer;
use App\Entity\User;

final class TransferNotificationFactory
{
    public function createTransferPreparedNotification(Transfer $transfer, User $recipient): Notification
    {
        return (new Notification())
            ->setRecipient($recipient)
            ->setSubject($this->createSubject($transfer))
            ->setChannel(Notification::CHANNEL_EMAIL)
            ->setStatus(Notification::STATUS_PENDING);
    }

    private function createSubject(Transfer $transfer): string
    {
        $inmate = $transfer->getInmate();
        $inmateLabel = $inmate !== null ? $inmate->getFullName() : 'detenu';

        if ($transfer->getType() === Transfer::TYPE_EXTERNAL) {
            return sprintf(
                'Transfert externe prepare: %s vers %s',
                $inmateLabel,
                $transfer->getExternalDestination() ?? 'destination externe',
            );
        }

        return sprintf(
            'Transfert interne prepare: %s vers cellule %s',
            $inmateLabel,
            $transfer->getToCell()?->getNumber() ?? 'inconnue',
        );
    }
}
