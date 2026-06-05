<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - Erreur 403</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f0f2f8] font-sans h-screen flex items-center justify-center selection:bg-[#1a9e7e] selection:text-white">

    <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-[0_2px_20px_rgba(26,35,64,0.04)] border border-gray-100 text-center mx-4 relative overflow-hidden">

        <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>

        <div class="relative w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-red-100">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>

        <h1 class="font-mono font-bold text-4xl text-[#1a2340] mb-2 tracking-tight">403</h1>
        <h2 class="text-lg font-bold text-[#1a2340] mb-3 uppercase tracking-widest text-opacity-80">Accès non autorisé</h2>

        <p class="text-sm text-gray-500 mb-8 leading-relaxed px-4">
            Désolé, vous n'avez pas les permissions nécessaires pour consulter cette page ou effectuer cette action.
        </p>

        <a href="javascript:history.back()"
           class="inline-flex items-center justify-center gap-2 bg-[#1a2340] hover:bg-[#111827] text-white text-sm font-bold px-6 py-3.5 rounded-xl transition-all shadow-md hover:scale-[1.02] w-full group">
            <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retourner à la page précédente
        </a>
    </div>

</body>
</html>
