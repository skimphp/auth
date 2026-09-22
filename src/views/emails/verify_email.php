<p>Hello <?= e((string) ($user->name ?? '')) ?>,</p>
<p>Verify your email address by opening this link:</p>
<p><a href="<?= e((string) $url) ?>"><?= e((string) $url) ?></a></p>
