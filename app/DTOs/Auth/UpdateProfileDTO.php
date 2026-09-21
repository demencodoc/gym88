<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

readonly class UpdateProfileDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $gender = null,
        public ?string $dateOfBirth = null,
        public ?string $address = null,
        public ?string $emergencyContact = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            gender: $request->validated('gender'),
            dateOfBirth: $request->validated('date_of_birth'),
            address: $request->validated('address'),
            emergencyContact: $request->validated('emergency_contact'),
        );
    }
}
