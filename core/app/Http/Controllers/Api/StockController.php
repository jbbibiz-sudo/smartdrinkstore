<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function movements(Request $request)
    {
        $query = StockMovement::with('product')->latest();
        if($request->has('product_id')) $query->where('product_id',$request->product_id);
        if($request->has('type')) $query->where('type',$request->type);
        return response()->json($query->limit(100)->get());
    }

    public function createMovement(Request $request)
    {
        $validated = $request->validate([
            'product_id'=>'required|exists:products,id',
            'type'=>'required|in:in,out,consignment_return',
            'quantity'=>'required|integer|min:1',
            'reason'=>'required|string',
            'expiry_date'=>'nullable|date',
            'empty_packages'=>'nullable|integer|min:0'
        ]);

        $movement = DB::transaction(function() use($validated){
            $product = Product::findOrFail($validated['product_id']);
            $previous = $product->stock;

            if($validated['type']==='in') $product->stock += $validated['quantity'];
            elseif($validated['type']==='out'){
                if($previous < $validated['quantity']) abort(400,'Stock insuffisant');
                $product->stock -= $validated['quantity'];
            }

            $product->save();
            return StockMovement::create(array_merge($validated,['previous_stock'=>$previous,'new_stock'=>$product->stock]));
        });

        return response()->json($movement->load('product'),201);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|integer|min:1',
            'reason'=>'nullable|string'
        ]);
        $product = Product::findOrFail($request->product_id);
        $product->addStock($request->quantity,$request->reason ?? 'Ajout',null);
        return response()->json(['product'=>$product->fresh(),'message'=>'Stock ajouté']);
    }

    public function removeStock(Request $request)
    {
        $request->validate([
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|integer|min:1',
            'reason'=>'nullable|string'
        ]);
        $product = Product::findOrFail($request->product_id);
        $product->removeStock($request->quantity,$request->reason ?? 'Retrait',null);
        return response()->json(['product'=>$product->fresh(),'message'=>'Stock retiré']);
    }

    public function alerts()
    {
        $low = Product::with('category')->active()->lowStock()->orderBy('stock')->get();
        $out = Product::with('category')->active()->where('stock',0)->get();
        return response()->json([
            'low_stock'=>$low,'out_of_stock'=>$out,
            'low_stock_count'=>$low->count(),'out_of_stock_count'=>$out->count()
        ]);
    }

    public function stockValuation()
    {
        $products = Product::with('category')->active()->get();
        $valuation = [
            'total_units'=>$products->sum('stock'),
            'total_cost_value'=>$products->sum(fn($p)=>$p->stock*$p->cost_price),
            'total_sell_value'=>$products->sum(fn($p)=>$p->stock*$p->unit_price),
            'by_category'=>$products->groupBy('category.name')->map(fn($ps,$cat)=>[
                'count'=>$ps->count(),
                'total_units'=>$ps->sum('stock'),
                'cost_value'=>$ps->sum(fn($p)=>$p->stock*$p->cost_price),
                'sell_value'=>$ps->sum(fn($p)=>$p->stock*$p->unit_price),
            ]),
        ];
        $valuation['potential_profit'] = $valuation['total_sell_value'] - $valuation['total_cost_value'];
        return response()->json($valuation);
    }

    public function stockStatusReport()
    {
        $products = Product::with('category')->active()->get();
        return response()->json([
            'total_products'=>$products->count(),
            'in_stock'=>$products->where('stock','>',0)->count(),
            'out_of_stock'=>$products->where('stock',0)->count(),
            'low_stock'=>$products->filter(fn($p)=>$p->is_low_stock)->count(),
            'overstocked'=>$products->where('stock','>',100)->count()
        ]);
    }
}
