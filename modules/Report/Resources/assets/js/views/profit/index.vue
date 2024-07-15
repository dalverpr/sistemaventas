<template>
    <div>
        <div class="card-header bg-info">
             <h3 class="my-0">Reporte de Beneficios</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <!--Periodo-->
                     <div class="row mt-2">
                            <div class="col-md-3">
                                <label class="control-label">Periodo</label>
                                <el-select v-model="form.period"
                                        @change="changePeriod">
                                    <el-option key="month"
                                            label="Por mes"
                                            value="month"></el-option>
                                
                                </el-select>
                            </div>"
                            <template v-if="form.period === 'month'">
                                <div class="col-md-3">
                                    <label class="control-label">Mes de</label>
                                    <el-date-picker v-model="form.month_start"
                                                    :clearable="false"
                                                    format="MM/yyyy"
                                                    type="month"
                                                    value-format="yyyy-MM"
                                                    @change="changeDisabledMonths"></el-date-picker>
                                </div>
                            </template>               
                    </div>              
                    <!--Establicimiento-->
                     <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="control-label">Establecimiento</label>
                            <el-select v-model="form.establishment_id"
                                       placeholder="Seleccionar establecimiento"
                                       >
                                <el-option key="all"
                                           label="Todos"
                                           value="all"></el-option>
                                <el-option v-for="opt in establishments"
                                           :key="opt.id"
                                           :label="opt.description"
                                           :value="opt.id">
                                </el-option>
                            </el-select>
                        </div>    
                    </div>
                    
                    <!--Almacen-->
                     <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="control-label">Almacen</label>
                            <el-select v-model="form.warehouse_id"
                                       placeholder="Seleccionar almacén"
                                       >
                                <el-option key="all"
                                           label="Todos"
                                           value="all"></el-option>
                                <el-option v-for="opt in warehouses"
                                           :key="opt.id"
                                           :label="opt.description"
                                           :value="opt.id">
                                </el-option>
                            </el-select>
                        </div>    
                    </div>
                     <!--Buscar-->
                    <div class="col-md-12 col-12">&nbsp;</div>
                    <div class="col-lg-7 col-md-7 col-md-7 col-sm-12"
                         style="margin-top:29px">
                        <el-button :loading="loading_submit"
                                   class="submit"
                                   icon="el-icon-search"
                                   type="primary"
                                   @click.prevent="getRecordsByFilter">Buscar
                        </el-button>

                        <template v-if="records.length>0">
                            <el-button class="submit"
                                       type="success"
                                       @click.prevent="clickExport('excel')"><i
                                class="fa fa-file-excel"></i> Exportar Excel
                            </el-button>
                            <el-button class="submit"
                                       icon="el-icon-tickets"
                                       type="danger"
                                       @click.prevent="clickExport('pdf')">Exportar PDF
                            </el-button>
                      
                        </template>
                    </div>
                    <!--Profit-->
                    <div class="col-md-12">
                        <div class="table-responsive">
                                <table class="table table-striped table-responsive-xl table-bordered table-hover">
                                <thead class="">
                                    <tr>
                                        <th>#</th>
                                        <th>Saldo Final - {{ this.ultimoDiaMesAnterior }}</th>
                                        <th>Compras - {{ this.ultimoDiaMesActual }}</th>
                                        <th>Gastos - {{ this.ultimoDiaMesActual }}</th>
                                        <th>Ingresos - {{ this.ultimoDiaMesActual }}</th>
                                        <th>Ventas - {{ this.ultimoDiaMesActual }}</th>
                                        <th>Costo - {{ this.ultimoDiaMesActual }}</th>
                                        
                                        <th class="text-right">Beneficio
                                            <el-tooltip
                                                class="item"
                                                content="Sa"
                                                effect="dark"
                                                placement="top-start"
                                            >
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in profits" :key="index">
                                        <td>{{ index + 1 }}</td>
                                        <td class="text-right">{{ row.fbpm}}</td>
                                        <td class="text-right">{{ row.purchases.toFixed(2) }}</td>
                                        <td class="text-right">{{ row.expenses.toFixed(2) }}</td>
                                        <td class="text-right">{{ row.income.toFixed(2) }}</td>
                                        <td class="text-right">{{ row.sales }}</td>
                                        <td class="text-right">{{ Math.abs(row.fbam).toFixed(2)}}</td>
                                        <td class="text-right">{{ Math.abs(Math.abs(row.sales).toFixed(2) - Math.abs(row.fbam).toFixed(2)).toFixed(2)}}</td>
                                    </tr>
                                </tbody>
                                </table>
                        </div>
                    </div>
                    <!--Inventario-->
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-responsive-xl table-bordered table-hover">
                                <thead class="">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th v-if="filters.description.visible">Descripción</th>
                                    <th v-if="filters.model.visible">Modelo</th>
                                    <th>Categoria</th>
                                    <th class="text-right">Stock mínimo</th>
                                    <th class="text-right">Stock actual</th>
                                    <th class="text-right">Costo</th>
                                    <th class="text-right">Costo total - Almacén
                                        <el-tooltip
                                            class="item"
                                            content="Costo * stock"
                                            effect="dark"
                                            placement="top-start"
                                        >
                                            <i class="fa fa-info-circle"></i>
                                        </el-tooltip>
                                    </th>
                                    <th class="text-right">Precio de venta</th>
                                    <th>Costo de venta
                                        <el-tooltip
                                            class="item"
                                            content="Precio de venta * stock"
                                            effect="dark"
                                            placement="top-start"
                                        >
                                            <i class="fa fa-info-circle"></i>
                                        </el-tooltip>
                                    </th>
                                    <th>Beneficio
                                        <el-tooltip
                                            class="item"
                                            content="Precio de venta - Costo * Cantidad"
                                            effect="dark"
                                            placement="top-start"
                                        >
                                            <i class="fa fa-info-circle"></i>
                                        </el-tooltip>
                                    </th>
                                    <th>Marca</th>
                                    <th class="text-center">F. vencimiento</th>
                                    <th>Almacén</th>
                                    <th>Cód. Barras</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in records"
                                    :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ row.name }}</td>
                                    <td v-if="filters.description.visible">{{ row.description }}</td>
                                    <td v-if="filters.model.visible">{{ row.model }}</td>
                                    <td>{{ row.item_category_name }}</td>
                                    <td class="text-right">{{ row.stock_min }}</td>
                                    <td class="text-right">{{ row.stock }}</td>
                                    <td class="text-right">{{ row.purchase_unit_price }}</td>
                                    <td class="text-right">{{ Math.abs(row.purchase_unit_price * row.stock).toFixed(2) }}</td>
                                    <td class="text-right">{{ row.sale_unit_price }}</td>
                                    <td class="text-right">{{ Math.abs(row.sale_unit_price * row.stock).toFixed(2) }}</td>
                                    <td class="text-right">{{ Math.abs(Math.abs(row.sale_unit_price * row.stock).toFixed(2) - Math.abs(row.purchase_unit_price * row.stock).toFixed(2)).toFixed(2) }}e</td>
                                <!---<td class="text-right">{{ Math.abs(row.profit * row.stock).toFixed(3) }}</td>-->
                                    <td>{{ row.brand_name }}</td>
                                    <td class="text-center">{{ row.date_of_due }}</td>
                                    <td>{{ row.warehouse_name }}</td>
                                    <td>{{ row.barcode }}</td>
                                </tr>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="celda" colspan="6"></td>
                                    <!--
                                    <td class="celda">RD$ {{ totals.sale_unit_price }}</td>
                                    <td class="celda">RD$ {{ totals.purchase_unit_price }}</td>
                                    <td class="celda">RD$ {{ totals.purchase_unit_price }}</td>
                                    -->
                                    <td class="celda text-right">RD$ {{ totals.cost_warehouse }}</td>
                                    <td class="celda" colspan="2"></td>
                                    <td class="celda text-right">RD$ {{ total_profit }}</td>
                                </tr>
                                </tfoot>
                            </table>
                            <div>
                                <el-pagination
                                        @current-change="getRecords"
                                        layout="total, prev, pager, next"
                                        :total="pagination.total"
                                        :current-page.sync="pagination.current_page"
                                        :page-size="pagination.per_page">
                                </el-pagination>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        Total {{ records.length }}
                    </div>
                       
                        
                    
                </div>
        </div>
    </div>
    
</template>
<script>
import moment from "moment";
import queryString from "query-string";
export default {
    props: [],
    data() {
        return {
            // loading_submit: false,
            // showDialogLots: false,
            // showDialogLotsOutput: false,
            // titleDialog: null,
            filter_range:false,
            showDialog: false,
            loading_submit: false,
            resource: 'profit',
            total_profit: 0,
            total_all_profit: 0,
            total_cost_warehouse: 0,
            costo:0,
            loading: false,
            loadingPdf: false,
            loadingXlsx: false,
            resource: 'profit',
            errors: {},
            form: {},
            warehouses: [],
            categories: [],
            establishments: [],
            brands: [],
            filters: [],
            records: [],
            profits: [],
            ultimoDiaMesAnterior: null,
            ultimoDiaMesActual: null,
            
            totals: {
                purchase_unit_price: 0,
                sale_unit_price: 0,
                cost_warehouse:0
            },
            pickerOptionsDates: {
                disabledDate: (time) => {
                    time = moment(time).format('YYYY-MM-DD')
                    return this.form.date_start > time
                }
            },
            pagination: {},
        }
    },
    created() {
        this.initTables();
        this.initForm();
        this.firstAndLastDay();
        this.filters = {
            description: {
                title: 'Descripción',
                visible: false
            },
            categories: {
                title: 'Categorias',
                visible: false
            },
            model: {
                title: 'Modelo',
                visible: false
            },
            brand: {
                title: 'Marcas',
                visible: false
            },
            active: {
                title: 'Estado',
                visible: false
            },
            range: {
                title: 'Rango de fechas',
                visible: false
            },
        }
    },
    methods: {
        getLastDayOfMonth(year, month) {
            let date = new Date(year, month + 1, 0);
            return date.getDate();  
        },
        firstAndLastDay(){
            this.ultimoDiaMesAnterior = this.getLastDayOfMonth(new Date().getFullYear(), new Date().getMonth() - 2)
            this.ultimoDiaMesActual = this.getLastDayOfMonth(new Date().getFullYear(), new Date().getMonth())
        },
        changeDisabledMonths() {
            if (this.form.month_end < this.form.month_start) {
                this.form.month_end = this.form.month_start
            }
        },
        changeDisabledDates() {
            if (this.form.date_end < this.form.date_start) {
                this.form.date_end = this.form.date_start
            }
            this.getRecords();
        },
        initTotals() {

            this.totals = {
                purchase_unit_price: 0,
                sale_unit_price: 0,
                cost_warehouse: 0
            }

        },
        initForm() {
            this.form = {
                'warehouse_id': null,
                'filter': '01',
                'category_id': null,
                'brand_id': null,
                active: null
            }
        },
        calculeTotalProfit() {

            this.total_profit = 0;
            this.total_all_profit = 0;
            this.total_purchase_unit_price = 0;
            this.total_sale_unit_price = 0;


            if (this.records.length > 0) {

                let el = this;
                this.records.forEach(function (a, b) {

                    el.total_profit += parseFloat(a.stock * a.sale_unit_price).toFixed(2) - parseFloat(a.stock * a.purchase_unit_price).toFixed(2);
                    el.total_all_profit += Math.abs(a.profit * a.stock);

                    el.totals.purchase_unit_price += parseFloat(a.purchase_unit_price)
                    el.totals.sale_unit_price += parseFloat(a.sale_unit_price)

                    el.totals.cost_warehouse += parseFloat(a.stock * a.purchase_unit_price);

                })

            }

            this.total_profit = this.total_profit.toFixed(3)
            this.total_all_profit = this.total_all_profit.toFixed(3)

            this.totals.purchase_unit_price = this.totals.purchase_unit_price.toFixed(3)
            this.totals.sale_unit_price = this.totals.sale_unit_price.toFixed(3)

            this.totals.cost_warehouse = this.totals.cost_warehouse.toFixed(3)


        },
        initTables() {
            this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.warehouses = response.data.warehouses;
                    this.brands = response.data.brands;
                    this.categories = response.data.categories;
                    this.establishments = response.data.establishments;
                });
        },
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page,
                limit: this.limit,
                ...this.form
            });
        },
        async getRecordsByFilter() {
            this.loading_submit = await true
            await this.getRecords()
            this.loading_submit = await false

        },
        async getRecords() {

            if (_.isNull(this.form.warehouse_id)) {
                this.$message.error('Seleccionar un almacén ');
                return false;
            }

            if (_.isNull(this.form.establishment_id)) {
                this.$message.error('Seleccionar un establecimiento ');
                return false;
            }

            this.loading = true

            this.records = [];
            this.total_profit = 0;
            this.total_all_profit = 0;
            this.initTotals()
            let range = this.filters.range.visible
            if (range !== true) {
                delete this.form.date_start
                delete this.form.date_end
            }

            await this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`)
                .then(response => {
                    this.records = response.data.data;
                    //console.log(this.records);
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                    this.calculeTotalProfit()
                })

            await this.$http.get(`/${this.resource}/getProfits/${this.form.establishment_id}/${this.form.warehouse_id}/${new Date().getMonth() + 1}/${new Date().getFullYear()}`)
                .then(response => {
                    this.profits = response.data.profits;
                    this.ultimoDiaMesActual = response.data.udm;
                    this.ultimoDiaMesAnterior = response.data.udma;
                })
                
            this.loading = false;
        },
        changeWarehouse() {
            this.getRecords();
        },
        changeEstablishment() {
            this.getRecords();
        },
        changeFilter() {
            this.getRecords();
        },
        changePeriod() {
            if (this.form.period === 'month') {
                this.form.month_start = moment().format('YYYY-MM');
                this.form.month_end = moment().format('YYYY-MM');
            }
            if (this.form.period === 'between_months') {
                this.form.month_start = moment().startOf('year').format('YYYY-MM'); //'2019-01';
                this.form.month_end = moment().endOf('year').format('YYYY-MM');

            }
            if (this.form.period === 'date') {
                this.form.date_start = moment().format('YYYY-MM-DD');
                this.form.date_end = moment().format('YYYY-MM-DD');
            }
            if (this.form.period === 'between_dates') {
                this.form.date_start = moment().startOf('month').format('YYYY-MM-DD');
                this.form.date_end = moment().endOf('month').format('YYYY-MM-DD');
            }
            // this.loadAll();
        },
        async clickExport(type) {
            let query = queryString.stringify({
                    ...this.form
                });
                window.open(`/${this.resource}/${type}/?${query}`, '_blank');

        }
    }
} 
</script>