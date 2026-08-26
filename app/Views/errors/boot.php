<?php
/**
 * Shown when the application cannot connect to its database. Rendered without
 * the normal layout because nothing else has booted yet.
 * @var string $message
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceClone is not set up yet</title>
  <style>
    body { font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
           background: #f0f2f5; color: #050505; margin: 0; padding: 40px 20px; }
    .box { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px;
           padding: 28px; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
    h1 { font-size: 22px; margin: 0 0 8px; color: #1877f2; }
    code, pre { background: #f0f2f5; border-radius: 6px; }
    pre { padding: 12px; overflow-x: auto; font-size: 13px; }
    code { padding: 2px 6px; font-size: 13px; }
    ol { line-height: 1.7; padding-left: 20px; }
    .err { color: #f02849; font-size: 13px; margin-top: 16px; word-break: break-word; }
  </style>
</head>
<body>
  <div class="box">
    <h1>FaceClone cannot reach its database</h1>
    <p>Finish the setup and this page will disappear.</p>
    <ol>
      <li>Create the database and user:
        <pre>sudo mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS faceclone
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'faceclone'@'localhost' IDENTIFIED BY 'faceclone';
GRANT ALL ON faceclone.* TO 'faceclone'@'localhost'; FLUSH PRIVILEGES;"</pre>
      </li>
      <li>Load the schema and demo data: <pre>php bin/console.php install --seed</pre></li>
      <li>Reload this page.</li>
    </ol>
    <p>Credentials live in <code>config/config.local.php</code> (copy <code>config/config.example.php</code>).</p>
    <p class="err"><strong>Details:</strong> <?= htmlspecialchars($message, ENT_QUOTES) ?></p>
  </div>
</body>
</html>
