<?php

namespace App\Core\Http;

class ApiValidator
{
    private array $errors = [];
    private array $data = [];
    private array $validated = [];

    public function __construct(array $input)
    {
        $this->data = $input;
    }

    public function required(string $field): self
    {
        if (!array_key_exists($field, $this->data) || $this->isEmpty($this->data[$field])) {
            $this->errors[$field] = 'required';
            return $this;
        }

        $this->validated[$field] = $this->data[$field];
        return $this;
    }

    public function string(string $field, int $maxLen = 255): self
    {
        if (!array_key_exists($field, $this->data) || is_array($this->data[$field])) {
            $this->errors[$field] = 'string';
            return $this;
        }

        $value = trim((string)$this->data[$field]);
        if ($value === '') {
            $this->errors[$field] = 'string';
            return $this;
        }

        if (strlen($value) > $maxLen) {
            $value = substr($value, 0, $maxLen);
        }

        $this->validated[$field] = $value;
        return $this;
    }

    public function integer(string $field, int $min = 0, int $max = PHP_INT_MAX): self
    {
        if (!array_key_exists($field, $this->data) || is_array($this->data[$field])) {
            $this->errors[$field] = 'integer';
            return $this;
        }

        $value = filter_var($this->data[$field], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $this->errors[$field] = 'integer';
            return $this;
        }

        $this->validated[$field] = (int)$value;
        return $this;
    }

    public function email(string $field): self
    {
        if (!array_key_exists($field, $this->data) || is_array($this->data[$field])) {
            $this->errors[$field] = 'email';
            return $this;
        }

        $value = trim((string)$this->data[$field]);
        if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'email';
            return $this;
        }

        $this->validated[$field] = $value;
        return $this;
    }

    public function inList(string $field, array $allowed): self
    {
        if (!array_key_exists($field, $this->data) || is_array($this->data[$field])) {
            $this->errors[$field] = 'in_list';
            return $this;
        }

        $value = trim((string)$this->data[$field]);
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = 'in_list';
            return $this;
        }

        $this->validated[$field] = $value;
        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function all(): array
    {
        return $this->data;
    }

    public static function fromPost(): self
    {
        return new self($_POST ?? []);
    }

    public static function fromJson(): self
    {
        $raw = (string)file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return new self(is_array($decoded) ? $decoded : []);
    }

    private function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return empty($value);
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }
}