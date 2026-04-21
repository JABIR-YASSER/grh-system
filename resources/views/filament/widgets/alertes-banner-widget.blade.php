<x-filament-widgets::widget>
    {{-- On utilise "style" pour forcer le rouge (Hex: #dc2626) --}}
    <div class="rounded-xl p-4 flex items-center justify-between shadow-lg" 
         style="background-color: #dc2626 !important; border: 1px solid #b91c1c;">
        
        <div class="flex items-center gap-4">
            {{-- On force l'icône en blanc --}}
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 animate-pulse" style="color: white !important;" />
            
            <div>
                {{-- On force le texte en blanc avec style --}}
                <h2 class="text-lg font-bold" style="color: white !important; margin: 0;">
                    Action Requise Immédiate
                </h2>
                <p class="text-sm" style="color: rgba(255, 255, 255, 0.9) !important; margin: 0;">
                    Vous avez <strong>{{ $congesEnAttente }} demande(s) de congé</strong> en attente de validation.
                </p>
            </div>
        </div>
        
        {{-- Le bouton --}}
        <a href="{{ route('filament.app.resources.conges.index') }}" 
           class="px-5 py-2 font-bold rounded-lg shadow-sm transition duration-200"
           style="background-color: white !important; color: #dc2626 !important; text-decoration: none;">
            Traiter maintenant
        </a>
        
    </div>
</x-filament-widgets::widget>