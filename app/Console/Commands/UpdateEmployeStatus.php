<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conge;
use App\Models\DossierEmploye;
use Carbon\Carbon;

//php artisan employes:update-statut


class UpdateEmployeStatus extends Command
{
    protected $signature = 'employes:update-statut';
    protected $description = 'Met à jour automatiquement le statut des employés selon leurs dates de congés';

    public function handle()
    {
        $today = Carbon::today();
        
        $this->info("=== DÉBUT DU DÉBOGAGE ===");
        $this->info("1. La date du jour vue par le serveur est : " . $today->toDateString());

        // --- PARTIE 1 : DÉPARTS EN CONGÉ ---
        $this->info("-------------------------------------------------");
        $this->info("=== ANALYSE DES DÉPARTS EN CONGÉ ===");
        
        $congesEnCours = Conge::where('statut', 'accepte')
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today)
            ->get();

        $countEnConge = 0;
        foreach ($congesEnCours as $conge) {
            $dossier = DossierEmploye::where('employe_id', $conge->employe_id)->first();
            if ($dossier && $dossier->statut !== 'en_conge') {
                $dossier->update(['statut' => 'en_conge']);
                $countEnConge++;
            }
        }
        $this->info("   -> " . $countEnConge . " employé(s) passé(s) en congé.");

        // --- PARTIE 2 : RETOURS DE CONGÉ ---
        $this->info("-------------------------------------------------");
        $this->info("=== ANALYSE DES RETOURS (Date de fin < Aujourd'hui) ===");

        $congesTermines = Conge::where('statut', 'accepte')
            ->whereDate('date_fin', '<', $today)
            ->get();

        $this->info("   -> Nombre de congés terminés trouvés : " . $congesTermines->count());

        $countRetours = 0;
        foreach ($congesTermines as $conge) {
            $this->comment("   -> Vérification du congé ID {$conge->id} (Terminé le {$conge->date_fin})");
            $dossier = DossierEmploye::where('employe_id', $conge->employe_id)->first();
            
            if ($dossier && $dossier->statut === 'en_conge') {
                $dossier->update(['statut' => 'actif']);
                $this->info("   ✅ SUCCÈS : Employé ID " . $conge->employe_id . " mis à jour -> 'actif' !");
                $countRetours++;
            } else {
                $this->comment("   ⚠️ INFO : L'employé ID " . $conge->employe_id . " est DÉJÀ actif (ou sans dossier).");
            }
        }

        $this->info("-------------------------------------------------");
        $this->info("Mise à jour terminée : {$countEnConge} départ(s) en congé, {$countRetours} retour(s).");
    }
}