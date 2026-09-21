<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

readonly class LoginDTO
{
    public function __construct(
        public string $login, // email or phone
        public string $password,
        public string $deviceName = 'mobile_app',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            login: (string) $request->validated('login'),
            password: (string) $request->validated('password'),
            deviceName: (string) ($request->validated('device_name') ?? 'mobile_app'),
        );
    }
}
