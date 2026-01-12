<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Liste de tous les clients
     * GET /api/v1/customers
     */
    public function index()
    {
        try {
            $customers = Customer::orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau client
     * POST /api/v1/customers
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => $request->is_active ?? true,
                'balance' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un client spécifique
     * GET /api/v1/customers/{id}
     */
    public function show($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un client
     * PUT /api/v1/customers/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::findOrFail($id);
            
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => $request->is_active ?? $customer->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès',
                'data' => $customer->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un client
     * DELETE /api/v1/customers/{id}
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Vérifier que le client n'a pas de dette
            if ($customer->balance > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un client avec un solde impayé'
                ], 400);
            }
            
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des clients
     * GET /api/v1/customers/stats
     */
    public function stats()
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'customers_with_balance' => Customer::where('balance', '>', 0)->count(),
                'total_balance' => Customer::sum('balance'),
                'average_balance' => Customer::where('balance', '>', 0)->avg('balance') ?? 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique d'un client (ventes + paiements)
     * GET /api/v1/customers/{id}/history
     */
    public function history($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $history = [];

            // Récupérer les ventes
            $sales = Sale::where('customer_id', $id)
                ->with('items.product')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($sales as $sale) {
                $products = [];
                foreach ($sale->items as $item) {
                    $products[] = [
                        'name' => $item->product->name ?? 'Produit inconnu',
                        'quantity' => $item->quantity
                    ];
                }

                $history[] = [
                    'id' => $sale->id,
                    'type' => 'sale',
                    'title' => 'Vente #' . ($sale->invoice_number ?? $sale->id),
                    'description' => $sale->payment_method === 'credit' ? 'Vente à crédit' : 'Vente comptant',
                    'date' => $sale->created_at,
                    'amount' => (float) $sale->total_amount,
                    'products' => $products
                ];
            }

            // Récupérer les paiements
            $payments = Payment::where('customer_id', $id)
                ->orderBy('payment_date', 'desc')
                ->get();

            foreach ($payments as $payment) {
                $paymentMethods = [
                    'cash' => 'Espèces',
                    'mobile_money' => 'Mobile Money',
                    'bank_transfer' => 'Virement bancaire',
                    'check' => 'Chèque'
                ];

                $history[] = [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'title' => 'Paiement',
                    'description' => 'Paiement par ' . ($paymentMethods[$payment->payment_method] ?? $payment->payment_method),
                    'date' => $payment->payment_date,
                    'amount' => (float) $payment->amount,
                    'products' => []
                ];
            }

            // Trier par date (plus récent en premier)
            usort($history, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des paiements d'un client
     * GET /api/v1/customers/{id}/payments
     */
    public function payments($id)
    {
        try {
            $payments = Payment::where('customer_id', $id)
                ->orderBy('payment_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un paiement de dette
     * POST /api/v1/customers/{id}/payments
     */
    public function recordPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,check',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($id);
            $amount = (float) $request->amount;

            // Vérifications
            if ($amount > $customer->balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant dépasse la dette du client'
                ], 400);
            }

            // Créer le paiement
            $payment = Payment::create([
                'customer_id' => $id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'payment_date' => $request->payment_date ?? now()
            ]);

            // Mettre à jour le solde du client
            $customer->balance -= $amount;
            $customer->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajuster le solde d'un client
     * POST /api/v1/customers/{id}/adjust-balance
     */
    public function adjustBalance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'type' => 'required|in:payment,credit,adjustment',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::findOrFail($id);
            
            // Ajuster le solde selon le type
            if ($request->type === 'payment') {
                $customer->balance -= (float) $request->amount;
            } else {
                $customer->balance += (float) $request->amount;
            }
            
            // S'assurer que le solde ne devient pas négatif
            if ($customer->balance < 0) {
                $customer->balance = 0;
            }
            
            $customer->save();

            return response()->json([
                'success' => true,
                'message' => 'Solde ajusté avec succès',
                'data' => $customer->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajustement du solde',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
