<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A business-rule failure in the participant API. Renders as
 * {"message": "...", "code": "<machine code>"} with the given HTTP status (422 by default).
 * It is an HttpException, so the /offline-actions loop reports it as a failed operation.
 */
class ParticipantRuleException extends HttpException
{
    public function __construct(string $message, public readonly string $errorCode, int $status = 422)
    {
        parent::__construct($status, $message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ], $this->getStatusCode());
    }
}
