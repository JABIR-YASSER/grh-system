<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained()->onDelete('cascade');
            
            // --- Identité ---
            $table->string('numero')->unique(); 
            $table->string('cin')->unique();
            $table->string('sexe');
            $table->date('date_naissance')->nullable();
            $table->string('situation_familiale');
            $table->integer('nombre_enfants')->default(0);

            // --- Contact ---
            $table->string('telephone');
            $table->string('email_personnel')->nullable();
            $table->text('adresse')->nullable();
            $table->string('contact_urgence')->nullable();

            // --- Administratif & Paie ---
            $table->string('rib', 24)->nullable();
            $table->string('cnss')->nullable();
            $table->string('statut')->default('actif');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_employes');
    }
};