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

        // ==========================================
        // 1. CRÉATION DES RÔLES
        // ==========================================
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleEmploye = Role::firstOrCreate(['name' => 'employe']);

        // ==========================================
        // 2. CRÉATION DES DÉPARTEMENTS ET POSTES
        // ==========================================
        $departements = [
            ['code' => 'RH', 'libelle' => 'Ressources Humaines', 'description' => 'Gestion du personnel'],
            ['code' => 'IT', 'libelle' => 'Systèmes d\'Information', 'description' => 'Développement et réseau'],
            ['code' => 'COM', 'libelle' => 'Commercial', 'description' => 'Ventes et marketing'],
            ['code' => 'FIN', 'libelle' => 'Finances', 'description' => 'Comptabilité et paie'],
        ];
        foreach ($departements as $dept) { Departement::firstOrCreate(['code' => $dept['code']], $dept); }

        $postes = [
            ['titre' => 'Directeur RH', 'salaire_min' => 15000, 'salaire_max' => 25000],
            ['titre' => 'Développeur Full Stack', 'salaire_min' => 8000, 'salaire_max' => 18000],
            ['titre' => 'Comptable', 'salaire_min' => 6000, 'salaire_max' => 12000],
            ['titre' => 'Commercial Terrain', 'salaire_min' => 5000, 'salaire_max' => 15000],
            ['titre' => 'Technicien Support', 'salaire_min' => 4000, 'salaire_max' => 8000],
        ];
        foreach ($postes as $poste) { Poste::firstOrCreate(['titre' => $poste['titre']], $poste); }

        // ==========================================
        // 3. CRÉATION DES COMPTES UTILISATEURS
        // ==========================================
        $adminUser = User::firstOrCreate(['email' => 'admin@grh.com'], [
            'uuid' => Str::uuid(), 'nom' => 'Jabir', 'prenom' => 'Yasser',
            'password' => Hash::make('password'),
            'telephone' => '0600000000', 'etat' => 'actif',
        ]);
        $adminUser->assignRole($roleAdmin);

        $employeUser = User::firstOrCreate(['email' => 'employe@grh.com'], [
            'uuid' => Str::uuid(), 'nom' => 'Test', 'prenom' => 'Employe',
            'password' => Hash::make('password'),
            'telephone' => '0611111111', 'etat' => 'actif',
        ]);
        $employeUser->assignRole($roleEmploye);

        $users = collect([$adminUser, $employeUser]);
        
        // On génère 20 employés supplémentaires au hasard
        for ($i = 0; $i < 20; $i++) {
            $nouvelEmploye = User::create([
                'uuid' => Str::uuid(), 'nom' => $faker->lastName, 'prenom' => $faker->firstName,
                'email' => $faker->unique()->safeEmail, 'password' => Hash::make('password'), 
                'telephone' => $faker->phoneNumber, 'etat' => 'actif',
            ]);
            $nouvelEmploye->assignRole($roleEmploye);
            $users->push($nouvelEmploye);
        }

        // ==========================================
        // 4. CRÉATION DES EMPLOYÉS, CONTRATS ET PAIE
        // ==========================================
        $anneeEnCours = date('Y');
        
        foreach ($users as $index => $user) {
            $employe = Employe::firstOrCreate(['user_id' => $user->id], [
                'matricule' => 'EMP-' . $anneeEnCours . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'departement_id' => Departement::inRandomOrder()->first()->id,
                'poste_id' => Poste::inRandomOrder()->first()->id,
                'date_embauche' => $faker->dateTimeBetween('-3 years', '-1 month'),
            ]);

            // Dossier de l'employé
            DossierEmploye::firstOrCreate(['employe_id' => $employe->id], [
                'numero' => 'DOS-' . str_pad($employe->id, 4, '0', STR_PAD_LEFT),
                'statut' => 'actif',
                'cin' => strtoupper($faker->bothify('??######')),
                'sexe' => $faker->randomElement(['H', 'F']),
                'date_naissance' => $faker->dateTimeBetween('-50 years', '-20 years'),
                'situation_familiale' => $faker->randomElement(['celibataire', 'marie', 'divorce', 'veuf']),
                'nombre_enfants' => $faker->numberBetween(0, 5),
                'telephone' => $faker->phoneNumber,
            ]);

            $salaire = $faker->randomFloat(2, 4000, 20000);
            
            Contrat::firstOrCreate(['employe_id' => $employe->id], [
                'type' => $faker->randomElement(['CDI', 'CDD', 'Stage']),
                'date_debut' => $employe->date_embauche,
                'date_fin' => null,
                'salaire' => $salaire,
            ]);

            // Paie générique pour MARS et AVRIL 2026
            foreach (['Mars', 'Avril'] as $mois) {
                Paie::firstOrCreate([
                    'employe_id' => $employe->id,
                    'mois' => $mois,  // 👈 Enregistre "Mars" et "Avril" en texte
                    'annee' => 2026,
                ], [
                    'salaire_brut' => $salaire,
                    'deductions' => $salaire * 0.20,
                    'net_a_payer' => $salaire * 0.80,
                    'statut' => 'paye',
                ]);
            }

            // Primes aléatoires (30%)
            if (rand(1, 100) <= 30) {
                Prime::create([
                    'employe_id' => $employe->id,
                    'type' => $faker->randomElement(['Rendement', 'Ancienneté', 'Aïd']),
                    'montant' => $faker->randomFloat(2, 500, 3000),
                    'date' => Carbon::now()->subDays(rand(1, 15)),
                    'payee' => $faker->boolean(80),
                ]);
            }

            // Congés aléatoires (40%)
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

        // ==========================================
        // 5. CRÉATION DES POINTAGES (MARS ET AVRIL 2026)
        // ==========================================
        $tousLesEmployes = Employe::all();
        $this->command->info("⏳ Génération des pointages pour Mars et Avril 2026...");
        
        $dateDebut = Carbon::create(2026, 3, 1);
        $dateFin = Carbon::create(2026, 4, 30);
        
        // Boucle jour par jour entre ces deux dates
        for ($date = $dateDebut->copy(); $date->lte($dateFin); $date->addDay()) {
            
            // 🛑 On saute les week-ends (Samedi et Dimanche)
            if ($date->isSunday()) continue;

            foreach ($tousLesEmployes as $employe) {
                // ✅ Présence à 95%
                if (rand(1, 100) <= 95) {
                    
                    // 📊 Génération de VRAIS retards
                    if (rand(1, 100) <= 80) {
                        // À l'heure : Arrivée entre 08:00 et 09:15
                        $heureArrivee = Carbon::createFromTime(rand(8, 9), rand(0, 15))->format('H:i:s');
                    } else {
                        // En retard : Arrivée entre 09:16 et 10:30
                        $heureArrivee = Carbon::createFromTime(rand(9, 10), rand(16, 30))->format('H:i:s');
                    }

                    // Départ normal entre 17:00 et 18:30
                    $heureDepart = Carbon::createFromTime(rand(17, 18), rand(0, 30))->format('H:i:s');

                    Pointage::updateOrCreate(
                        [
                            'employe_id' => $employe->id,
                            'date' => $date->toDateString(),
                        ],
                        [
                            'heure_arrivee' => $heureArrivee,
                            'heure_depart' => $heureDepart,
                            'created_at' => $date, 
                            'updated_at' => $date,
                        ]
                    );
                }
            }
        }

        // ==========================================
        // 6. RECRUTEMENTS INTERNES
        // ==========================================
        for ($j = 0; $j < 5; $j++) {
            RecrutementInterne::create([
                'poste_id' => Poste::inRandomOrder()->first()->id,
                'employe_id' => Employe::inRandomOrder()->first()->id,
                'statut' => $faker->randomElement(['en_attente', 'entretien', 'approuve', 'rejete']),
            ]);
        }
        
        $this->command->info("✅ Base de données initialisée avec succès !");
    }
}