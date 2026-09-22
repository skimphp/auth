<?php
$errors = $errors ?? [];
$old = $old ?? [];
$token = $token ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose password</title>
    <style>
        body{margin:0;background:#f5f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif}
        main{min-height:100vh;display:grid;place-items:center;padding:24px}
        form{width:100%;max-width:420px;background:#fff;border:1px solid #d9dee8;padding:28px}
        h1{font-size:24px;margin:0 0 20px}
        label{display:block;font-size:14px;font-weight:700;margin:16px 0 6px}
        input{box-sizing:border-box;width:100%;border:1px solid #b8c0cc;padding:11px 12px;font-size:16px}
        button{border:0;background:#111827;color:#fff;padding:11px 14px;font-weight:700;cursor:pointer}
        .error{color:#b42318;font-size:14px;margin-top:8px}
    </style>
</head>
<body>
<main>
    <form method="post" action="/reset-password">
        <h1>Choose password</h1>
        <input type="hidden" name="token" value="<?= e((string) $token) ?>">
        <?php if (isset($errors['token'])): ?><div class="error"><?= e((string) $errors['token']) ?></div><?php endif; ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>" required>
        <?php if (isset($errors['email'])): ?><div class="error"><?= e((string) $errors['email']) ?></div><?php endif; ?>

        <label for="password">New password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?><div class="error"><?= e((string) $errors['password']) ?></div><?php endif; ?>

        <label for="password_confirm">Confirm password</label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password_confirm'])): ?><div class="error"><?= e((string) $errors['password_confirm']) ?></div><?php endif; ?>

        <p><button type="submit">Update password</button></p>
    </form>
</main>
</body>
</html>
