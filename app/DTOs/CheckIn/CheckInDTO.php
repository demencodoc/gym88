<?php

namespace App\DTOs\CheckIn;

use Illuminate\Http\Request;

readonly class CheckInDTO
{
    public function __construct(
        public ?string $code = null, // dynamic QR token or membership code
        public ?int $userId = null,
        public string $method = 'dynamic_qr',
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            code: $request->validated('code'),
            userId: $request->validated('user_id'),
            method: (string) ($request->validated('method') ?? 'dynamic_qr'),
            notes: $request->validated('notes'),
        );
    }
}
