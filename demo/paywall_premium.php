<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trademarket AI — Passa a Premium (DEMO)</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ["Inter"] },
      colors: {
        primary: "#0ea5a4",
        accent: "#7c3aed"
      }
    }
  }
}
</script>

<style>
.glass { background: rgba(255,255,255,.06); backdrop-filter: blur(8px); }
.float { transform: translateY(0); transition: transform .25s ease; }
.float:hover { transform: translateY(-6px); }
.badge { background-color:#7c3aed; color:#fff; padding:2px 6px; border-radius:4px; font-size:.75rem; }
</style>
</head>

<body class="bg-slate-900 text-slate-100 antialiased font-sans flex items-center justify-center min-h-screen">

<div class="glass rounded-2xl border border-slate-700/70 p-8 max-w-lg w-full shadow-xl text-center">
  <div class="flex justify-center mb-4">
    <span class="badge">PREMIUM DEMO</span>
  </div>

  <h1 class="text-3xl font-extrabold tracking-tight">
    Sblocca Premium
  </h1>

  <p class="mt-2 text-slate-300">
    Ciao <span class="font-semibold text-slate-100"><?= htmlspecialchars(current_user()['username']) ?></span>,<br>
    questa è una schermata <b>DEMO</b>: nessun pagamento reale.
  </p>

  <div class="mt-6 grid gap-4">
    <div class="glass p-4 rounded-xl">📈 Analisi AI avanzate</div>
    <div class="glass p-4 rounded-xl">🧠 Strategie probabilistiche</div>
    <div class="glass p-4 rounded-xl">⚡ Mercati avanzati</div>
  </div>

  <div class="mt-8 flex flex-col sm:flex-row gap-3">
    <a href="dashboard_free.php"
       class="w-full float rounded-xl border border-slate-600 px-4 py-2 text-slate-200 text-center">
      Torna alla FREE
    </a>

    <!-- ✅ ORA REINDIRIZZA ALLA DASHBOARD PREMIUM DEMO -->
    <a href="dashboard_premium_demo.php"
       class="w-full float rounded-xl bg-primary text-slate-900 font-extrabold px-4 py-2 inline-flex items-center justify-center">
      Sblocca (DEMO)
    </a>
  </div>
</div>

</body>
</html>
