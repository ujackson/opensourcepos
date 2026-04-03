<?php
/**
 * @var string $plugin_id
 * @var array $settings
 */
?>

<div class="form-group">
    <label class="control-label">
        <input type="checkbox" name="enabled" value="1" <?= ($settings['sumup_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
        <?= lang('Plugins.sumup_enable') ?? 'Enable SumUp Payment Gateway' ?>
    </label>
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.sumup_api_key') ?? 'API Key' ?></label>
    <input type="text" class="form-control" name="api_key" value="<?= esc($settings['sumup_api_key'] ?? '') ?>">
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.sumup_merchant_id') ?? 'Merchant ID' ?></label>
    <input type="text" class="form-control" name="merchant_id" value="<?= esc($settings['sumup_merchant_id'] ?? '') ?>">
</div>

<div class="form-group">
    <label class="control-label"><?= lang('Plugins.sumup_terminal_id') ?? 'Terminal ID' ?></label>
    <input type="text" class="form-control" name="terminal_id" value="<?= esc($settings['sumup_terminal_id'] ?? '') ?>">
</div>