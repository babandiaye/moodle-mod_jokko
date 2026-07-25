<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd (Trading as Element)
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Plugin\Application;

use mod_matrix\Matrix;

final class Configuration
{
    private $accessToken;
    private $elementUrl;
    private $homeserverUrl;
    private $roomAvatarMxc;

    private function __construct(
        Matrix\Domain\AccessToken $accessToken,
        Matrix\Domain\Url $elementUrl,
        Matrix\Domain\Url $homeserverUrl,
        Matrix\Domain\Url $roomAvatarMxc
    ) {
        $this->accessToken = $accessToken;
        $this->elementUrl = $elementUrl;
        $this->homeserverUrl = $homeserverUrl;
        $this->roomAvatarMxc = $roomAvatarMxc;
    }

    public static function default(): self
    {
        return new self(
            Matrix\Domain\AccessToken::fromString(''),
            Matrix\Domain\Url::fromString('https://matrix-client.matrix.org'),
            Matrix\Domain\Url::fromString(''),
            Matrix\Domain\Url::fromString(''),
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromObject(\stdClass $object): self
    {
        if (!\property_exists($object, 'access_token')) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration should have an "%s" property, but it does not.',
                'access_token',
            ));
        }

        $accessToken = $object->access_token;

        if (!\is_string($accessToken)) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration "%s" should be a string, got "%s" instead.',
                'access_token',
                \is_object($accessToken) ? \get_class($accessToken) : \gettype($accessToken),
            ));
        }

        if (!\property_exists($object, 'element_url')) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration should have an "%s" property, but it does not.',
                'element_url',
            ));
        }

        $elementUrl = $object->element_url;

        if (!\is_string($elementUrl)) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration "%s" should be a string, got "%s" instead..',
                'element_url',
                \is_object($elementUrl) ? \get_class($elementUrl) : \gettype($elementUrl),
            ));
        }

        if (!\property_exists($object, 'homeserver_url')) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration should have an "%s" property, but it does not.',
                'homeserver_url',
            ));
        }

        $homeserverUrl = $object->homeserver_url;

        if (!\is_string($homeserverUrl)) {
            throw new \InvalidArgumentException(\sprintf(
                'Configuration "%s" should be a string, got "%s" instead..',
                'homeserver_url',
                \is_object($homeserverUrl) ? \get_class($homeserverUrl) : \gettype($homeserverUrl),
            ));
        }

        // Réglage optionnel ajouté après la mise en service initiale du plugin :
        // contrairement aux trois réglages ci-dessus, on ne peut pas exiger sa
        // présence dans la config existante (elle ne sera écrite qu'une fois
        // qu'un administrateur aura ouvert et enregistré la page de réglages).
        $roomAvatarMxc = \property_exists($object, 'room_avatar_mxc') && \is_string($object->room_avatar_mxc)
            ? \trim($object->room_avatar_mxc)
            : '';

        return new self(
            Matrix\Domain\AccessToken::fromString(\trim($accessToken)),
            Matrix\Domain\Url::fromString(\trim($elementUrl)),
            Matrix\Domain\Url::fromString(\trim($homeserverUrl)),
            Matrix\Domain\Url::fromString($roomAvatarMxc),
        );
    }

    public function accessToken(): Matrix\Domain\AccessToken
    {
        return $this->accessToken;
    }

    public function elementUrl(): Matrix\Domain\Url
    {
        return $this->elementUrl;
    }

    public function homeserverUrl(): Matrix\Domain\Url
    {
        return $this->homeserverUrl;
    }

    public function roomAvatarMxc(): Matrix\Domain\Url
    {
        return $this->roomAvatarMxc;
    }
}
