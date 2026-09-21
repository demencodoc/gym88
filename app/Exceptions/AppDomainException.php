<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDomainException extends Exception
{
    public function __construct(
        string $message = 'Đã xảy ra lỗi nghiệp vụ trong hệ thống.',
        protected int $statusCode = 422,
        protected string $errorCode = 'DOMAIN_ERROR',
        protected array $details = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Render the exception into an HTTP JSON response.
     */
    public function render(Request $request): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ];

        if (! empty($this->details)) {
            $payload['errors'] = $this->details;
        }

        return response()->json($payload, $this->statusCode);
    }
}
