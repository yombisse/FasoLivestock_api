<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Réponse succès
     */
    public static function success($data = null, $message = "Success", $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Réponse erreur
     */
    public static function error($message = "Error", $errors = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}