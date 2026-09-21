<?php

namespace App\Exceptions;

class ResourceNotFoundException extends AppDomainException
{
    public function __construct(string $message = 'Tài nguyên yêu cầu không tồn tại trong hệ thống.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'RESOURCE_NOT_FOUND'
        );
    }
}
