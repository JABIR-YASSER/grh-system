<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulletin de Paie - {{ $paie->employe->matricule }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #ddd; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1e3a8a; }
        .company-info { float: right; text-align: right; font-size: 11px; }
        
        .box { border: 1px solid #e5e7eb; padding: 15px; margin-bottom: 20px; background-color: #f9fafb; border-radius: 5px; }
        
        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.main-table th { background-color: #f3f4f6; text-align: left; padding: 10px; border: 1px solid #d1d5db; }
        table.main-table td { padding: 10px; border: 1px solid #d1d5db; }
        
        .text-right { text-align: right; }
        .text-red { color: #dc2626; }
        .total-row td { font-weight: bold; background-color: #e5e7eb; }
        
        .net-pay { 
            font-size: 18px; 
            font-weight: bold; 
            color: #166534; 
            text-align: right; 
            padding: 15px; 
            border: 2px solid #166534; 
            margin-top: 20px; 
            border-radius: 5px;
            background-color: #f0fdf4;
        }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div style="float: left;" class="logo">GRH SYSTEM</div>
        <div class="company-info">
            <strong>Société PFE Tech</strong><br>
            Avenue Hassan II, Mohammedia<br>
            contact@grh-system.com
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; vertical-align: top; border: none; padding: 0;">
                    <strong>Matricule :</strong> {{ $paie->employe->matricule }}<br>
                    <strong>Nom Prénom :</strong> {{ $paie->employe->user->nom }} {{ $paie->employe->user->prenom }}<br>
                    <strong>Département :</strong> {{ $paie->employe->departement->libelle ?? 'Non défini' }}<br>
                    <strong>Poste :</strong> {{ $paie->employe->poste->titre ?? 'Non défini' }}
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top; border: none; padding: 0;">
                    <strong>Période :</strong> {{ $paie->mois }} {{ $paie->annee }}<br>
                    <strong>Date d'édition :</strong> {{ now()->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th>Rubrique</th>
                <th class="text-right">Base</th>
                <th class="text-right">Taux</th>
                <th class="text-right">Gains (+)</th>
                <th class="text-right">Retenues (-)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salaire de Base</td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['salaire_base'] ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['salaire_base'] ?? 0, 2, ',', ' ') }}</td>
                <td></td>
            </tr>

            @if(isset($paie->donnees_calcul['total_primes']) && $paie->donnees_calcul['total_primes'] > 0)
            <tr>
                <td>Primes et Indemnités</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['total_primes'], 2, ',', ' ') }}</td>
                <td></td>
            </tr>
            @endif

            @if(isset($paie->donnees_calcul['retenue_absence']) && $paie->donnees_calcul['retenue_absence'] > 0)
            <tr>
                <td>Absence injustifiée ({{ $paie->donnees_calcul['jours_absents'] }} jours)</td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['salaire_base'] ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">-</td>
                <td></td>
                <td class="text-right text-red">{{ number_format($paie->donnees_calcul['retenue_absence'], 2, ',', ' ') }}</td>
            </tr>
            @endif

            @if(isset($paie->donnees_calcul['montant_cnss']))
            <tr>
                <td>Cotisation CNSS</td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['base_cnss'] ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">4.48 %</td>
                <td></td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['montant_cnss'], 2, ',', ' ') }}</td>
            </tr>
            @endif

            @if(isset($paie->donnees_calcul['montant_amo']))
            <tr>
                <td>Cotisation AMO</td>
                <td class="text-right">{{ number_format($paie->salaire_brut, 2, ',', ' ') }}</td>
                <td class="text-right">2.26 %</td>
                <td></td>
                <td class="text-right">{{ number_format($paie->donnees_calcul['montant_amo'], 2, ',', ' ') }}</td>
            </tr>
            @endif

            <tr class="total-row">
                <td colspan="3">Totaux (MAD)</td>
                <td class="text-right">{{ number_format($paie->salaire_brut, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($paie->deductions, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-pay">
        NET À PAYER : {{ number_format($paie->net_a_payer, 2, ',', ' ') }} MAD
    </div>

    <div class="footer">
        Ce bulletin de paie est généré électroniquement par GRH System. Document strictement confidentiel.<br>
        Pour toute réclamation, veuillez contacter le service des Ressources Humaines dans un délai de 30 jours.
    </div>

</body>
</html>