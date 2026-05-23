<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTokenException extends Exception
{
    protected $code = 422;

    public function __construct(string $message = 'Token invalide ou expiré', int $code = 422)
    {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception as a JSON response
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
        ], $this->code);
    }
}
