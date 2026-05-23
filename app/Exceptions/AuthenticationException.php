<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AuthenticationException extends Exception
{
    protected $code = 401;

    public function __construct(string $message = 'Authentification échouée', int $code = 401)
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
