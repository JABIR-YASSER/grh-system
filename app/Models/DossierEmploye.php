<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DossierEmploye extends Model
{
    protected $fillable = [
        'employe_id',
        'numero',
        'cin',
        'sexe',
        'date_naissance',
        'situation_familiale',
        'nombre_enfants',
        'telephone',
        'email_personnel',
        'adresse',
        'contact_urgence',
        'rib',
        'cnss',
        'statut',
    ];

    protected $casts = [
        'scan_cin' => 'array', // Pour gérer plusieurs fichiers
        'date_naissance' => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}