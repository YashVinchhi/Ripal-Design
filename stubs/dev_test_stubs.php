<?php

namespace Monolog;

if (!class_exists('Monolog\\Logger')) {
    class Logger
    {
        public function __construct(string $name)
        {
        }

        public function pushHandler($handler): void
        {
        }

        public function log(string $level, string $message, array $context = []): void
        {
        }
    }
}

if (!class_exists('Monolog\\Level')) {
    class Level
    {
        public const Info = 200;
    }
}

namespace Monolog\Handler;

if (!class_exists('Monolog\\Handler\\StreamHandler')) {
    class StreamHandler
    {
        public function __construct(string $stream, $level = null)
        {
        }
    }
}

namespace PhpCsFixer;

if (!class_exists('PhpCsFixer\\Finder')) {
    class Finder
    {
        public static function create(): self
        {
            return new self();
        }

        public function in($dir): self
        {
            return $this;
        }

        public function exclude(array $dirs): self
        {
            return $this;
        }

        public function name(string $pattern): self
        {
            return $this;
        }
    }
}

if (!class_exists('PhpCsFixer\\Config')) {
    class Config
    {
        public function setRiskyAllowed(bool $allowed): self
        {
            return $this;
        }

        public function setRules(array $rules): self
        {
            return $this;
        }

        public function setFinder($finder): self
        {
            return $this;
        }
    }
}

if (!class_exists('Redis')) {
    class Redis
    {
        public function connect(string $host, int $port = 6379, float $timeout = 0): bool
        {
            return false;
        }

        public function auth(string $password): bool
        {
            return false;
        }

        public function select(int $db): bool
        {
            return true;
        }

        public function ttl(string $key): int
        {
            return -1;
        }

        public function incr(string $key): int
        {
            return 0;
        }

        public function expire(string $key, int $seconds): bool
        {
            return true;
        }

        public function setex(string $key, int $seconds, string $value): bool
        {
            return true;
        }

        public function del(string ...$keys): int
        {
            return 0;
        }
    }
}

namespace PHPUnit\Framework;

if (!class_exists('PHPUnit\\Framework\\TestCase')) {
    abstract class TestCase
    {
        protected function assertSame($expected, $actual, string $message = ''): void
        {
        }

        protected function assertNotSame($expected, $actual, string $message = ''): void
        {
        }

        protected function assertTrue($condition, string $message = ''): void
        {
        }

        protected function assertFalse($condition, string $message = ''): void
        {
        }
    }
}
