<?php

namespace App\Controllers\Payments;

use App\Controllers\BaseController;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\ResponseInterface;

class Webhook extends BaseController
{
    public function handle(string $pluginId): ResponseInterface
    {
        $pluginManager = new \App\Libraries\Plugins\PluginManager();
        $plugin = $pluginManager->getPlugin($pluginId);
        
        if ($plugin === null || !($plugin instanceof \App\Libraries\Payments\PaymentProviderInterface)) {
            log_message('error', "Webhook received for unknown or invalid provider: {$pluginId}");
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error' => 'Provider not found or not a payment provider'
            ]);
        }
        
        $rawInput = $this->request->getBody();
        $data = json_decode($rawInput, true) ?? [];
        
        if (empty($rawInput)) {
            $data = $this->request->getPost();
        }
        
        try {
            $result = $plugin->processCallback($data);
            
            if ($result['success'] ?? false) {
                log_message('info', "Webhook processed successfully for provider: {$pluginId}", $result);
                return $this->response->setStatusCode(200)->setJSON($result);
            }
            
            log_message('warning', "Webhook processing failed for provider: {$pluginId}", $result);
            return $this->response->setStatusCode(400)->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', "Webhook exception for provider {$pluginId}: " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Internal server error'
            ]);
        }
    }

    public function status(string $pluginId, string $transactionId): ResponseInterface
    {
        $pluginManager = new \App\Libraries\Plugins\PluginManager();
        $plugin = $pluginManager->getPlugin($pluginId);
        
        if ($plugin === null || !($plugin instanceof \App\Libraries\Payments\PaymentProviderInterface)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error' => 'Provider not found or not a payment provider'
            ]);
        }
        
        try {
            $result = $plugin->getPaymentStatus($transactionId);
            return $this->response->setStatusCode(200)->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', "Status check exception for provider {$pluginId}: " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Internal server error'
            ]);
        }
    }
}