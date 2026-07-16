<?php

declare(strict_types=1);

namespace App\Support;

final readonly class Money
{
    public function __construct(public string $amount, public string $currency = 'EGP') {}

    public function add(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, 2), $this->currency);
    }

    public function sub(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, 2), $this->currency);
    }

    public function mul(string $multiplier): self
    {
        return new self(bcmul($this->amount, $multiplier, 2), $this->currency);
    }

    public function percent(string $rate): self
    {
        $value = bcmul($this->amount, bcdiv($rate, '100', 4), 2);

        return new self($value, $this->currency);
    }

    public function toDecimal(): string
    {
        return $this->amount;
    }

    public function compare(self $other): int
    {
        return bccomp($this->amount, $other->amount, 2);
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', 2) > 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', 2) < 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', 2) === 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return $this->amount.' '.$this->currency;
    }

    public static function zero(string $currency = 'EGP'): self
    {
        return new self('0.00', $currency);
    }
}
