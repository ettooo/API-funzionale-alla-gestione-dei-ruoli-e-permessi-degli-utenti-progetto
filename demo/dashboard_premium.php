<?php
require_once __DIR__ . '/auth.php';
require_login();
require_permission('DASHBOARD_ADVANCED');
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Trademarket AI — Dashboard PREMIUM</title>

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

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

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
        <span class="badge">PREMIUM</span>
      </div>
      <a href="logout.php"
         class="rounded-xl border border-slate-700/70 bg-slate-900/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800/40 transition">
        Logout
      </a>
    </div>
  </header>

  <!-- Main -->
  <main class="mx-auto max-w-6xl px-4 py-10">
    <section class="glass rounded-2xl border border-slate-700/70 p-8 shadow-xl">
      <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight">Dashboard PREMIUM</h1>
          <p class="mt-2 text-slate-300">
            Benvenuto, <span class="font-semibold text-slate-100"><?= htmlspecialchars(current_user()['username']) ?></span>
          </p>
          <p class="mt-2 text-sm text-slate-400">
            Accesso esteso a strumenti AI e indicatori avanzati.
          </p>
        </div>

        <div class="grid grid-cols-3 gap-3 w-full md:w-auto">
          <div class="rounded-2xl border border-slate-700/70 bg-slate-900/20 p-4">
            <p class="text-xs text-slate-400">Piano</p>
            <p class="mt-1 text-xl font-extrabold">Premium</p>
          </div>
          <div class="rounded-2xl border border-slate-700/70 bg-slate-900/20 p-4">
            <p class="text-xs text-slate-400">Segnali</p>
            <p class="mt-1 text-xl font-extrabold text-primary">AI</p>
          </div>
          <div class="rounded-2xl border border-slate-700/70 bg-slate-900/20 p-4">
            <p class="text-xs text-slate-400">Stato</p>
            <p class="mt-1 text-xl font-extrabold">Attivo</p>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <div class="flex items-start justify-between">
            <h3 class="text-lg font-extrabold tracking-tight">Mercati avanzati</h3>
            <span class="badge">Pro</span>
          </div>
          <p class="mt-2 text-sm text-slate-300">Indicatori avanzati e storico esteso.</p>
          <p class="mt-4 text-sm">
            Stato:
            <span class="font-semibold <?= can('MARKET_VIEW_ADVANCED') ? 'text-primary' : 'text-red-400' ?>">
              <?= can('MARKET_VIEW_ADVANCED') ? 'OK' : 'NO' ?>
            </span>
          </p>
        </div>

        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <div class="flex items-start justify-between">
            <h3 class="text-lg font-extrabold tracking-tight">AI Predict</h3>
            <span class="badge">AI</span>
          </div>
          <p class="mt-2 text-sm text-slate-300">Previsioni, scenari e intervalli.</p>
          <p class="mt-4 text-sm">
            Stato:
            <span class="font-semibold <?= can('AI_PREDICT') ? 'text-primary' : 'text-red-400' ?>">
              <?= can('AI_PREDICT') ? 'OK' : 'NO' ?>
            </span>
          </p>
        </div>

        <div class="float rounded-2xl border border-slate-700/70 bg-slate-900/30 p-6 shadow-lg">
          <div class="flex items-start justify-between">
            <h3 class="text-lg font-extrabold tracking-tight">AI Strategy</h3>
            <span class="badge">Sim</span>
          </div>
          <p class="mt-2 text-sm text-slate-300">Simulazioni, backtesting e strategie.</p>
          <p class="mt-4 text-sm">
            Stato:
            <span class="font-semibold <?= can('AI_STRATEGY') ? 'text-primary' : 'text-red-400' ?>">
              <?= can('AI_STRATEGY') ? 'OK' : 'NO' ?>
            </span>
          </p>
        </div>
      </div>

      <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="glass rounded-2xl border border-slate-700/70 p-6 shadow-xl">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs text-slate-400">Widget (demo)</p>
              <h2 class="mt-1 text-xl font-extrabold tracking-tight">Confidenza segnali</h2>
              <p class="mt-2 text-sm text-slate-300">Grafico fittizio per UI (nessuna API).</p>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full border border-slate-700/70 bg-slate-900/30 px-2.5 py-1 text-xs font-semibold text-slate-200">
              <span class="h-2 w-2 rounded-full bg-primary"></span>
              30D
            </span>
          </div>
          <div class="mt-5 rounded-2xl border border-slate-700/70 bg-slate-900/20 p-4">
            <div class="h-[220px]">
              <canvas id="premiumChart"></canvas>
            </div>
          </div>
        </div>

        <div class="glass rounded-2xl border border-slate-700/70 p-6 shadow-xl">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs text-slate-400">Checklist Premium</p>
              <h2 class="mt-1 text-xl font-extrabold tracking-tight">Prossime azioni</h2>
              <p class="mt-2 text-sm text-slate-300">Suggerimenti UI per completare la configurazione.</p>
            </div>
            <span class="badge">Tip</span>
          </div>

          <ul class="mt-5 space-y-3">
            <li class="rounded-xl border border-slate-700/70 bg-slate-900/20 p-4 float">
              <p class="text-sm font-semibold">Crea una watchlist</p>
              <p class="mt-1 text-xs text-slate-400">Aggiungi titoli e indici per ricevere segnali mirati.</p>
            </li>
            <li class="rounded-xl border border-slate-700/70 bg-slate-900/20 p-4 float">
              <p class="text-sm font-semibold">Imposta alert avanzati</p>
              <p class="mt-1 text-xs text-slate-400">Soglie, condizioni e trigger basati su volatilità.</p>
            </li>
            <li class="rounded-xl border border-slate-700/70 bg-slate-900/20 p-4 float">
              <p class="text-sm font-semibold">Prova una simulazione</p>
              <p class="mt-1 text-xs text-slate-400">Backtest e Monte Carlo con i tuoi parametri.</p>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <footer class="mt-8 text-center text-xs text-slate-500">
      © <span id="year"></span> Trademarket AI
    </footer>
  </main>

  <script>
    document.getElementById("year").textContent = new Date().getFullYear();

    const ctx = document.getElementById("premiumChart");
    const labels = ["G1","G4","G7","G10","G13","G16","G19","G22","G25","G28"];
    const data = [0.56,0.60,0.58,0.64,0.70,0.74,0.72,0.78,0.81,0.79];

    new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [{
          label: "Confidenza (demo)",
          data,
          tension: 0.35,
          borderWidth: 2,
          pointRadius: 2,
          pointHoverRadius: 4,
          fill: true,
          backgroundColor: "rgba(14,165,164,0.10)",
          borderColor: "rgba(14,165,164,0.85)",
          pointBackgroundColor: "rgba(124,58,237,0.95)"
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            enabled: true,
            backgroundColor: "rgba(15,23,42,0.92)",
            titleColor: "#e2e8f0",
            bodyColor: "#e2e8f0",
            borderColor: "rgba(148,163,184,0.25)",
            borderWidth: 1
          }
        },
        scales: {
          x: {
            grid: { color: "rgba(148,163,184,0.10)" },
            ticks: { color: "rgba(226,232,240,0.8)" }
          },
          y: {
            suggestedMin: 0.45,
            suggestedMax: 0.9,
            grid: { color: "rgba(148,163,184,0.10)" },
            ticks: { color: "rgba(226,232,240,0.8)" }
          }
        }
      }
    });
  </script>
</body>
</html>
