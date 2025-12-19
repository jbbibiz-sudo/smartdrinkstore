<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category','subcategory'])->active();

        if($request->filled('search')) $query->search($request->search);
        if($request->filled('category_id')) $query->where('category_id',$request->category_id);
        if($request->boolean('low_stock')) $query->lowStock();

        $sortBy = $request->get('sort_by','name');
        $sortOrder = $request->get('sort_order','asc');
        return response()->json($query->orderBy($sortBy,$sortOrder)->paginate($request->get('per_page',15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'=>'required',
            'sku'=>'required|unique:products',
            'category_id'=>'required|exists:categories,id',
            'unit_price'=>'required|numeric',
            'cost_price'=>'required|numeric',
            'stock'=>'required|integer',
            'min_stock'=>'required|integer'
        ]);

        $product = Product::create($validated);
        return response()->json(['data'=>$product],201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['category','subcategory','supplier','stockMovements']));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'=>'sometimes',
            'unit_price'=>'sometimes|numeric',
            'cost_price'=>'sometimes|numeric',
            'min_stock'=>'sometimes|integer',
        ]);
        $product->update($validated);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active'=>false]);
        return response()->json(null,204);
    }

    public function stats()
    {
        $active = Product::active();
        return response()->json([
            'total_products'=>$active->count(),
            'low_stock_count'=>$active->lowStock()->count(),
            'total_stock_value'=>$active->sum(DB::raw('stock*cost_price')),
            'out_of_stock'=>$active->where('stock',0)->count()
        ]);
    }

    public function lowStock()
    {
        return response()->json(Product::with('category')->active()->lowStock()->orderBy('stock')->get());
    }

    public function outOfStock()
    {
        return response()->json(
            Product::with('category')
                ->active()
                ->where('stock', 0)
                ->orderBy('name')
                ->get()
        );
    }
}
