<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Plugin\Infrastructure;

use mod_matrix\Matrix;
use mod_matrix\Plugin;

final class MoodleFunctionBasedMatrixUserIdLoader implements Plugin\Domain\MatrixUserIdLoader
{
    public const USER_PROFILE_FIELD_NAME = 'matrix_user_id';

    /**
     * Méthodes d'authentification Moodle acceptées pour la génération
     * automatique de l'identifiant Jokko à partir de l'email.
     */
    private const SUPPORTED_AUTH_METHODS = ['oidc', 'cas', 'manual', 'ldap', 'shibboleth'];

    private $configuration;

    public function __construct(Plugin\Application\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function load(object $user): ?Matrix\Domain\UserId
    {
        global $CFG;

        require_once $CFG->dirroot . '/user/profile/lib.php';

        profile_load_custom_fields($user);

        if (!\property_exists($user, 'profile') || !\is_array($user->profile)) {
            return null;
        }

        $value = $user->profile[self::USER_PROFILE_FIELD_NAME] ?? '';

        if (empty($value) || !\is_string($value) || !\str_starts_with($value, '@')) {
            // Génération automatique : on compose @{localpart-email}:{host}
            // où host est déduit du homeserver configuré dans l'admin du plugin.
            // Appliquée aux utilisateurs OIDC, CAS et manuels (voir SUPPORTED_AUTH_METHODS).
            $generated = $this->autoGenerateFromEmail($user);

            if (null === $generated) {
                return null;
            }

            $value = $generated;
        }

        try {
            return Matrix\Domain\UserId::fromString($value);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    private function autoGenerateFromEmail(object $user): ?string
    {
        if (!isset($user->auth) || !\in_array($user->auth, self::SUPPORTED_AUTH_METHODS, true)) {
            return null;
        }

        $localpart = $this->extractEmailLocalpart($user);

        if (null === $localpart) {
            return null;
        }

        $host = $this->homeserverHost();

        if (null === $host) {
            return null;
        }

        return \sprintf('@%s:%s', $localpart, $host);
    }

    private function extractEmailLocalpart(object $user): ?string
    {
        if (empty($user->email) || !\is_string($user->email)) {
            return null;
        }

        $parts = \explode('@', $user->email, 2);

        if ('' === $parts[0]) {
            return null;
        }

        return $parts[0];
    }

    private function homeserverHost(): ?string
    {
        $homeserverUrl = $this->configuration->homeserverUrl()->toString();

        if ('' === $homeserverUrl) {
            return null;
        }

        $host = \parse_url($homeserverUrl, \PHP_URL_HOST);

        if (!\is_string($host) || '' === $host) {
            return null;
        }

        return $host;
    }
}
