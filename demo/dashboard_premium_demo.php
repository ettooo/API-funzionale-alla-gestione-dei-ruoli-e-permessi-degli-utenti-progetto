<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Trademarket AI — Dashboard PREMIUM (DEMO)</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui"] },
          colors: { primary:"#0ea5a4", accent:"#7c3aed" }
        }
      }
    }
  </script>

  <style>
    .glass { background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); }
    .float { transform: translateY(0); transition: transform .25s ease; }
    .float:hover { transform: translateY(-6px); }
    .badge { background-color:#7c3aed; color:#fff; padding:2px 6px; border-radius:4px; font-size:.75rem; }
  </style>
</head>

<body class="bg-slate-900 text-slate-100 antialiased font-sans">
  <div aria-hidden="true" class="fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-[520px] w-[520px] rounded-full blur-3xl opacity-30"
         style="background: radial-gradient(circle at 30% 30%, rgba(14,165,164,.9), transparent 55%);"></div>
    <div class="absolute -bottom-52 -right-52 h-[640px] w-[640px] rounded-full blur-3xl opacity-30"
         style="background: radial-gradient(circle at 70% 70%, rgba(124,58,237,.9), transparent 55%);"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950"></div>
  </div>

  <header class="border-b border-slate-800/70 bg-slate-900/60 backdrop-blur">
    <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-lg font-extrabold tracking-tight">Trademarket AI</span>
        <span class="badge">PREMIUM DEMO</span>
      </div>
      <a href="dashboard_free.php"
         class="rounded-xl border border-slate-700/70 bg-slate-900/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800/40 transition">
        Torna alla FREE
      </a>
    </div>
  </header>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <section class="glass rounded-2xl border border-slate-700/70 p-8 shadow-xl">
      <h1 class="text-3xl font-extrabold tracking-tight">Dashboard PREMIUM (DEMO)</h1>
      <p class="mt-2 text-slate-300">
        Benvenuto, <span class="font-semibold text-slate-100"><?= htmlspecialchars(current_user()['username']) ?></span>
      </p>

      <div class="mt-6 rounded-xl border border-slate-700/70 bg-slate-900/20 p-5">
        <p class="text-sm text-slate-300">
          🔒 Questa è una dashboard DEMO: non usa permessi reali e non sblocca davvero PREMIUM.
        </p>
      </div>

      <div class="mt-8 grid gap-6 md:grid-cols-3">
        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <h3 class="text-lg font-extrabold tracking-tight">Mercati avanzati</h3>
          <p class="mt-2 text-sm text-slate-300">Indicatori pro e storico esteso (demo).</p>
        </div>
        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <h3 class="text-lg font-extrabold tracking-tight">AI Predict</h3>
          <p class="mt-2 text-sm text-slate-300">Previsioni e scenari (demo).</p>
        </div>
        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <h3 class="text-lg font-extrabold tracking-tight">AI Strategy</h3>
          <p class="mt-2 text-sm text-slate-300">Simulazioni e strategie (demo).</p>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
