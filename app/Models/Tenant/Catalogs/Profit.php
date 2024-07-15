<?php

namespace App\Models\Tenant\Catalogs;

use Illuminate\Support\Facades\DB;
use App\Models\System\User;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class Profit extends ModelCatalog
{
    use UsesTenantConnection;

    protected $table = 'profits';

    protected $fillable = [
        'fbpm',
        'purchases',
        'expenses',
        'income',
        'sales',
        'fbam'
    ];

    static function getProfits($establishment_id,$warehouse_id,$month,$year){
         DB::connection('tenant')
         ->statement('call spGetProfit (?,?,?,?)',[$establishment_id,$warehouse_id,$month,$year]);
    } 
   
}