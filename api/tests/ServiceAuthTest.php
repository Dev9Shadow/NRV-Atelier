<?php

namespace nrv\tests;

use PHPUnit\Framework\TestCase;
use nrv\core\application\usecases\ServiceAuth;
use nrv\core\application\ports\UtilisateurRepositoryInterface;
use nrv\core\application\ports\AuthTokenProviderInterface;
use nrv\core\domain\entities\Utilisateur;
use nrv\core\domain\exceptions\NonAuthentifieException;

class ServiceAuthTest extends TestCase
{
    public function testSigninSuccess()
    {
        $repo = $this->createMock(UtilisateurRepositoryInterface::class);
        $tokenProvider = $this->createMock(AuthTokenProviderInterface::class);

        $hashed = password_hash("Alice2027!", PASSWORD_BCRYPT);
        $user = new Utilisateur("alice@nrv.fr", $hashed, "Martin", "Alice", "USER", 1);

        $repo->method('findByEmail')->willReturn($user);
        $tokenProvider->expects($this->once())->method('createToken')->willReturn("fake-jwt-token");

        $service = new ServiceAuth($repo, $tokenProvider);
        $res = $service->signin("alice@nrv.fr", "Alice2027!");

        $this->assertEquals("fake-jwt-token", $res['token']);
        $this->assertEquals("alice@nrv.fr", $res['utilisateur']['email']);
    }

    public function testSigninInvalidPassword()
    {
        $this->expectException(NonAuthentifieException::class);

        $repo = $this->createMock(UtilisateurRepositoryInterface::class);
        $tokenProvider = $this->createMock(AuthTokenProviderInterface::class);

        $hashed = password_hash("Alice2027!", PASSWORD_BCRYPT);
        $user = new Utilisateur("alice@nrv.fr", $hashed, "Martin", "Alice", "USER", 1);

        $repo->method('findByEmail')->willReturn($user);

        $service = new ServiceAuth($repo, $tokenProvider);
        $service->signin("alice@nrv.fr", "MauvaisMDP");
    }
}