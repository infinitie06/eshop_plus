<?php

namespace App\Traits;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait HandlesValidation
{
    public function HandlesValidation(Request $request, array $rules, array $messages = [], ?Closure $after = null, bool $fromApp = false)
    {
        // Sanitize inputs
        $sanitized = $this->sanitizeInput($request->all());
        $request->merge($sanitized);

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($after) {
            $validator->after($after);
        }

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            // If custom message array exists for that rule, prefer that
            if (!empty($messages)) {
                foreach ($messages as $key => $msg) {
                    $rule = explode('.', $key)[0] ?? null;
                    if ($rule && $validator->errors()->has($rule)) {
                        $firstError = $msg;
                        break;
                    }
                }
            }

            // Extract clean error message (handling potential JSON strings)
            $decoded = json_decode($firstError, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $firstField = array_key_first($decoded);
                if (isset($decoded[$firstField]) && is_array($decoded[$firstField]) && isset($decoded[$firstField][0])) {
                    $firstError = $decoded[$firstField][0];
                } elseif (isset($decoded[$firstField]) && is_string($decoded[$firstField])) {
                    $firstError = $decoded[$firstField];
                }
            }

            $errorResponse = [
                'error'   => true,
                'message' => $firstError,
                'errors'  => $validator->errors(),
                'code'    => 102,
            ];

            if ($fromApp || $request->ajax()) {
                return response()->json($errorResponse);
            }

            throw new \Illuminate\Validation\ValidationException(
                Validator::make([], []),
                response()->json($errorResponse, 422)
            );
        }

        return null;
    }

    /**
     * Strip tags and dangerous patterns from all request inputs
     */
    protected function sanitizeInput(array $inputs): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeInput($value);
            }

            if (is_string($value)) {
                $cleanValue = trim($value);

                // Disallowed HTML/JS tags
                if (preg_match('/<\s*(script|meta|object|embed|style|base|form|input|button|textarea|svg|math)\b/i', $cleanValue)) {
                    throw new \Illuminate\Validation\ValidationException(
                        Validator::make([], []),
                        response()->json([
                            'error'   => true,
                            'message' => 'Input contains disallowed HTML/JS.',
                            'code'    => 103,
                        ], 422)
                    );
                }

                // Disallow inline JS or event handlers
                if (preg_match('/javascript:/i', $cleanValue) || preg_match('/on\w+=/i', $cleanValue)) {
                    throw new \Illuminate\Validation\ValidationException(
                        Validator::make([], []),
                        response()->json([
                            'error'   => true,
                            'message' => 'Input contains potentially unsafe content.',
                            'code'    => 104,
                        ], 422)
                    );
                }

                // Allow URLs
                if (filter_var($cleanValue, FILTER_VALIDATE_URL)) {
                    return $cleanValue;
                }

                return strip_tags($cleanValue);
            }

            return $value;
        }, $inputs);
    }
}
