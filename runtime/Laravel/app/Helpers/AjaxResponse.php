<?php

namespace App\Helpers;

/**
 * Class AjaxResponse
 *
 * Helper class to standardize JSON responses for AJAX requests.
 * Provides success and error responses with consistent structure.
 */
class AjaxResponse
{
    /**
     * Return a standardized JSON success response.
     *
     * @param string $message Optional success message.
     * @param mixed $data Optional data to include in the response.
     * @param int $code HTTP status code (default: 200).
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success(string $message = '', mixed $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'error' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized JSON error response.
     *
     * @param string $message Optional error message.
     * @param mixed $errors Optional additional error details (array or string).
     * @param int $code HTTP status code (default: 400).
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = '', mixed $errors = [], int $code = 400)
    {
        return response()->json([
            'success' => false,
            'error' => true,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
