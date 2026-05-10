<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Main\Establishment;
use App\Entity\Main\User;
use App\Security\Voter\EstablishmentVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EstablishmentVoterTest extends TestCase
{
    private EstablishmentVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new EstablishmentVoter();
    }

    #[Test]
    public function itGrantsWhenUserIsMemberOfEstablishment(): void
    {
        $user = new User();
        $establishment = new Establishment();
        $user->addEstablishment($establishment);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[Test]
    public function itDeniesWhenUserIsNotMember(): void
    {
        $user = new User();
        $establishment = new Establishment(); // pas ajouté à $user

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    public function itDeniesWhenTokenHasNoUser(): void
    {
        $establishment = new Establishment();

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $establishment, [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    public function itAbstainsOnUnsupportedSubject(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $result = $this->voter->vote($token, new stdClass(), [EstablishmentVoter::SWITCH]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function itAbstainsOnUnsupportedAttribute(): void
    {
        $establishment = new Establishment();
        $token = $this->createStub(TokenInterface::class);

        $result = $this->voter->vote($token, $establishment, ['DELETE']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
