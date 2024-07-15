<?php

namespace Modules\Inventory\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProfitExport implements  FromView, ShouldAutoSize
{
    use Exportable;

    protected $records;
    protected $company;
    protected $establishment;

    protected $profit;

    protected $udma;
    protected $udm;
    
    protected $format;

    public function records($records) {
        $this->records = $records;
       
        return $this;
    }

    public function company($company) {
        $this->company = $company;

        return $this;
    }

    public function establishment($establishment) {
        $this->establishment = $establishment;

        return $this;
    }

    public function format($format) {
        $this->format = $format;

        return $this;
    }

    public function profit($profit) {
        $this->profit = $profit;
        
        return $this;
    }

    public function udma($udma) {
        $this->udma = $udma;
        
        return $this;
    }
    public function udm($udm) {
        $this->udm = $udm;
        
        return $this;
    }


    public function view(): View {

        return view('inventory::reports.inventory.report_profit_excel', [
            'records' => $this->records,
            'company' => $this->company,
            'establishment' => $this->establishment,
            'profit' => $this->profit,
            'udma' => $this->udma,
            'udm' => $this->udm,
            'format' => $this->format,
        ]);
    }
}