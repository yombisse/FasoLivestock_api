<?php

namespace App\DTOs;

readonly class ApiResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public mixed $data,
        public int $status
    ) {}

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): self
    {
        return new self(true, $message, $data, $status);
    }

    public static function error(string $message, int $status = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $status);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'status' => $this->status,
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
