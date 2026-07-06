<?php

namespace App\Exception;

use App\Entity\Cell;
use App\Entity\Inmate;

final class AssignmentException extends \DomainException
{
    public static function inmateCannotBeAssigned(Inmate $inmate): self
    {
        return new self(sprintf(
            'Le detenu %s ne peut pas etre affecte avec le statut %s.',
            $inmate->getUid(),
            $inmate->getStatus()
        ));
    }

    public static function inmateAlreadyAssigned(Inmate $inmate): self
    {
        return new self(sprintf('Le detenu %s a deja une affectation active.', $inmate->getUid()));
    }

    public static function cellCapacityExceeded(Cell $cell): self
    {
        return new self(sprintf('La cellule %s a atteint sa capacite maximale.', $cell->getNumber()));
    }
}
