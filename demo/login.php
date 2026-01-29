<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? ''); // email o username
    $pass  = $_POST['password'] ?? '';

    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, password_hash, is_active FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $u = $stmt->fetch();

    if (!$u || (int)$u['is_active'] !== 1) {
        $err = "Credenziali non valide.";
    } elseif (!password_verify($pass, $u['password_hash'])) {
        $err = "Credenziali non valide.";
    } else {
        login_user((int)$u['id']);
        redirect_dashboard();
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Trademarket AI — Login</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui"] },
          colors: {
            primary: "#0ea5a4",
            accent: "#7c3aed"
          }
        }
      }
    }
  </script>

  <style>
    .glass { background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); }
    .float { transform: translateY(0); transition: transform .25s ease; }
    .float:hover { transform: translateY(-6px); }
  </style>
</head>

<body class="bg-slate-900 text-slate-100 antialiased font-sans">
  <!-- Background -->
  <div aria-hidden="true" class="fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-[520px] w-[520px] rounded-full blur-3xl opacity-30"
         style="background: radial-gradient(circle at 30% 30%, rgba(14,165,164,.9), transparent 55%);"></div>
    <div class="absolute -bottom-52 -right-52 h-[640px] w-[640px] rounded-full blur-3xl opacity-30"
         style="background: radial-gradient(circle at 70% 70%, rgba(124,58,237,.9), transparent 55%);"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950"></div>
  </div>

  <!-- Header -->
  <header class="border-b border-slate-800/70 bg-slate-900/60 backdrop-blur">
    <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
      <span class="text-lg font-extrabold tracking-tight">Trademarket AI</span>
      <a href="register.php"
         class="rounded-xl bg-primary px-4 py-2 text-sm font-extrabold text-slate-950 shadow-xl hover:brightness-110 transition">
        Inizia gratis
      </a>
    </div>
  </header>

  <!-- Main -->
  <main class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
    <section class="w-full max-w-md">
      <div class="glass rounded-2xl border border-slate-700/70 p-8 shadow-xl">
        <h1 class="text-3xl font-extrabold tracking-tight text-center">Login</h1>
        <p class="mt-2 text-center text-sm text-slate-300">
          Accedi per entrare nella dashboard in base al tuo ruolo.
        </p>

        <?php if ($err): ?>
          <div class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-200">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <form method="post" class="mt-6 space-y-4">
          <div>
            <label class="text-sm font-semibold text-slate-200">Email o Username</label>
            <input name="login" required
                   class="mt-2 w-full rounded-xl border border-slate-700/70 bg-slate-900/30 px-4 py-3 text-slate-100 outline-none focus:border-primary/70 focus:ring-2 focus:ring-primary/20" />
          </div>

          <div>
            <label class="text-sm font-semibold text-slate-200">Password</label>
            <input name="password" type="password" required
                   class="mt-2 w-full rounded-xl border border-slate-700/70 bg-slate-900/30 px-4 py-3 text-slate-100 outline-none focus:border-primary/70 focus:ring-2 focus:ring-primary/20" />
          </div>

          <button type="submit"
                  class="float w-full rounded-xl bg-primary px-5 py-3 text-sm font-extrabold text-slate-950 shadow-xl hover:brightness-110 transition">
            Accedi
          </button>

          <p class="text-center text-sm text-slate-300">
            Non hai un account?
            <a href="register.php" class="font-semibold text-primary hover:brightness-110 transition">
              Registrati
            </a>
          </p>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
