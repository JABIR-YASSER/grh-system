<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'nom', 'prenom', 'email', 'telephone', 
        'etat', 'derniere_connexion_at', 'password',
        // 'uuid' et 'photo_url' sont conservés ici au cas où ils existent en BDD 
        // même si on ne les affiche plus dans le formulaire.
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'derniere_connexion_at' => 'datetime',
        ];
    }
    
    public function employe()
    {
        return $this->hasOne(Employe::class);
    }

    /**
     * 🔒 C'est ici que la magie opère !
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // L'utilisateur ne peut entrer que si son état est 'actif'
        return $this->etat === 'actif';
    }

    public function getFilamentName(): string
    {
        return "{$this->prenom} {$this->nom}";
    }
}