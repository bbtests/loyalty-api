<?php

namespace App\Contracts;

use App\Models\User;

interface PaymentProviderInterface
{
    /**
     * Initialize a payment transaction
     *
     * @return array<string, mixed>
     */
    public function initializePayment(User $user, float $amount, string $reference): array;

    /**
     * Verify a payment transaction
     *
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference): array;

    /**
     * Process cashback payment
     *
     * @return array<string, mixed>
     */
    public function processCashback(User $user, float $amount): array;

    /**
     * Get provider configuration
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array;

    /**
     * Check if provider is available/enabled
     */
    public function isAvailable(): bool;

    /**
     * Get provider name
     */
    public function getName(): string;

    /**
     * Get supported currencies
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get minimum payment amount
     */
    public function getMinimumAmount(): float;

    /**
     * Get maximum payment amount
     */
    public function getMaximumAmount(): float;
}
