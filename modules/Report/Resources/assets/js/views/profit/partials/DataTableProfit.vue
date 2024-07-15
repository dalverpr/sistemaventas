<template>
    <div>
        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-12 ">
                <!--Periodos-->
                <div class="row">
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
                <!--Almacenes-->
                 <div class="row">
                    <div class="col-lg-4 col-md-4">
                        <div class="form-group">
                            <label class="control-label">
                                Almacén
                            </label>

                            <el-select
                                v-model="form.warehourse_id"
                                :loading="loading_search"
                                :remote-method="searchWarehouse"
                                clearable
                                filterable
                                placeholder="Código interno o nombre"
                                popper-class="el-select-customers"
                                remote
                                >
                                <el-option v-for="option in all_warehouses"
                                           :key="option.id"
                                           :label="option.description"
                                           :value="option.id"></el-option>
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
                                       @click.prevent="clickDownload('excel')"><i
                                class="fa fa-file-excel"></i> Exportal Excel
                            </el-button>
                            <el-button class="submit"
                                       icon="el-icon-tickets"
                                       type="danger"
                                       @click.prevent="clickDownload('pdf')">Exportar PDF
                            </el-button>
                      
                        </template>
                    </div>
                </div>
                <div class="row mt-1 mb-4">

                </div>
            </div>


            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <slot name="heading"></slot>
                        </thead>
                        <tbody>
                        <slot v-for="(row, index) in records"
                              :index="customIndex(index)"
                              :row="row"></slot>
                        </tbody>
                    </table>
                    <div>
                        <el-pagination
                            :current-page.sync="pagination.current_page"
                            :page-size="pagination.per_page"
                            :total="pagination.total"
                            layout="total, prev, pager, next"
                            @current-change="getRecords">
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>
        <totals-by-item-form
            :parameters="getQueryParameters()"
            :resource="'reports/guides'"
            :showDialog.sync="showDialog"
        ></totals-by-item-form>
    </div>
</template>
<style>
.font-custom {
    font-size: 15px !important
}
</style>
<script>

import moment from 'moment'
import queryString from 'query-string'
import TotalsByItemForm from '../../../components/partials/totals_by_item.vue'

export default {
    components: {TotalsByItemForm},
  
    data() {
        return {
            filter_range:false,
            showDialog: false,
            loading_submit: false,
            resource: 'profit',
            items: [],
            all_items: [],
            all_warehouses: [],
            all_categories: [],
            all_brands: [],
            loading_search: false,
            columns: [],
            records: [],
            headers: headers_token,
            document_types: [],
            pagination: {},
            search: {},
            totals: {},
            warehouses: [],
            web_platforms: [],
            customers: {},
            users: {},
            form: {
                min: 1,
                max: 2,
            },
            pickerOptionsDates: {
                disabledDate: (time) => {
                    time = moment(time).format('YYYY-MM-DD')
                    return this.form.date_start > time
                }
            },
            pickerOptionsMonths: {
                disabledDate: (time) => {
                    time = moment(time).format('YYYY-MM')
                    return this.form.month_start > time
                }
            },
        }
    },
    computed: {},
    created() {
        this.initForm()
        this.initTotals()
        this.$eventHub.$on('reloadData', () => {
            this.getRecords()
        })
    },
    async mounted() {

        await this.$http.get(`/${this.resource}/tables`)
            .then(response => {
                this.all_warehouses = response.data.warehouses
                this.allcategories = response.data.categories
                this.all_brands = response.data.brands                
            });


        await this.filterItems()

    },
    methods: {

        clickTotalByItem() {
            this.showDialog = true
        },
        searchRemoteItems(input) {
            if (input.length > 0) {
                this.loading_search = true
                let parameters = `input=${input}`
                this.$http.get(`/reports/data-table/items/?${parameters}`)
                    .then(response => {
                        this.items = response.data.items
                        this.loading_search = false
                        if (this.items.length == 0) {
                            this.filterItems()
                        }
                    })
            } else {
                this.filterItems()
            }

        },
        filterItems() {
            this.items = this.all_items
        },
        clickDownload(type) {
            let query = queryString.stringify({
                ...this.form
            });
            window.open(`/${this.resource}/${type}/?${query}`, '_blank');
        },
        initForm() {

            this.form = {
                establishment_id: null,
                customer_id: null,
                item_id: null,
                user_id: null,
                document_type_id: null,
                period: 'month',
                date_start: moment().format('YYYY-MM-DD'),
                date_end: moment().format('YYYY-MM-DD'),
                month_start: moment().format('YYYY-MM'),
                month_end: moment().format('YYYY-MM'),
            }

        },
        initTotals() {

            this.totals = {
                acum_total_taxed: 0,
                acum_total_igv: 0,
                acum_total: 0,
                acum_total_exonerated: 0,
                acum_total_unaffected: 0,
                acum_total_free: 0,

                acum_total_taxed_usd: 0,
                acum_total_igv_usd: 0,
                acum_total_usd: 0,
            }
        },
        customIndex(index) {
            return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
        },
        async getRecordsByFilter() {
            this.loading_submit = await true
            await this.getRecords()
            this.loading_submit = await false

        },
        getRecords() {
            let url = `/${this.resource}/records?${this.getQueryParameters()}`;
            return this.$http.get(url).then((response) => {
                this.records = response.data.data
                this.pagination = response.data.meta
                this.pagination.per_page = parseInt(response.data.meta.per_page)
                this.loading_submit = false
            });


        },
        getQueryParameters() {
            let param = {
                page: this.pagination.current_page,
                limit: this.limit,
                ...this.form
            };
            if(this.filter_range === false) {
                delete param.min;
                delete param.max;
            }
            return queryString.stringify(param)
        },

        changeDisabledDates() {
            if (this.form.date_end < this.form.date_start) {
                this.form.date_end = this.form.date_start
            }
            // this.loadAll();
        },
        changeDisabledMonths() {
            if (this.form.month_end < this.form.month_start) {
                this.form.month_end = this.form.month_start
            }
            // this.loadAll();
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
    }
}
</script>
