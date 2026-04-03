<?php

namespace App\Libraries\Payments;

use App\Libraries\Plugins\BasePlugin;
use App\Models\PaymentTransaction;

abstract class PaymentPluginBase extends BasePlugin implements PaymentProviderInterface
{
    protected PaymentTransaction $transactionModel;

    public function __construct()
    {
        parent::__construct();
        $this->transactionModel = model(PaymentTransaction::class);
    }

    public function getProviderId(): string
    {
        return $this->getPluginId();
    }

    public function getProviderName(): string
    {
        return $this->getPluginName();
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled();
    }

    public function getPaymentTypes(): array
    {
        return [];
    }

    public function getConfigView(): ?string
    {
        return "Plugins/{$this->getPluginId()}/Views/config";
    }

    protected function logTransaction(
        string $transactionId,
        string $status,
        float $amount,
        string $currency = 'USD',
        ?int $saleId = null,
        array $metadata = []
    ): bool {
        $data = [
            'provider_id' => $this->getProviderId(),
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
            'sale_id' => $saleId,
            'metadata' => json_encode($metadata),
        ];
        
        return $this->transactionModel->insert($data) !== false;
    }

    protected function updateTransactionStatus(string $transactionId, string $status, array $metadata = []): bool
    {
        $transaction = $this->transactionModel
            ->where('transaction_id', $transactionId)
            ->where('provider_id', $this->getProviderId())
            ->first();
            
        if (!$transaction) {
            return false;
        }
        
        $updateData = ['status' => $status];
        if (!empty($metadata)) {
            $existingMetadata = json_decode($transaction['metadata'] ?? '{}', true);
            $updateData['metadata'] = json_encode(array_merge($existingMetadata, $metadata));
        }
        
        return $this->transactionModel->update($transaction['id'], $updateData);
    }

    protected function getTransaction(string $transactionId): ?array
    {
        return $this->transactionModel
            ->where('transaction_id', $transactionId)
            ->where('provider_id', $this->getProviderId())
            ->first();
    }

    public function install(): bool
    {
        $this->setSetting('enabled', '0');
        
        foreach ($this->getPaymentTypes() as $key => $label) {
            $this->setSetting("payment_type_{$key}", $label);
        }
        
        return true;
    }

    public function uninstall(): bool
    {
        $this->transactionModel->where('provider_id', $this->getProviderId())->delete();
        
        return true;
    }
}