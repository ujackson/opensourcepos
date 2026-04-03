<?php
/**
 * @var string $plugin_id
 * @var array $settings
 */
?>

<div class="form-group">
    <label class="control-label">
        <input type="checkbox" name="enabled" value="1" <?= ($settings['paypal_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
        <?= lang('Plugins.paypal_enable') ?? 'Enable PayPal/Zettle Payment Gateway' ?>
    </label>
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.paypal_client_id') ?? 'Client ID' ?></label>
    <input type="text" class="form-control" name="client_id" value="<?= esc($settings['paypal_client_id'] ?? '') ?>">
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.paypal_client_secret') ?? 'Client Secret' ?></label>
    <input type="password" class="form-control" name="client_secret" value="<?= esc($settings['paypal_client_secret'] ?? '') ?>">
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.paypal_environment') ?? 'Environment' ?></label>
    <select class="form-control" name="environment">
        <option value="sandbox" <?= ($settings['paypal_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
        <option value="live" <?= ($settings['paypal_environment'] ?? '') === 'live' ? 'selected' : '' ?>>Live (Production)</option>
    </select>
</div>

<div class="form-group">
    <label class="control-label">
        <input type="checkbox" name="enable_qr" value="1" <?= ($settings['paypal_enable_qr'] ?? '1') === '1' ? 'checked' : '' ?>>
        <?= lang('Plugins.paypal_enable_qr') ?? 'Enable QR Code Payment' ?>
    </label>
</div>