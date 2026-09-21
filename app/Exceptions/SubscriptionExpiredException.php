<?php

namespace App\Exceptions;

class SubscriptionExpiredException extends AppDomainException
{
    public function __construct(string $message = 'Gói tập của bạn đã hết hạn hoặc chưa được kích hoạt.')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'SUBSCRIPTION_EXPIRED'
        );
    }
}
