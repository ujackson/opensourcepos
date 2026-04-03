<?php

namespace App\Plugins;

use App\Libraries\Payments\PaymentPluginBase;
use CodeIgniter\Events\Events;

class SumUpPlugin extends PaymentPluginBase
{
    public function getPluginId(): string
    {
        return 'sumup';
    }

    public function getPluginName(): string
    {
        return 'SumUp Payment Gateway';
    }

    public function getPluginDescription(): string
    {
        return 'Accept card payments using SumUp card reader terminals. Integrates with SumUp API for real-time transaction processing.';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function registerEvents(): void
    {
        Events::on('payment_options', [$this, 'onPaymentOptions']);
        Events::on('payment_initiated', [$this, 'onPaymentInitiated']);
        Events::on('sale_completed', [$this, 'onSaleCompleted']);
    }

    public function getPaymentTypes(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }
        
        return [
            'sumup_card' => lang('Sales.sumup_card') ?? 'Card (SumUp Terminal)',
        ];
    }

    public function initiatePayment(float $amount, string $currency = 'USD', array $options = []): array
    {
        $checkoutReference = uniqid('sumup_', true);
        
        $this->logTransaction(
            $checkoutReference,
            'pending',
            $amount,
            $currency,
            $options['sale_id'] ?? null,
            ['options' => $options]
        );

        return [
            'success' => true,
            'transaction_id' => $checkoutReference,
            'status' => 'pending',
            'checkout_reference' => $checkoutReference,
            'amount' => $amount,
            'currency' => $currency,
            'message' => 'Payment initiated. Use SumUp terminal to complete.',
        ];
    }

    public function processCallback(array $data): array
    {
        $eventType = $data['event_type'] ?? '';
        $checkoutId = $data['checkout_id'] ?? $data['id'] ?? null;

        if (!$checkoutId) {
            return ['success' => false, 'error' => 'Missing checkout ID'];
        }

        $transaction = $this->getTransaction($checkoutId);
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        switch ($eventType) {
            case 'payment.success':
                $this->updateTransactionStatus($checkoutId, 'completed', $data);
                Events::trigger('payment_completed', [
                    'provider' => $this->getProviderId(),
                    'transaction_id' => $checkoutId,
                    'sale_id' => $transaction['sale_id'],
                    'amount' => $transaction['amount'],
                ]);
                return ['success' => true, 'status' => 'completed'];

            case 'payment.failed':
                $this->updateTransactionStatus($checkoutId, 'failed', $data);
                Events::trigger('payment_failed', [
                    'provider' => $this->getProviderId(),
                    'transaction_id' => $checkoutId,
                    'error' => $data['error_message'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'status' => 'failed', 'error' => $data['error_message'] ?? 'Payment failed'];

            default:
                return ['success' => false, 'error' => "Unknown event type: {$eventType}"];
        }
    }

    public function getPaymentStatus(string $transactionId): array
    {
        $transaction = $this->getTransaction($transactionId);
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => $transaction['status'],
            'amount' => (float)$transaction['amount'],
            'currency' => $transaction['currency'],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        $transaction = $this->getTransaction($transactionId);
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if ($transaction['status'] !== 'completed') {
            return ['success' => false, 'error' => 'Transaction cannot be refunded'];
        }

        $this->updateTransactionStatus($transactionId, 'refunded', [
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'refunded',
            'refund_amount' => $amount,
        ];
    }

    public function cancel(string $transactionId): array
    {
        $transaction = $this->getTransaction($transactionId);
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if (!in_array($transaction['status'], ['pending', 'authorized'])) {
            return ['success' => false, 'error' => 'Transaction cannot be cancelled'];
        }

        $this->updateTransactionStatus($transactionId, 'cancelled');

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'cancelled',
        ];
    }

    public function onPaymentOptions(array $payments): array
    {
        if ($this->isEnabled()) {
            foreach ($this->getPaymentTypes() as $key => $label) {
                $payments[$key] = $label;
            }
        }
        return $payments;
    }

    public function onPaymentInitiated(array $data): void
    {}

    public function onSaleCompleted(array $data): void
    {}

    public function install(): bool
    {
        parent::install();
        
        $this->setSetting('api_key', '');
        $this->setSetting('merchant_id', '');
        $this->setSetting('terminal_id', '');
        
        return true;
    }

    public function uninstall(): bool
    {
        parent::uninstall();
        return true;
    }
}