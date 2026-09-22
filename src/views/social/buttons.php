<?php
$providers = (array) config('skim_auth.social', []);
$enabled = array_filter($providers, static fn(array $provider): bool => (bool) ($provider['enabled'] ?? false));
?>
<?php if ($enabled !== []): ?>
    <div style="display:grid;gap:10px;margin-top:18px">
        <?php foreach ($enabled as $name => $provider): ?>
            <a class="button" href="/auth/<?= e((string) $name) ?>" style="text-align:center;background:#ffffff;color:#111827;border:1px solid #b8c0cc">
                Continue with <?= e(ucfirst((string) $name)) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
