<template> 
    <div>
        <el-select 
            v-model="item_id"
            :loading="loading_search"
            :remote-method="searchRemoteItems"
            remote
            filterable
            placeholder="Buscar/Agregar producto" 
            @change="changeItem"
            :disabled="!is_mounted"
        >
            <el-option
                v-for="row in items"
                :key="row.id"
                :label="itemOptionDescriptionView(row)"
                :value="row.id"
                >

                <div>
                    <div class="block f-left">
                        <img 
                            class="custom-image pt-2" 
                            :src="row.image_url"
                            :alt="row.description"
                        >
                    </div>

                    <span class="full-description pt-2 ml-4">
                        {{ itemOptionDescriptionView(row) }}
                    </span>

                    <div class="f-right">
                        <span class="f-right custom-stock pt-1 pb-1 ml-3">Stock: {{ parseStock(row.stock) }}</span>

                        <span class="f-right custom-price pt-1 pb-1">{{ row.currency_type_symbol }} {{ row.sale_unit_price }}</span>
                        <br>
                        <div class="f-right">
                            <button type="button" class="el-button el-button--default is-plain el-button--mini f-right mt-1" @click.stop.prevent="clickStock(row)">
                                <span>Stock</span>
                            </button>

                            <button v-if="showDetailButton" type="button" class="el-button el-button--default is-plain el-button--mini f-right m-1" @click.stop.prevent="clickDetail(row.id)">
                                <span>Detalle</span>
                            </button>
                        </div>
                    </div>
                </div>

            </el-option>
        </el-select>
        
        <warehouses-stock
            :showDialog.sync="showDialogStock"
            :warehouses="warehouses"
            :itemName="itemName">
        </warehouses-stock>
        
        <!-- <item-detail-form 
            :recordId="dialogItemId"
            :showDialog.sync="showDialogItem"
            :onlyShowAllDetails="showDetailButton"
        >
        </item-detail-form> -->

    </div>
</template>

<script>

    import WarehousesStock from './partials/WarehousesStock.vue'
    import { ItemOptionDescription } from '@helpers/modal_item'
    import ItemDetailForm from '@views/items/form.vue'

    export default {
        props: {
            resource: {
                type: String,
                required: true,
            },
            showDetailButton: {
                type: Boolean,
                required: false,
                default: false,
            },
        },
        components: {
            WarehousesStock,
            // ItemDetailForm
        },
        data() {
            return {
                item_id: null,
                loading_search: false,
                all_items: [],
                items: [],
                is_mounted: false,
                showDialogStock: false,
                warehouses: [],
                showDialogItem: false,
                itemName: null,
                dialogItemId: null,
            }
        },
        async created()
        { 
            await this.initialItems() 
        }, 
        mounted()
        {
            this.is_mounted = true
        },
        methods: 
        {
            parseStock(stock)
            {
                return parseFloat(stock)
            },
            cleanValue()
            {
                this.item_id = null
            },
            clickDetail(id)
            {
                // this.dialogItemId = id
                // this.showDialogItem = true
                window.open(`/items/show-item-detail/${id}`)

            },
            clickStock(row)
            {
                this.warehouses = row.warehouses
                this.itemName = row.full_description
                this.showDialogStock = true
            },
            changeItem()
            {
                // console.log("changeItem")
                const item = { ..._.find(this.items, { id : this.item_id}) }
                this.$emit('changeItem', item)
            },
            async initialItems() 
            {
                await this.$http.get(`/${this.resource}/table/items`).then((response) => {
                    this.all_items = response.data
                    this.filterItems()
                })
            },
            itemOptionDescriptionView(row)
            {
                return ItemOptionDescription(row)
            },
            async searchRemoteItems(input) 
            {
                if (input.length > 2) 
                {
                    this.loading_search = true

                    const params = {
                        input: input,
                        search_by_barcode: 0,
                        search_item_by_barcode_presentation: 0,
                        search_factory_code_items: 0
                    }

                    await this.$http.get(`/${this.resource}/search-items`, { params })
                                    .then(response => {
                                        this.items = response.data.items
                                        this.loading_search = false
                                        if (this.items.length == 0) this.filterItems()
                                    })

                    return
                } 

                await this.filterItems()

            },
            filterItems() 
            {
                this.items = this.all_items
            },
        }
    }
</script>

<style scoped>
    .f-left {
        float: left
    }

    .f-right {
        float: right
    }

    .custom-price {
        color: #8492a6; 
        font-size: 17px
    }

    .custom-stock {
        color: red; 
        font-size: 17px
    }


    .custom-image {
        height: 74px !important;
        width: 85px;
    }

    .full-description {
        float: left; 
        font-size: 15px;
        max-width: 80%;
    }

    .el-select-dropdown__item{
        height: 85px !important
    }
</style>