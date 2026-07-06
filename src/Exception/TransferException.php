<?php

namespace App\Exception;

use App\Entity\Cell;
use App\Entity\Inmate;

final class TransferException extends \DomainException
{
    public static function activeAssignmentRequired(Inmate $inmate): self
    {
        return new self(sprintf('Le detenu %s ne possede aucune affectation active.', $inmate->getUid()));
    }

    public static function targetCellRequired(): self
    {
        return new self('Une cellule de destination est obligatoire pour un transfert interne.');
    }

    public static function targetCellMustBeDifferent(Cell $cell): self
    {
        return new self(sprintf('La cellule %s est deja la cellule active du detenu.', $cell->getNumber()));
    }

    public static function targetCellIsFull(Cell $cell): self
    {
        return new self(sprintf('La cellule %s a atteint sa capacite maximale.', $cell->getNumber()));
    }

    public static function externalDestinationRequired(): self
    {
        return new self('Une destination externe est obligatoire pour un transfert externe.');
    }
}
