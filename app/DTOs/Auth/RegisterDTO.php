<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
        public string $role = 'member',
        public ?string $gender = null,
        public ?string $dateOfBirth = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            phone: $request->validated('phone'),
            password: (string) $request->validated('password'),
            role: (string) ($request->validated('role') ?? 'member'),
            gender: $request->validated('gender'),
            dateOfBirth: $request->validated('date_of_birth'),
        );
    }
}
