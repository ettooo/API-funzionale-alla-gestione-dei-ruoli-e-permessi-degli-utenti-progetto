<?php
require_once __DIR__ . '/auth.php';
require_login();

// dashboard base consentita
require_permission('DASHBOARD_BASIC');
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Trademarket AI — Dashboard FREE</title>

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
    .badge { background-color:#7c3aed; color:#fff; padding:2px 6px; border-radius:4px; font-size:.75rem; }
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
      <div class="flex items-center gap-3">
        <span class="text-lg font-extrabold tracking-tight">Trademarket AI</span>
        <span class="badge">FREE</span>
      </div>
      <a href="logout.php"
         class="rounded-xl border border-slate-700/70 bg-slate-900/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800/40 transition">
        Logout
      </a>
    </div>
  </header>

  <!-- Main -->
  <main class="min-h-[calc(100vh-80px)] flex items-center justify-center px-4">
    <section class="w-full max-w-3xl">
      <div class="glass rounded-2xl border border-slate-700/70 p-8 shadow-xl">
        <h1 class="text-3xl font-extrabold tracking-tight">
          Dashboard FREE
        </h1>
        <p class="mt-2 text-slate-300">
          Ciao <span class="font-semibold text-slate-100"><?= htmlspecialchars(current_user()['username']) ?></span>,
          queste sono le funzionalità disponibili per il tuo piano.
        </p>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
          <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
            <h3 class="text-lg font-extrabold tracking-tight">Mercati base</h3>
            <p class="mt-2 text-sm text-slate-300">
              Accesso ai dati di mercato essenziali.
            </p>
            <p class="mt-4 text-sm">
              Stato:
              <span class="font-semibold <?= can('MARKET_VIEW_BASIC') ? 'text-primary' : 'text-red-400' ?>">
                <?= can('MARKET_VIEW_BASIC') ? 'OK' : 'NO' ?>
              </span>
            </p>
          </div>

          <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
            <h3 class="text-lg font-extrabold tracking-tight">Analisi predittiva AI</h3>
            <p class="mt-2 text-sm text-slate-300">
              Previsioni basate su intelligenza artificiale.
            </p>
            <p class="mt-4 text-sm">
              Stato:
              <span class="font-semibold <?= can('AI_PREDICT') ? 'text-primary' : 'text-red-400' ?>">
                <?= can('AI_PREDICT') ? 'OK' : 'NO' ?>
              </span>
            </p>
          </div>
        </div>

        <div class="mt-8 rounded-xl border border-slate-700/70 bg-slate-900/20 p-5">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-300">
              Passa a <span class="font-semibold text-accent">PREMIUM</span> per sbloccare analisi AI avanzate,
              storico esteso e strategie probabilistiche.
            </p>
            <a href="upgrade_premium.php"
               class="float inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-extrabold text-slate-950 shadow-xl hover:brightness-110 transition">
              Passa a PREMIUM
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
