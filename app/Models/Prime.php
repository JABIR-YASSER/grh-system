<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prime extends Model
{
    use HasFactory;

    // 👇 LA SOLUTION EST ICI : Cela autorise Filament à remplir les colonnes
    protected $guarded = [];

    // 👇 On en profite pour ajouter la relation avec l'Employe
    // Cela permettra à Filament d'afficher le nom de l'employé dans le tableau
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}