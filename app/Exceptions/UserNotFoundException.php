<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UserNotFoundException extends Exception
{
    protected $code = 404;

    public function __construct(string $message = 'Utilisateur introuvable', int $code = 404)
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
