<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd (Trading as Element)
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Plugin\Infrastructure;

use mod_matrix\Container;
use mod_matrix\Matrix;
use mod_matrix\Plugin;

final class FrontController
{
    private $container;
    private $page;
    private $renderer;

    public function __construct(
        Container $container,
        \moodle_page $page,
        \core_renderer $renderer
    ) {
        $this->container = $container;
        $this->page = $page;
        $this->renderer = $renderer;
    }

    public function handle(
        Plugin\Domain\Module $module,
        \cm_info $cm,
        \stdClass $user,
        string $action = '',
        int $roomId = 0
    ): void {
        // Invitation paresseuse : déclenchée au clic sur « Entrer dans le salon ».
        // L'action redirige (et termine) sans imprimer l'en-tête de page.
        if ('join' === $action && $roomId > 0) {
            $this->joinRoomAction()->handle(
                $user,
                $module,
                $cm,
                $roomId,
            );

            return;
        }

        echo $this->renderer->header();

        $matrixUserId = $this->container->matrixUserIdLoader()->load($user);

        if (!$matrixUserId instanceof Matrix\Domain\UserId) {
            $this->editMatrixUserIdFormAction()->handle($user);

            return;
        }

        $this->listRoomsAction()->handle(
            $user,
            $module,
            $cm,
        );
    }

    private function joinRoomAction(): Plugin\Infrastructure\Action\JoinRoomAction
    {
        return new Plugin\Infrastructure\Action\JoinRoomAction(
            $this->container->roomRepository(),
            $this->container->matrixUserIdLoader(),
            $this->container->roomService(),
            $this->container->matrixRoomService(),
        );
    }

    private function editMatrixUserIdFormAction(): Plugin\Infrastructure\Action\EditMatrixUserIdAction
    {
        return new Plugin\Infrastructure\Action\EditMatrixUserIdAction(
            $this->page,
            $this->renderer,
            $this->container->configuration(),
        );
    }

    private function listRoomsAction(): Plugin\Infrastructure\Action\ListRoomsAction
    {
        return new Plugin\Infrastructure\Action\ListRoomsAction(
            $this->container->roomRepository(),
            $this->container->moodleGroupRepository(),
            $this->container->matrixUserIdLoader(),
            $this->container->roomService(),
            $this->container->nameService(),
            $this->renderer,
        );
    }
}
