<?php
$old = $old ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password</title>
    <style>
        body{margin:0;background:#f5f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif}
        main{min-height:100vh;display:grid;place-items:center;padding:24px}
        form{width:100%;max-width:420px;background:#fff;border:1px solid #d9dee8;padding:28px}
        h1{font-size:24px;margin:0 0 20px}
        label{display:block;font-size:14px;font-weight:700;margin:16px 0 6px}
        input{box-sizing:border-box;width:100%;border:1px solid #b8c0cc;padding:11px 12px;font-size:16px}
        button{border:0;background:#111827;color:#fff;padding:11px 14px;font-weight:700;cursor:pointer}
        .status{background:#ecfdf3;border:1px solid #abefc6;color:#067647;padding:10px 12px;margin-bottom:16px}
        .muted{color:#4b5563;font-size:14px}
    </style>
</head>
<body>
<main>
    <form method="post" action="/forgot-password">
        <h1>Reset password</h1>
        <?php if (!empty($status)): ?><div class="status"><?= e((string) $status) ?></div><?php endif; ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" value="<?= e((string) ($old['email'] ?? '')) ?>" required>
        <p><button type="submit">Send reset link</button></p>
        <a class="muted" href="/login">Back to sign in</a>
    </form>
</main>
</body>
</html>
