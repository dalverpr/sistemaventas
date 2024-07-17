<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Models\Tenant\Company;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\DownloadTray;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Inventory\Exports\ProfitExport;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Inventory\Models\Warehouse;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Category;
use App\Models\Tenant\Catalogs\Profit;
use Hyn\Tenancy\Models\Hostname;
use App\Models\System\Client;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Jobs\ProcessInventoryReport;
use Modules\Inventory\Http\Resources\ReportInventoryCollection;

class ReportProfitController extends Controller
{
    public function index(){
        return view('tenant.reports.profit.index');        
    }
    
    public function tables(){
        //Obtener Warehouse
        return [
            'warehouses' => Warehouse::query()->select('id', 'description')->get(),
            'categories' => Category::query()->select('id', 'name')->get(),
            'brands' => Brand::query()->select('id', 'name')->get(),
            'establishments' => Establishment::query()->select('id', 'description')->get(),
            
        ];
    }

      public function records(Request $request)
    {
        $warehouse_id = $request->input('warehouse_id');
        $filter = $request->input('filter');
        $records = $this->getRecords($warehouse_id, $filter, $request);

        return new ReportInventoryCollection($records->paginate(50), $filter);
    }
     private function getRecords($warehouse_id = 0, $filter, $request)
    {
        //obtener saldos
        

        $query = ItemWarehouse::with(['warehouse', 'item' => function ($query) {
            $query->select('id', 'barcode', 'internal_id', 'description', 'name', 'category_id', 'brand_id', 'stock_min', 'sale_unit_price', 'purchase_unit_price', 'model', 'date_of_due', 'currency_type_id');
            $query->with(['category', 'brand', 'currency_type']);
            $query->without(['item_type', 'unit_type', 'warehouses', 'item_unit_types', 'tags']);
        }])
            ->whereHas('item', function ($q) {
                $q->where([
                    ['item_type_id', '01'],
                    ['unit_type_id', '!=', 'ZZ'],
                ])
                    ->whereNotIsSet();
            });


        if ($warehouse_id != 0) {
            $query->where('item_warehouse.warehouse_id', $warehouse_id);
        }

        if ($request->category_id) $query->whereItemCategory($request->category_id);

        if ($request->brand_id) $query->whereItemBrand($request->brand_id);

        return $query;
    }

    public function getProfits($establishment_id,$warehouse_id, $initialDate, $endDate){
        
        
        Profit::getProfits($establishment_id, $warehouse_id, $initialDate,$endDate);

        

        return [
            'profits' => Profit::query()->select(
                'fbpm',
                'purchases',
                'expenses',
                'income',
                'sales',
                'fbam'
            )->get(),
            'udma' =>  $initialDate,
            'udm' => $endDate
        ];
        
    }

    public  function data_last_month_day() { 
      $month = date('m');
      $year = date('Y');
      $day = date("d", mktime(0,0,0, $month+1, 0, $year));
 
      return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
    }
    public  function data_first_month_day($month) { 
      $year = date('Y');
      $day = date("d", mktime(0,0,0, $month, 0, $year));
 
      return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
    }

    public  function data_last_prev_month_day($month) { 
      $year = date('Y');
      $day = date("d", mktime(0,0,0, $month, -1, $year));
      return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
    }
    

    public function pdf(Request $request)
    {

        $company = Company::first();
        $establishment = Establishment::first();
        $profit = Profit::first();
        ini_set('max_execution_time', 0);

        if ($request->warehouse_id && $request->warehouse_id != 'all') {
            $records = ItemWarehouse::with(['item', 'item.brand'])->where('warehouse_id', $request->warehouse_id)->whereHas('item', function ($q) {
                $q->where([['item_type_id', '01'], ['unit_type_id', '!=', 'ZZ']]);
                $q->whereNotIsSet();
            })->latest()->get();
        } else {

            $records = ItemWarehouse::with(['item', 'item.brand'])->whereHas('item', function ($q) {
                $q->where([['item_type_id', '01'], ['unit_type_id', '!=', 'ZZ']]);
                $q->whereNotIsSet();
            })->latest()->get();
        }
        
        $ultimoDiaMes = $request->date_end;
       
        $ultimoDiaMesAnterior = $request->date_start;
        
        

        $pdf = PDF::loadView('inventory::reports.inventory.report_profit_pdf', compact("records", "company", "establishment","profit","ultimoDiaMes","ultimoDiaMesAnterior"));
        $pdf->setPaper('A4', 'landscape');
        $filename = 'Reporte_Profit' . date('YmdHis');

        return $pdf->download($filename . '.pdf');
    }

    public function excel(Request $request)
    {
        $company = Company::first();
        $establishment = Establishment::first();
        $profits = Profit::first();
       
        $ultimoDiaMes = $request->date_end;
       
        $ultimoDiaMesAnterior = $request->date_start;

        

        if ($request->warehouse_id && $request->warehouse_id != 'all') {
            $records = ItemWarehouse::with(['item', 'item.brand'])->where('warehouse_id', $request->warehouse_id)->whereHas('item', function ($q) {
                $q->where([['item_type_id', '01'], ['unit_type_id', '!=', 'ZZ']]);
                $q->whereNotIsSet();
            })->latest()->get();
        } else {
            $records = ItemWarehouse::with(['item', 'item.brand'])->whereHas('item', function ($q) {
                $q->where([['item_type_id', '01'], ['unit_type_id', '!=', 'ZZ']]);
                $q->whereNotIsSet();
            })->latest()->get();
        }

        //dd($records);

        return (new ProfitExport)
            ->records($records)
            ->company($company)
            ->establishment($establishment)
            ->profit($profits)
            ->udma($ultimoDiaMesAnterior)
            ->udm($ultimoDiaMes)
            ->download('ReporteProfit' . Carbon::now() . '.xlsx');
    }
    

 
}