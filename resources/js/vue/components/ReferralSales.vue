<template>
    <v-container fluid>
        <v-row>
            <v-col
                cols="12"
                class="pa-0"
            >
                <v-card
                    class="rounded-0"
                >
                    <v-card-title class="display-sm-bold px-6 pt-4 pb-4">
                        Overview
                    </v-card-title>
                    <v-card-actions class="text-sm px-6 d-block d-md-flex d-lg-flex d-xl-flex">
                        <v-select
                            dense
                            outlined
                            hide-details
                            single-line
                            v-model="selectedPreDate"
                            :items="filterPreDates"
                            label="Pre-defined Range"
                            @change="reGet(1)"
                            class="text-sm sales-card-select"
                        ></v-select>
                        <span class="px-3" v-if="!isMobile">compared to</span>
                        <v-select
                            v-if="!isMobile"
                            dense
                            outlined
                            hide-details
                            single-line
                            disabled
                            :value="comparePredefined[0]"
                            :items="comparePredefined"
                            label="Compared To"
                            class="text-sm sales-card-select"
                        ></v-select>
                    </v-card-actions>

                    <v-container fluid class="overview-container">
                        <v-row>
                            <v-col
                                cols="12"
                                md="4" lg="4" xl="4"
                                class="py-4 pt-md-7 pt-lg-7 pt-xl-7 px-6 pr-md-2 pr-lg-2 pr-xl-2"
                            >
                                <v-tabs
                                    show-arrows
                                    v-model="overviewTab"
                                    @change="reGet(0)"
                                    v-if="isMobile"
                                >
                                    <v-tab
                                        v-for="(item, index) in tabs"
                                        :key="index"
                                    >
                                        <span>{{ item.title }}</span>
                                    </v-tab>
                                </v-tabs>

                                <v-tabs
                                    vertical
                                    grow
                                    background-color="white"
                                    active-class="title-color"
                                    slider-size="3"
                                    class="overview-tabs"
                                    v-model="overviewTab"
                                    @change="reGet(0)"
                                    v-if="!isMobile"
                                >
                                    <v-tab
                                        class="overview-tab py-4 px-6"
                                        v-for="(item, index) in tabs"
                                        :key="index"
                                    >
                                        <v-skeleton-loader
                                            v-if="initialloading"
                                            width="100%"
                                            height="120px"
                                            type="image"
                                        ></v-skeleton-loader>

                                        <div v-else class="d-flex flex-column fill-height">
                                            <div
                                                class="text-sm-bold"
                                            >
                                                {{ item.title }}
                                            </div>
                                            <div class="d-flex flex-column flex-grow-1 justify-end">
                                                <div
                                                    class="display-lg-bold mt-3"
                                                >
                                                    <span v-if="item.title == 'Sales'">RM </span>{{ item.number }}
                                                    <span class="text-xxs grey--text ml-1">( <span v-if="item.title == 'Sales'">RM </span>{{ item.previousNumber }} )</span>
                                                </div>
                                                <div class="text-xs d-flex align-center" v-bind:class="[ item.status == 1 ? 'green--text' : 'red--text' ]" v-if="item.status != 0">
                                                    <v-icon size="16" class="green--text" v-if="item.status == 1">mdi-arrow-up</v-icon>
                                                    <v-icon size="16" class="red--text" v-if="item.status == 2">mdi-arrow-down</v-icon>
                                                    <span>{{ item.percentage }}</span>
                                                </div>
                                                <div
                                                    class="text-xs body-color"
                                                    v-if="item.caption != null"
                                                >
                                                    {{ item.caption }}
                                                </div>
                                            </div>
                                        </div>
                                    </v-tab>

                                    <v-overlay
                                        absolute
                                        color="white"
                                        opacity="0.36"
                                        :value="summaryLoading"
                                    >
                                        <v-progress-circular
                                            indeterminate
                                            size="58"
                                            width="5"
                                            color="rgba(0, 0, 0, 0.2)"
                                        ></v-progress-circular>
                                    </v-overlay>
                                </v-tabs>
                            </v-col>

                            <v-col
                                cols="12"
                                md="8" lg="8" xl="8"
                                class="py-4 px-6
                                    pt-md-7 pt-lg-7 pt-xl-7
                                    pl-md-2 pl-lg-2 pl-xl-2
                                    pr-md-0 pr-lg-0 pr-xl-0"
                            >
                                <v-skeleton-loader
                                    v-if="initialloading"
                                    width="100%"
                                    height="326px"
                                    type="image"
                                ></v-skeleton-loader>

                                <v-card
                                    v-if="!initialloading"
                                    class="px-6 py-4 rounded-0"
                                    v-bind:class="{ 'v-sheet--outlined': !isMobile }"
                                >
                                    <v-card-title class="text-md-bold pt-0 pb-md-5 pb-lg-5 pb-xl-5" v-bind:class="{ 'justify-center' : isMobile}">{{ lineTitle }}</v-card-title>
                                    <div class="pb-10 text-center" v-if="isMobile">
                                        <span class="text-sm-bold text--secondary pr-2">Current: {{ tabs[overviewTab].number }}</span>
                                        <span class="text-sm-bold text--secondary">Past: {{ tabs[overviewTab].previousNumber }}</span>
                                    </div>
                                    <div class="legendBox d-flex justify-center mb-5">
                                        <v-btn
                                            text
                                            small
                                            color="primary"
                                            @click="toggleDatasetVisibility(0)"
                                        >
                                            <v-divider v-if="slashCurrent"></v-divider>
                                            <v-icon
                                                left
                                                x-small
                                            >
                                                mdi-checkbox-blank-circle
                                            </v-icon>
                                            Current
                                        </v-btn>
                                        <v-btn
                                            text
                                            small
                                            color="secondary"
                                            @click="toggleDatasetVisibility(1)"
                                        >
                                            <v-divider v-if="slashPast"></v-divider>
                                            <v-icon
                                                left
                                                x-small
                                            >
                                                mdi-checkbox-blank-circle
                                            </v-icon>
                                            Past
                                        </v-btn>
                                    </div>
                                    <line-chart
                                        :height="240"
                                        :chart-data="lineData"
                                        :manual-alert="alert"
                                    ></line-chart>

                                    <v-overlay
                                        absolute
                                        color="white"
                                        opacity="0.36"
                                        :value="chartLoading"
                                    >
                                        <v-progress-circular
                                            indeterminate
                                            size="58"
                                            width="5"
                                            color="rgba(0, 0, 0, 0.2)"
                                        ></v-progress-circular>
                                    </v-overlay>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" class="pa-0 pt-2">
                <v-card
                    class="rounded-0"
                >
                    <v-card-title class="display-sm-bold px-6 pt-4 pb-4">
                        Order List
                    </v-card-title>
                    <v-card-actions class="text-sm px-6 d-block d-md-flex d-lg-flex d-xl-flex">
                        <v-select
                            dense
                            outlined
                            hide-details
                            single-line
                            v-model="selectedPreDate"
                            item-color="primary"
                            :items="filterPreDates"
                            label="Pre-defined Range"
                            @change="reGet(1)"
                            class="text-sm mb-6 sales-card-select"
                        ></v-select>

                        <v-spacer></v-spacer>

                        <v-text-field
                            v-model.trim="search"
                            append-icon="mdi-magnify"
                            label="Search"
                            single-line
                            hide-details
                            class="pa-2 mt-0"
                            @keyup="getOrdersRecordsTable"
                        ></v-text-field>
                    </v-card-actions>
                    <v-container fluid>
                        <v-row class="pb-4 px-6">
                            <v-col
                                cols="12"
                                class="pa-0"
                            >
                                <v-skeleton-loader
                                    v-if="initialloading"
                                    width="100%"
                                    height="326px"
                                    type="image"
                                ></v-skeleton-loader>

                                <v-card
                                    v-if="!initialloading"
                                    outlined
                                    class="pb-4 rounded-0"
                                >
                                    <v-data-table
                                        :headers="headers"
                                        :items="orderRecords"
                                        :options.sync="options"
                                        :server-items-length="totalOrderRecords"
                                        no-data-text="No Data."
                                        :search="search"
                                        no-results-text="No Result."
                                    >
                                        <template  v-slot:[`item.sales`]="{ item }">
                                            <span>RM {{ parseFloat(item.sales).toFixed(2) }}</span>
                                        </template>
                                    </v-data-table>

                                    <v-overlay
                                        absolute
                                        color="white"
                                        opacity="0.36"
                                        :value="ordersListLoading"
                                    >
                                        <v-progress-circular
                                            indeterminate
                                            size="58"
                                            width="5"
                                            color="rgba(0, 0, 0, 0.2)"
                                        ></v-progress-circular>
                                    </v-overlay>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import LineChart from '../../charts/LineChart.js';
export default {
    components: {
        LineChart
    },
    data: () => {
        return {
            overviewTab: 0,
            tabs: [
                {
                    title: 'Sales',
                    number: 0,
                    previousNumber: 0,
                    status: 1,
                    percentage: '0%',
                    caption: 'from previous period'
                },
                {
                    title: 'Orders',
                    number: 0,
                    previousNumber: 0,
                    status: 1,
                    percentage: '0%',
                    caption: 'from previous period'
                }
            ],
            lineData: null,
            lineTitle: 'Sales Growth by 7 days',
            selectedPreDate: 'This month',
            filterPreDates: [
                'Today',
                'Last 7 days',
                'This month',
                'Last 3 months',
                'Last 6 months',
                'Last 12 months'
            ],
            comparePredefined: [
                'Last Period'
            ],
            option: null,
            slashCurrent: false,
            slashPast: false,
            alert: false,
            headers: [
                {
                    text: 'Order #',
                    align: 'start',
                    value: 'order_id',
                },
                {
                    text: 'Date',
                    align: 'start',
                    value: 'date_created',
                },
                {
                    text: 'Customer Name',
                    align: 'start',
                    value: 'customer_name',
                },
                {
                    text: 'Customer Phone',
                    align: 'start',
                    value: 'customer_phone',
                },
                {
                    text: 'Sales',
                    align: 'start',
                    value: 'sales',
                },
                {
                    text: 'Status',
                    align: 'start',
                    value: 'status'
                }
            ],
            orderRecords: [],
            options: {},
            totalOrderRecords: 0,
            search: '',
            initialloading: false,
            summaryLoading: true,
            chartLoading: true,
            ordersListLoading: true
        }
    },

    watch: {
        options: {
            handler () {
                this.getOrdersRecordsTable();
            },
            deep: true,
        },
    },

    mounted() {
        this.reGet(0);
    },

    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        }
    },

    methods: {
        reGet(i) {
            this.calculatePredefinedDates();
            this.getSummary();

            if (this.overviewTab == 0) {
                this.getSalesChart();
            }
            else {
                this.getOrdersChart();
            }

            // i = 0, exclude table
            // i = 1, include table
            if(i == 1) {
                this.getOrdersRecordsTable();
            }
        },

        calculatePredefinedDates() {
            if (this.overviewTab == 0) {
                this.lineTitle = 'Sales ';
            }
            else {
                this.lineTitle = 'Orders ';
            }

            if (this.selectedPreDate == 'Last 7 days') {
                this.option = 1;
                this.lineTitle += 'Growth by 7 Days';
            }
            else if (this.selectedPreDate == 'This month') {
                this.option = 2;
                this.lineTitle += 'Growth by This Month';
            }
            else if (this.selectedPreDate == 'Last 3 months') {
                this.option = 3;
                this.lineTitle += 'Growth by 3 Months';
            }
            else if (this.selectedPreDate == 'Last 6 months') {
                this.option = 4;
                this.lineTitle += 'Growth by 6 Months';
            }
            else if (this.selectedPreDate == 'Last 12 months') {
                this.option = 5;
                this.lineTitle += 'Growth by 12 months';
            }
            else {
                this.option = 0;
                this.lineTitle += 'Growth by Today';
            }
        },

        getSummary() {
            if(this.option != null) {

                this.summaryLoading = true;
                axios.get('/user-referral-sale/' + this.$key + '?o=' + this.option)
                .then(response =>{
                    this.tabs[0].number = response.data.current_sales;
                    this.tabs[0].previousNumber = response.data.past_sales,
                    this.tabs[0].status = response.data.sales_status;
                    this.tabs[0].percentage = response.data.sales_percentage;
                    this.tabs[0].caption = 'from previous period';

                    this.tabs[1].number = response.data.current_orders;
                    this.tabs[1].previousNumber = response.data.past_orders,
                    this.tabs[1].status = response.data.orders_status;
                    this.tabs[1].percentage = response.data.orders_percentage;
                    this.tabs[1].caption = 'from previous period';

                    this.summaryLoading = false;
                })
                .catch(error =>{
                    if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                        this.$logout();
                    }
                })
            }
        },

        getSalesChart() {
            if(this.option != null) {

                this.chartLoading = true;
                axios.get('/user-referral-sale-chart/' + this.$key + '?o=' + this.option)
                .then(response =>{
                    let labelsArr = [];
                    let datasetsArr = [];
                    response.data.current_records.forEach((obj) => {
                        labelsArr.push(obj.dates);
                        datasetsArr.push(Number(obj.accumulate_sales));
                    });

                    let pastLabelsArr = [];
                    let pastDatasetsArr = [];
                    response.data.past_records.forEach((obj) => {
                        pastLabelsArr.push(obj.dates);
                        pastDatasetsArr.push(Number(obj.accumulate_sales));
                    });

                    this.lineData = {
                        labels: labelsArr,
                        datasets: [
                            {
                                label: labelsArr,
                                data: datasetsArr,
                                backgroundColor: this.$primaryLightColor,
                                borderColor: this.$primaryColor,
                                borderWidth: 1,
                                hidden: false
                            },
                            {
                                label: pastLabelsArr,
                                data: pastDatasetsArr,
                                backgroundColor: this.$secondaryLightColor,
                                borderColor: this.$secondaryColor,
                                borderWidth: 1,
                                hidden: false
                            }
                        ]
                    }


                    if(pastLabelsArr.length > labelsArr.length) {
                        this.lineData.labels = pastLabelsArr;
                    }

                    this.slashCurrent = false;
                    this.slashPast = false;
                    this.chartLoading = false;
                })
                .catch(error =>{
                    if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                        this.$logout();
                    }
                })
            }
        },

        getOrdersChart() {
            if(this.option != null) {
                
                this.chartLoading = true;
                axios.get('/user-referral-order-chart/' + this.$key + '?o=' + this.option)
                .then(response =>{
                    let labelsArr = [];
                    let datasetsArr = [];
                    response.data.current_records.forEach((obj) => {
                        labelsArr.push(obj.dates);
                        datasetsArr.push(Number(obj.accumulate_orders));
                    });

                    let pastLabelsArr = [];
                    let pastDatasetsArr = [];
                    response.data.past_records.forEach((obj) => {
                        pastLabelsArr.push(obj.dates);
                        pastDatasetsArr.push(Number(obj.accumulate_orders));
                    });

                    this.lineData = {
                        labels: labelsArr,
                        datasets: [
                            {
                                label: labelsArr,
                                data: datasetsArr,
                                backgroundColor: this.$primaryLightColor,
                                borderColor: this.$primaryColor,
                                borderWidth: 1,
                                hidden: false
                            },
                            {
                                label: pastLabelsArr,
                                data: pastDatasetsArr,
                                backgroundColor: this.$secondaryLightColor,
                                borderColor: this.$secondaryColor,
                                borderWidth: 1,
                                hidden: false
                            }
                        ]
                    }

                    if(pastLabelsArr.length > labelsArr.length) {
                        this.lineData.labels = pastLabelsArr;
                    }

                    this.slashCurrent = false;
                    this.slashPast = false;
                    this.chartLoading = false;
                })
                .catch(error =>{
                    if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                        this.$logout();
                    }
                })
            }
        },

        getOrdersRecordsTable() {
            if(this.option != null) {
                //const { sortBy, sortDesc, page, itemsPerPage } = this.options

                let order = 'date_created';
                let sort = 'desc';
                let page = this.options.page;
                let paginate = this.options.itemsPerPage;

                // all
                if(this.options.itemsPerPage === -1) {
                    paginate = 1000000;
                }

                if(this.options.sortBy.length === 1) {
                    order = this.options.sortBy[0];
                }

                if(this.options.sortDesc.length === 1) {
                    if(this.options.sortDesc[0]) {
                        sort = 'desc';
                    }
                    else {
                        sort = 'asc';
                    }
                }


                this.ordersListLoading = true;
                axios.get('/user-referral-sales/' + this.$key
                    + '?o=' + this.option
                    + '&page=' + page
                    + '&order=' + order
                    + '&paginate=' + paginate
                    + '&sort=' + sort
                    + '&search=' + this.search)
                .then(response =>{
                    this.orderRecords = [];

                    if(response.status == 200) {
                        this.totalOrderRecords = response.data.records.total;

                        response.data.records.data.forEach(item => {
                            this.orderRecords.push(item);
                        });

                        this.ordersListLoading = false;
                    }
                })
                .catch(error =>{
                    if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                        this.$logout();
                    }
                })
            }
        },

        toggleDatasetVisibility (value) {
            this.alert = !this.alert;

            if(value == 0) {
                if(this.lineData.datasets[value].hidden) {
                    this.lineData.datasets[value].hidden = false;
                    this.slashCurrent = false;
                }
                else {
                    this.lineData.datasets[value].hidden = true;
                    this.slashCurrent = true;
                }
            }
            else {
                if(this.lineData.datasets[value].hidden) {
                    this.lineData.datasets[value].hidden = false;
                    this.slashPast = false;
                }
                else {
                    this.lineData.datasets[value].hidden = true;
                    this.slashPast = true;
                }
            }
        }
    }

}
</script>

<style>

</style>