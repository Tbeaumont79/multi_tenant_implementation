<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Main\Establishment;
use App\Entity\Main\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<self::SWITCH, Establishment>
 */
final class EstablishmentVoter extends Voter
{
    public const SWITCH = 'SWITCH';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::SWITCH === $attribute && $subject instanceof Establishment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->canSwitchEstablishment($user, $subject);
    }

    private function canSwitchEstablishment(User $user, Establishment $establishment): bool
    {
        return $user->getEstablishments()->contains($establishment);
    }
}
