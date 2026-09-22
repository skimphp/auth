<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify email</title>
    <style>
        body{margin:0;background:#f5f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif}
        main{min-height:100vh;display:grid;place-items:center;padding:24px}
        section{width:100%;max-width:480px;background:#fff;border:1px solid #d9dee8;padding:28px}
        h1{font-size:24px;margin:0 0 12px}
        button{border:0;background:#111827;color:#fff;padding:11px 14px;font-weight:700;cursor:pointer}
        .status{background:#ecfdf3;border:1px solid #abefc6;color:#067647;padding:10px 12px;margin-bottom:16px}
    </style>
</head>
<body>
<main>
    <section>
        <h1>Verify email</h1>
        <?php if (!empty($status)): ?><div class="status"><?= e((string) $status) ?></div><?php endif; ?>
        <p>Check your inbox for a verification link.</p>
        <form method="post" action="/email/resend">
            <button type="submit">Send again</button>
        </form>
    </section>
</main>
</body>
</html>
