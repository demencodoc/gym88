<?php

namespace App\Exceptions;

class InsufficientPtSessionsException extends AppDomainException
{
    public function __construct(string $message = 'Số buổi tập PT trong gói hợp đồng của bạn đã hết.')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'INSUFFICIENT_PT_SESSIONS'
        );
    }
}
