<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

use Spatie\Permission\Models\Role;

// Importation de TOUS les modèles
use App\Models\User;
use App\Models\Departement;
use App\Models\Poste;
use App\Models\Employe;
use App\Models\Contrat;
use App\Models\Pointage;
use App\Models\DossierEmploye;
use App\Models\Prime;
use App\Models\Paie;
use App\Models\Conge;
use App\Models\RecrutementInterne;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // 1. CRÉATION DES RÔLES
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleEmploye = Role::firstOrCreate(['name' => 'employe']);

        // 2. CRÉATION DES DÉPARTEMENTS ET POSTES
        $departements = [
            ['code' => 'RH', 'libelle' => 'Ressources Humaines', 'description' => 'Gestion du personnel'],
            ['code' => 'IT', 'libelle' => 'Systèmes d\'Information', 'description' => 'Développement et réseau'],
            ['code' => 'COM', 'libelle' => 'Commercial', 'description' => 'Ventes et marketing'],
            ['code' => 'FIN', 'libelle' => 'Finances', 'description' => 'Comptabilité et paie'],
        ];
        foreach ($departements as $dept) { Departement::create($dept); }

        $postes = [
            ['titre' => 'Directeur RH', 'salaire_min' => 15000, 'salaire_max' => 25000],
            ['titre' => 'Développeur Full Stack', 'salaire_min' => 8000, 'salaire_max' => 18000],
            ['titre' => 'Comptable', 'salaire_min' => 6000, 'salaire_max' => 12000],
            ['titre' => 'Commercial Terrain', 'salaire_min' => 5000, 'salaire_max' => 15000],
            ['titre' => 'Technicien Support', 'salaire_min' => 4000, 'salaire_max' => 8000],
        ];
        foreach ($postes as $poste) { Poste::create($poste); }

        // 3. CRÉATION DES COMPTES
        $adminUser = User::create([
            'uuid' => Str::uuid(), 'nom' => 'Jabir', 'prenom' => 'Yasser',
            'email' => 'admin@grh.com', 'password' => Hash::make('password'),
            'telephone' => '0600000000', 'etat' => 'actif',
        ]);
        $adminUser->assignRole($roleAdmin);

        $employeUser = User::create([
            'uuid' => Str::uuid(), 'nom' => 'Test', 'prenom' => 'Employe',
            'email' => 'employe@grh.com', 'password' => Hash::make('password'),
            'telephone' => '0611111111', 'etat' => 'actif',
        ]);
        $employeUser->assignRole($roleEmploye);

        $users = collect([$adminUser, $employeUser]);
        for ($i = 0; $i < 20; $i++) {
            $nouvelEmploye = User::create([
                'uuid' => Str::uuid(), 'nom' => $faker->lastName, 'prenom' => $faker->firstName,
                'email' => $faker->unique()->safeEmail, 'password' => Hash::make('password'), 
                'telephone' => $faker->phoneNumber, 'etat' => 'actif',
            ]);
            $nouvelEmploye->assignRole($roleEmploye);
            $users->push($nouvelEmploye);
        }

        // 4. CRÉATION DES EMPLOYÉS, CONTRATS ET DOSSIERS
        $anneeEnCours = date('Y');
        
        foreach ($users as $index => $user) {
            $employe = Employe::create([
                'user_id' => $user->id,
                'matricule' => 'EMP-' . $anneeEnCours . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'departement_id' => Departement::inRandomOrder()->first()->id,
                'poste_id' => Poste::inRandomOrder()->first()->id,
                'date_embauche' => $faker->dateTimeBetween('-3 years', '-1 month'), // Embauchés il y a au moins 1 mois
            ]);

            // Dossier de l'employé
            DossierEmploye::create([
                'employe_id' => $employe->id,
                'numero' => 'DOS-' . str_pad($employe->id, 4, '0', STR_PAD_LEFT),
                'statut' => 'actif',
                'cin' => strtoupper($faker->bothify('??######')), // Ex: AB123456
                'sexe' => $faker->randomElement(['H', 'F']),
                'date_naissance' => $faker->dateTimeBetween('-50 years', '-20 years'),
                'situation_familiale' => $faker->randomElement(['celibataire', 'marie', 'divorce', 'veuf']),
                'nombre_enfants' => $faker->numberBetween(0, 5),
                'telephone' => $faker->phoneNumber,
            ]);

            $salaire = $faker->randomFloat(2, 4000, 20000);
            
            Contrat::create([
                'employe_id' => $employe->id,
                'type' => $faker->randomElement(['CDI', 'CDD', 'Stage']),
                'date_debut' => $employe->date_embauche,
                'date_fin' => null,
                'salaire' => $salaire,
            ]);

            // Paie de Février 2026 pour tout le monde
            Paie::create([
                'employe_id' => $employe->id,
                'mois' => 'Février',
                'annee' => 2026,
                'salaire_brut' => $salaire,
                'deductions' => $salaire * 0.20, // 20% de retenues (CNSS, IGR...)
                'net_a_payer' => $salaire * 0.80,
                'statut' => 'paye',
            ]);

            // Primes aléatoires (30% des employés ont une prime)
            if (rand(1, 100) <= 30) {
                Prime::create([
                    'employe_id' => $employe->id,
                    'type' => $faker->randomElement(['Rendement', 'Ancienneté', 'Aïd']),
                    'montant' => $faker->randomFloat(2, 500, 3000),
                    'date' => Carbon::now()->subDays(rand(1, 15)),
                    'payee' => $faker->boolean(80),
                ]);
            }

            // Congés aléatoires (40% des employés ont demandé un congé)
            if (rand(1, 100) <= 40) {
                Conge::create([
                    'employe_id' => $employe->id,
                    'type' => $faker->randomElement(['Payé', 'Maladie', 'Sans solde']),
                    'date_debut' => Carbon::now()->addDays(rand(1, 10)),
                    'date_fin' => Carbon::now()->addDays(rand(11, 20)),
                    'jours' => rand(2, 5),
                    'motif' => $faker->sentence(),
                    'statut' => $faker->randomElement(['en_attente', 'accepte', 'refuse']),
                ]);
            }
        }

        // 5. CRÉATION DES POINTAGES
        $tousLesEmployes = Employe::all();
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            if ($date->isSunday()) continue;

            foreach ($tousLesEmployes as $employe) {
                if (rand(1, 100) <= 85) {
                    Pointage::create([
                        'employe_id' => $employe->id,
                        'date' => $date->toDateString(),
                        'heure_arrivee' => $faker->time('H:i:s', '08:45:00'),
                        'heure_depart' => $faker->time('H:i:s', '18:15:00'),
                        'created_at' => $date, 
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        // 6. RECRUTEMENTS INTERNES
        // On crée 5 demandes de changement de poste au hasard
        for ($j = 0; $j < 5; $j++) {
            RecrutementInterne::create([
                'poste_id' => Poste::inRandomOrder()->first()->id,
                'employe_id' => Employe::inRandomOrder()->first()->id,
                'statut' => $faker->randomElement(['en_attente', 'entretien', 'approuve', 'rejete']),
            ]);
        }
    }
}