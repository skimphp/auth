<p>Hello <?= e((string) ($user->name ?? '')) ?>,</p>
<p>Reset your password by opening this link:</p>
<p><a href="<?= e((string) $url) ?>"><?= e((string) $url) ?></a></p>
