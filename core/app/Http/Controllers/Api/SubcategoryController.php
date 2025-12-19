<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Subcategory::with('category');

        if($request->has('category_id')) $query->where('category_id',$request->category_id);
        if($request->has('is_active')) $query->where('is_active',$request->boolean('is_active'));
        if($request->get('with_products_count',false)) $query->withCount('products');

        $sortBy = $request->get('sort_by','position');
        $sortOrder = $request->get('sort_order','asc');
        $query->orderBy($sortBy,$sortOrder);

        return response()->json($query->paginate($request->get('per_page',20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'=>'required|string|max:255',
            'code'=>'required|string|max:20|unique:subcategories,code',
            'category_id'=>'required|exists:categories,id',
            'description'=>'nullable|string',
            'color'=>'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'position'=>'nullable|integer|min:0',
            'is_active'=>'nullable|boolean'
        ]);

        $category = Category::find($validated['category_id']);
        $validated['slug'] = Str::slug($category->name.' '.$validated['name']);
        $validated['position'] = $validated['position'] ?? (Subcategory::where('category_id',$validated['category_id'])->max('position')+1);

        $subcategory = Subcategory::create($validated);
        return response()->json(['message'=>'Sous-catégorie créée','subcategory'=>$subcategory->load('category')],201);
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load(['category','products'=>fn($q)=>$q->active()->limit(10)]);
        $stats = [
            'total_products'=>$subcategory->products()->count(),
            'active_products'=>$subcategory->products()->active()->count(),
            'low_stock_products'=>$subcategory->products()->active()->lowStock()->count(),
            'total_stock_value'=>$subcategory->products()->active()->sum(fn($p)=>$p->stock*$p->cost_price),
        ];
        return response()->json(['subcategory'=>$subcategory,'stats'=>$stats]);
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $validated = $request->validate([
            'name'=>'sometimes|string|max:255',
            'code'=>'sometimes|string|max:20|unique:subcategories,code,'.$subcategory->id,
            'description'=>'nullable|string',
            'color'=>'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'position'=>'nullable|integer|min:0',
            'is_active'=>'nullable|boolean'
        ]);

        if(isset($validated['name'])){
            $validated['slug'] = Str::slug($subcategory->category->name.' '.$validated['name']);
        }

        $subcategory->update($validated);
        return response()->json(['message'=>'Sous-catégorie mise à jour','subcategory'=>$subcategory->fresh('category')]);
    }

    public function destroy(Subcategory $subcategory)
    {
        $count = $subcategory->products()->count();
        if($count>0) return response()->json(['message'=>'Impossible de supprimer, produits liés','products_count'=>$count],422);
        $subcategory->delete();
        return response()->json(['message'=>'Sous-catégorie supprimée']);
    }

    public function products(Subcategory $subcategory, Request $request)
    {
        $query = $subcategory->products()->with('category');
        if($request->get('active_only',true)) $query->active();
        if($request->has('search')) $query->search($request->search);
        return response()->json($query->paginate($request->get('per_page',20)));
    }
}
