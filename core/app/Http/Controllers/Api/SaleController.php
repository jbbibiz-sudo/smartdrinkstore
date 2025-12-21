<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('customer');

        // 🔹 Filtres dates
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from . ' 00:00:00',
                $request->date_to   . ' 23:59:59'
            ]);
        } elseif ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        } elseif ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // 🔹 Filtre mode de paiement
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // 🔹 Filtre type de vente
        if ($request->filled('sale_type')) {
            $query->where('type', $request->sale_type); // colonne 'type' en base
        }

        // 🔹 Filtre recherche (invoice + nom client + téléphone)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($qc) use ($search) {
                      $qc->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // 🔹 Retour JSON
        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get()
        ]);
    }
}
