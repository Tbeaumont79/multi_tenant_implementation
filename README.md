# Context

Le projet final de mon école DonkeySchool était un projet client pour Andreane, un logiciel de gestion de patientèle destiné principalement aux infirmiers libéraux. Le client souhaitait porter cette solution sur le Web. Nous avons donc réalisé une web app en équipe de 10 personnes. Ce projet illustre la partie sur laquelle j'ai travaillé : la mise en place de l'architecture multi tenant (scopée côté back).

# Multi Tenant Implementation

Backend Symfony démontrant une architecture multi tenant avec isolation database per tenant et provisionnement automatique de nouveaux cabinets depuis un back office EasyAdmin.

Le contexte métier est un SaaS médical (cabinet de médecins) où chaque cabinet possède sa propre base Postgres pour garantir l'isolation des dossiers patients (NIR), conformément aux exigences HDS et CNIL.

## Contribution open source

Une PR mergée sur le bundle `hakam/multi-tenancy-bundle` (sur lequel ce projet s'appuie) ajoute le flag `--dbid` aux commandes `tenant:database:create`.

Lien : https://github.com/RamyHakam/multi_tenancy_bundle/pull/63

## Ce que le projet prouve

* **Voter unique pour deux flux d'authentification** : `EstablishmentVoter` (`src/Security/Voter/EstablishmentVoter.php`) est consommé à la fois par `DashboardController` (Twig session) et par `ApiTenantAuthorizationListener` (JWT plus header `X-Tenant-Id`). Une seule règle d'autorisation à maintenir.
* **Détection automatique des ressources tenant** : le listener API n'opère que sur les ApiResources qui implémentent `Hakam\MultiTenancyBundle\Model\TenantEntityInterface`. Aucune liste de paths à maintenir, ajouter une nouvelle entité tenant suffit.
* **Provisionnement automatique** : créer un cabinet via `/admin` déclenche `TenantProvisioner` (`src/Service/Tenant/TenantProvisioner.php`) qui crée la base Postgres et applique les migrations dans un sous processus PHP isolé du request lifecycle.
* **Test d'invariant** : `TenantIsolationTest` (`tests/Functional/Security/TenantIsolationTest.php`) prouve qu'un user authentifié, par session ou par JWT, ne peut accéder aux données d'un cabinet qu'il ne possède pas. C'est l'invariant central de l'application.

## Architecture

```
                       Browser                       API client
                          |                              |
                  /login (form)                  POST /api/login
                  /app/* (session)                /api/* (JWT plus
                          |                       X Tenant Id header)
                          v                              v
              DashboardController          ApiTenantAuthorizationListener
              PatientController                          |
                          |                              |
                          +---------- common ------------+
                                       |
                                       v
                            EstablishmentVoter
                          (single source of truth)
                                       |
                                       v
                           dispatch SwitchDbEvent
                                       |
                                       v
                            TenantEntityManager
                                       |
                          +-----+-----+-----+-----+
                          |     |     |     |     |
                    cabinet1 cabinet2 cabinet3 cabinet4 ...
                  (Postgres) (Postgres)        (each isolated)
```

## Stack

* Symfony
* PostgreSQL 16
* Doctrine ORM avec deux entity managers (`default` pour la base Main, `tenant` pour les bases cabinet)
* `hakam/multi-tenancy-bundle` pour la résolution et le switch tenant
* API Platform plus `lexik/jwt-authentication-bundle` pour l'API
* EasyAdmin pour le back office
* PHPUnit, PHPStan niveau 8, PHP CS Fixer preset Symfony

## Quick start

Préalable : Postgres sur `127.0.0.1:5432` avec utilisateur `app` et mot de passe `app`. Soit via Docker (`make docker-up`), soit en local.

> **Note** — Le fichier `.env` est volontairement versionné avec des valeurs renseignées (`APP_SECRET`, `JWT_PASSPHRASE`, credentials Postgres) pour rendre l'initialisation du projet immédiate en contexte démo : `git clone` puis `make install` puis `make db-reset` doivent suffire. En production, ces secrets seraient déplacés dans `.env.local` (gitignored).

```bash
make install      # composer install plus génération de la paire de clés JWT
make docker-up    # start docker postgres container
make db-reset     # crée la base Main, applique les migrations, charge les fixtures, crée les bases tenant et leurs migrations, charge les fixtures tenant
make serve        # démarre le serveur Symfony en arrière plan
```

L'application est accessible sur `https://localhost:8000`.

### Comptes de démonstration

| Email | Password | Rôle | Cabinets |
| --- | --- | --- | --- |
| `john@icloud.com` | `Password123!` | ROLE_USER | aucun |
| `emma@hotmail.com` | `Titi123!` | ROLE_USER | 1, 2 |
| `lucas@protonmail.com` | `Tutu123!` | ROLE_USER | 3 |
| `daniel@free.fr` | `PowerTest123!` | ROLE_USER | 4 |
| `admin@myapp.com` | `SuperAdmin123!` | ROLE_ADMIN | aucun |

## API

La documentation Swagger interactive est exposée sur `/api`. Authentifier la UI via le bouton "Authorize" (deux entrées : `JWT` au format `Bearer <token>`, et `TenantId` avec un identifiant numérique).

## Quality gates

```bash
make ci
# => php cs fixer dry run, puis phpstan analyse, puis phpunit
```

* PHPStan niveau 8 avec plugins `phpstan-symfony` et `phpstan-doctrine`. Une baseline de 13 entrées couvre la dette de typage des entités, à nettoyer progressivement.
* PHP CS Fixer preset `@Symfony` plus `declare_strict_types`.
* PHPUnit 13 strict (`failOnDeprecation`, `failOnNotice`, `failOnWarning`). 9 tests, 19 assertions.

## Tests

```bash
make test
```

* `tests/Unit/Security/Voter/EstablishmentVoterTest.php` : 5 cas (granted, denied, abstain sur subject ou attribut non supporté, denied sur token sans user).
* `tests/Functional/Security/TenantIsolationTest.php` : 4 cas couvrant les deux surfaces, Twig et API. Le test à mentionner explicitement en entretien.

## Structure

```
src/
  Controller/
    Admin/                   # EasyAdmin Dashboard, EstablishmentCrudController, UserCrudController
    DashboardController.php  # /app, switch cabinet
    PatientController.php    # /app/patients/* CRUD
    SecurityController.php   # /login, /logout
    HomeController.php
  Entity/
    Main/                    # User, Establishment, TenantDbConfig (base default)
    Tenant/                  # Patient (base tenant)
  EventListener/
    ApiLoginRateLimitListener.php          # rate limit sur /api/login
    ApiTenantAuthorizationListener.php     # voter sur ressources tenant
    EstablishmentProvisioningListener.php  # provisionnement DB sur création
  Form/
    PatientType.php
  Security/Voter/
    EstablishmentVoter.php   # source unique d'autorisation tenant
  Service/Tenant/
    TenantProvisioner.php    # création DB plus migrations
    TenantSwitcher.php       # switch DB plus check accès
config/packages/
  hakam_multi_tenancy.yaml
  rate_limiter.yaml
  security.yaml
templates/
  admin/dashboard.html.twig
  dashboard/                 # Twig dashboard utilisateur
  patient/                   # forms patient
  security/login.html.twig
tests/
  Functional/Security/TenantIsolationTest.php
  Unit/Security/Voter/EstablishmentVoterTest.php
```
