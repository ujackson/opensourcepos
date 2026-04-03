<?php

namespace App\Plugins;

use App\Libraries\Payments\PaymentPluginBase;
use CodeIgniter\Events\Events;

class PayPalPlugin extends PaymentPluginBase
{
    public function getPluginId(): string
    {
        return 'paypal';
    }

    public function getPluginName(): string
    {
        return 'PayPal/Zettle Payment Gateway';
    }

    public function getPluginDescription(): string
    {
        return 'Accept payments using PayPal (Zettle) card reader terminals and QR code payments. Supports both in-person and remote payment flows.';
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
            'paypal_card' => lang('Sales.paypal_card') ?? 'Card (PayPal/Zettle)',
            'paypal_qr' => lang('Sales.paypal_qr') ?? 'PayPal QR',
        ];
    }

    public function initiatePayment(float $amount, string $currency = 'USD', array $options = []): array
    {
        $orderId = uniqid('paypal_', true);
        $paymentType = $options['payment_type'] ?? 'paypal_card';

        $this->logTransaction(
            $orderId,
            'pending',
            $amount,
            $currency,
            $options['sale_id'] ?? null,
            ['payment_type' => $paymentType, 'options' => $options]
        );

        $message = $paymentType === 'paypal_qr'
            ? 'Payment initiated. Customer can scan QR code to complete payment.'
            : 'Payment initiated. Use PayPal Zettle terminal to complete.';

        return [
            'success' => true,
            'transaction_id' => $orderId,
            'status' => 'pending',
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'payment_type' => $paymentType,
            'message' => $message,
        ];
    }

    public function processCallback(array $data): array
    {
        $eventType = $data['event_type'] ?? '';
        $orderId = $data['resource']['id'] ?? $data['order_id'] ?? null;

        if (!$orderId) {
            return ['success' => false, 'error' => 'Missing order ID'];
        }

        $transaction = $this->getTransaction($orderId);
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->updateTransactionStatus($orderId, 'completed', $data);
                Events::trigger('payment_completed', [
                    'provider' => $this->getProviderId(),
                    'transaction_id' => $orderId,
                    'sale_id' => $transaction['sale_id'],
                    'amount' => $transaction['amount'],
                ]);
                return ['success' => true, 'status' => 'completed'];

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->updateTransactionStatus($orderId, 'failed', $data);
                Events::trigger('payment_failed', [
                    'provider' => $this->getProviderId(),
                    'transaction_id' => $orderId,
                    'error' => $data['resource']['status_details'] ?? 'Payment declined',
                ]);
                return ['success' => false, 'status' => 'failed', 'error' => 'Payment declined'];

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
        
        $this->setSetting('client_id', '');
        $this->setSetting('client_secret', '');
        $this->setSetting('environment', 'sandbox');
        $this->setSetting('enable_qr', '1');
        
        return true;
    }

    public function uninstall(): bool
    {
        parent::uninstall();
        return true;
    }
}