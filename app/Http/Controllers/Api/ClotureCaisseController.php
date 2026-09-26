<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\ClotureCaisse;
use App\Services\ClotureCaisseService;
use Illuminate\Http\Request;

class ClotureCaisseController extends Controller
{
    public function __construct(private readonly ClotureCaisseService $service) {}

    /** GET /api/boutiques/{boutique}/clotures?mois=2026-09 */
    public function index(Request $request, Boutique $boutique)
    {
        $this->checkAdmin($request, $boutique);

        $query = $boutique->clotures()->orderByDesc('date_cloture');

        if ($mois = $request->query('mois')) {
            $carbonMonth = \Illuminate\Support\Carbon::parse($mois . '-01');
            $query->whereYear('date_cloture', $carbonMonth->year)
                  ->whereMonth('date_cloture', $carbonMonth->month);
        }

        return response()->json($query->get());
    }

    /** POST /api/boutiques/{boutique}/clotures — déclencher une clôture manuelle */
    public function store(Request $request, Boutique $boutique)
    {
        $this->checkAdmin($request, $boutique);

        $data = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date    = isset($data['date']) ? \Carbon\Carbon::parse($data['date']) : null;
        $cloture = $this->service->cloturer($boutique, $date);

        return response()->json($cloture, 201);
    }

    /** PUT /api/clotures/{cloture}/solde-reel — enregistrer un contrôle physique */
    public function enregistrerSoldeReel(Request $request, ClotureCaisse $cloture)
    {
        $boutique = $cloture->boutique;
        $this->checkAdmin($request, $boutique);

        $data = $request->validate([
            'solde_reel' => 'required|numeric',
        ]);

        $cloture = $this->service->enregistrerSoldeReel($cloture, (float) $data['solde_reel']);

        return response()->json($cloture);
    }
}
