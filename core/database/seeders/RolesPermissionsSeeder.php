
<?php

use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

$adminRole = Role::create(['name'=>'admin','label'=>'Administrateur']);
$salesRole = Role::create(['name'=>'seller','label'=>'Vendeur']);

$permissions = [
    'create_sale','view_sale','edit_sale','delete_sale',
    'create_product','view_product','edit_product','delete_product'
];

foreach ($permissions as $perm) {
    $p = Permission::create(['name'=>$perm]);
    $adminRole->permissions()->attach($p);
}

// Optionnel : affecter certaines permissions au vendeur
$salesRole->permissions()->attach(Permission::whereIn('name',['create_sale','view_sale'])->get());
