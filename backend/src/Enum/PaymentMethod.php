<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';

    /**
     * Get all payment method values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $method) => $method->value, self::cases());
    }

    /**
     * Check if payment method requires immediate processing
     */
    public function isImmediate(): bool
    {
        return match($this) {
            self::CREDIT_CARD, self::DEBIT_CARD, self::STRIPE, self::PAYPAL => true,
            self::BANK_TRANSFER => false,
        };
    }

    /**
     * Check if payment method supports refunds
     */
    public function supportsRefunds(): bool
    {
        return match($this) {
            self::CREDIT_CARD, self::DEBIT_CARD, self::STRIPE, self::PAYPAL => true,
            self::BANK_TRANSFER => false,
        };
    }

    /**
     * Get display label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
        };
    }
}
