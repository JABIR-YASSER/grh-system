<?php

namespace App\Imports;

use App\Models\Pointage;
use App\Models\Employe;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class PointagesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. On cherche l'employé
        $employe = Employe::where('matricule', trim($row['matricule']))->first();

        if (!$employe) {
            return null;
        }

        // 2. Formatage sécurisé de la date
        try {
            $datePointage = Carbon::createFromFormat('d/m/Y', trim($row['date']));
        } catch (\Exception $e) {
            $datePointage = Carbon::parse(trim($row['date']));
        }

        $dateFinale = $datePointage->format('Y-m-d');
        
        // 👇 CORRECTION ICI : On ne garde QUE l'heure, sans la date 👇 
        $heureArrivee = !empty($row['arrivee']) ? trim($row['arrivee']) : null;
        $heureDepart  = !empty($row['depart'])  ? trim($row['depart'])  : null;

        // 3. updateOrCreate pour éviter les doublons
        return Pointage::updateOrCreate(
            [
                'employe_id' => $employe->id,
                'date'       => $dateFinale,
            ],
            [
                'heure_arrivee' => $heureArrivee,
                'heure_depart'  => $heureDepart,
            ]
        );
    }
}