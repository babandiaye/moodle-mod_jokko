<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Matrix\Domain;

/**
 * Erreur structurée renvoyée par l'API Matrix (corps JSON contenant
 * "errcode"/"error"), par opposition à une erreur de connectivité pure
 * (timeout, DNS, réponse sans corps JSON exploitable), qui reste levée
 * comme un \RuntimeException générique.
 *
 * @see https://spec.matrix.org/latest/client-server-api/#standard-error-response
 */
final class ApiError extends \RuntimeException
{
    private $errorCode;

    private function __construct(
        string $message,
        string $errorCode
    ) {
        parent::__construct($message);

        $this->errorCode = $errorCode;
    }

    public static function fromResponse(
        int $httpStatusCode,
        string $httpErrorMessage,
        string $errorCode,
        string $errorMessage
    ): self {
        return new self(
            <<<TXT
Sending a request failed with HTTP status code {$httpStatusCode} and error message {$httpErrorMessage}.

The response contains a specific error code and message.

Error code
---------

{$errorCode}

Error message
---------

{$errorMessage}

TXT,
            $errorCode,
        );
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
