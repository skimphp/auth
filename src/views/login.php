<?php
$errors = $errors ?? [];
$old = $old ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <style>
        body{margin:0;background:#f5f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif}
        main{min-height:100vh;display:grid;place-items:center;padding:24px}
        form,.panel{width:100%;max-width:420px;background:#fff;border:1px solid #d9dee8;padding:28px}
        h1{font-size:24px;margin:0 0 20px}
        label{display:block;font-size:14px;font-weight:700;margin:16px 0 6px}
        input{box-sizing:border-box;width:100%;border:1px solid #b8c0cc;padding:11px 12px;font-size:16px}
        button,.button{display:inline-block;border:0;background:#111827;color:#fff;padding:11px 14px;font-weight:700;text-decoration:none;cursor:pointer}
        .row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px}
        .check{display:flex;align-items:center;gap:8px;font-size:14px}
        .check input{width:auto}
        .error{color:#b42318;font-size:14px;margin-top:8px}
        .muted{color:#4b5563;font-size:14px}
        .links{margin-top:18px;display:flex;gap:12px;flex-wrap:wrap}
    </style>
</head>
<body>
<main>
    <div class="panel">
        <form method="post" action="/login">
            <h1>Sign in</h1>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>" required>
            <?php if (isset($errors['email'])): ?><div class="error"><?= e((string) $errors['email']) ?></div><?php endif; ?>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <div class="row">
                <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
                <button type="submit">Sign in</button>
            </div>
        </form>

        <?php include __DIR__ . '/social/buttons.php'; ?>

        <div class="links">
            <a class="muted" href="/forgot-password">Forgot password?</a>
            <a class="muted" href="/register">Create account</a>
        </div>
    </div>
</main>
</body>
</html>
