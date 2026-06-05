<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable - Erreur 404</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f0f2f8] font-sans h-screen flex items-center justify-center selection:bg-[#1a9e7e] selection:text-white">

    <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-[0_2px_20px_rgba(26,35,64,0.04)] border border-gray-100 text-center mx-4 relative overflow-hidden">

        <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-50 rounded-full blur-3xl opacity-80 pointer-events-none"></div>

        <div class="relative w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-gray-100">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-100">
                <span class="text-[#1a9e7e] font-bold text-lg font-mono">?</span>
            </div>
        </div>

        <h1 class="font-mono font-bold text-4xl text-[#1a2340] mb-2 tracking-tight">404</h1>
        <h2 class="text-lg font-bold text-[#1a2340] mb-3 uppercase tracking-widest text-opacity-80">Page introuvable</h2>

        <p class="text-sm text-gray-500 mb-8 leading-relaxed px-4">
            Oups ! Il semblerait que vous vous soyez égaré. La page que vous recherchez n'existe pas ou a été déplacée.
        </p>

        <a href="javascript:history.back()"
           class="inline-flex items-center justify-center gap-2 bg-[#1a9e7e] hover:bg-[#158a6c] text-white text-sm font-bold px-6 py-3.5 rounded-xl transition-all shadow-[0_4px_16px_rgba(26,158,126,0.3)] hover:shadow-[0_6px_20px_rgba(26,158,126,0.4)] hover:-translate-y-0.5 w-full group">
            <svg class="w-4 h-4 text-emerald-100 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour en arrière
        </a>

    </div>

</body>
</html>
