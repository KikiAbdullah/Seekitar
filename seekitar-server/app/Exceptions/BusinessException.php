<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessException extends HttpException
{
    public function __construct(string $message = '', int $statusCode = 422, ?\Throwable $previous = null)
    {
        parent::__construct($statusCode, $message, $previous);
    }
}
