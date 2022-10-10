<template>
    <v-app>
        <app-bar :drawer="drawer" @opened="openDrawer"/>

        <nav-bar :active="mainMenu" :drawer="drawer" @closed="closeDrawer"/>

        <v-main>
            <v-container fluid class="py-2 px-0 py-lg-6 py-xl-6 px-lg-6 px-xl-6">
                <v-tabs
                    v-model="sideMenu"
                    show-arrows
                    class="pb-2"
                >
                    <v-tab
                        v-for="(item, index) in subMenus"
                        :key="index"
                    >
                        <v-icon
                            size="20"
                            class="me-3"
                        >
                            {{ item.icon }}
                        </v-icon>
                        <span>{{ item.title }}</span>
                    </v-tab>
                </v-tabs>

                <v-tabs-items v-model="sideMenu">
                    <v-tab-item>
                        <v-container fluid>
                            <v-row>
                                <v-col
                                    cols="12"
                                    md="12"
                                    lg="6"
                                    xl="6"
                                    class="pa-0 pr-lg-1 pr-xl-1"
                                >
                                    <v-card
                                        class="rounded-0 fill-height"
                                    >
                                        <v-overlay
                                            absolute
                                            color="white"
                                            opacity="0.36"
                                            :value="childrenLoading"
                                        >
                                            <v-progress-circular
                                                indeterminate
                                                size="58"
                                                width="5"
                                                color="rgba(0, 0, 0, 0.2)"
                                            ></v-progress-circular>
                                        </v-overlay>

                                        <v-card-title class="display-sm-bold py-4 px-6">
                                            Current Month Sales
                                        </v-card-title>

                                        <v-container fluid>
                                            <v-row>
                                                <v-col class="pb-5">
                                                    <v-card-text  class="success--text display-md-bold text-center pa-3">
                                                        RM {{ parseFloat(totalSales).toFixed(2) }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 px-3">Total Children Sales</v-card-subtitle>
                                                </v-col>
                                            </v-row>

                                            <v-row class="pa-5">
                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        RM {{ parseFloat(children[0].sales).toFixed(2) }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[0].name }} Children</v-card-subtitle>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0 pt-1">({{ children[0].bonus }})</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        RM {{ parseFloat(children[1].sales).toFixed(2) }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[1].name }} Children</v-card-subtitle>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0 pt-1">({{ children[1].bonus }})</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        RM {{ parseFloat(children[2].sales).toFixed(2) }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[2].name }} Children</v-card-subtitle>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0 pt-1">({{ children[2].bonus }})</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        RM {{ parseFloat(children[3].sales).toFixed(2) }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[3].name }} Children</v-card-subtitle>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0 pt-1">({{ children[3].bonus }})</v-card-subtitle>
                                                </v-col>
                                            </v-row>
                                        </v-container>
                                    </v-card>
                                </v-col>
                                <v-col
                                    cols="12"
                                    md="12"
                                    lg="6"
                                    xl="6"
                                    class="pa-0 pt-2 pt-lg-0 pt-xl-0 pl-lg-1 pl-xl-1"
                                >
                                    <v-card
                                        class="rounded-0 fill-height"
                                    >
                                        <v-overlay
                                            absolute
                                            color="white"
                                            opacity="0.36"
                                            :value="childrenLoading"
                                        >
                                            <v-progress-circular
                                                indeterminate
                                                size="58"
                                                width="5"
                                                color="rgba(0, 0, 0, 0.2)"
                                            ></v-progress-circular>
                                        </v-overlay>

                                        <v-card-title class="display-sm-bold py-4 px-6">
                                            Direct Children
                                        </v-card-title>

                                        <v-container fluid>
                                            <v-row>
                                                <v-col class="pb-5">
                                                    <v-card-text  class="success--text display-md-bold text-center pa-3">
                                                        {{ totalChildren }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 px-3">Total Direct Children</v-card-subtitle>
                                                </v-col>
                                            </v-row>

                                            <v-row class="pa-5">
                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        {{ children[0].amount }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[0].name }} Children</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        {{ children[1].amount }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[1].name }} Children</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                       {{ children[2].amount }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[2].name }} Children</v-card-subtitle>
                                                </v-col>

                                                <v-divider vertical class="my-2"></v-divider>

                                                <v-col>
                                                    <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                                        {{ children[3].amount }}
                                                    </v-card-text>
                                                    <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">{{ children[3].name }} Children</v-card-subtitle>
                                                </v-col>
                                            </v-row>
                                        </v-container>
                                    </v-card>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col
                                    cols="12"
                                    class="pa-0 pt-2"
                                >
                                    <v-card
                                        class="rounded-0"
                                    >
                                        <v-card-title class="display-sm-bold px-6 pt-4 pb-4">
                                            Overview
                                        </v-card-title>
                                        <v-card-actions class="text-sm px-6">
                                            <v-select
                                                dense
                                                outlined
                                                hide-details
                                                single-line
                                                return-object
                                                v-model="selectedLevel"
                                                :items="filterLevels"
                                                item-text="text"
                                                item-value="value"
                                                label="Levels"
                                                @change="reGet(1)"
                                                class="text-sm mr-3 sales-card-select"
                                            ></v-select>
                                            <v-select
                                                dense
                                                outlined
                                                hide-details
                                                single-line
                                                v-model="selectedPreDate"
                                                :items="filterPreDates"
                                                label="Pre-defined Range"
                                                @change="reGet(1)"
                                                class="text-sm mr-3 sales-card-select"
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
                                                            class="sales-overlay"
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
                                                            class="sales-overlay"
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
                                <v-col cols="12" class="pa-0 py-2">
                                    <v-card
                                        class="rounded-0"
                                    >
                                        <v-card-title class="display-sm-bold px-6 pt-4 pb-4">
                                            Members
                                        </v-card-title>
                                        <v-card-actions class="text-sm px-6 d-block d-md-flex d-lg-flex d-xl-flex">
                                            <div class="d-flex mb-6">
                                                <v-select
                                                    dense
                                                    outlined
                                                    hide-details
                                                    single-line
                                                    return-object
                                                    v-model="selectedLevel"
                                                    :items="filterLevels"
                                                    item-text="text"
                                                    item-value="value"
                                                    label="Levels"
                                                    @change="reGet(1)"
                                                    class="text-sm mr-3 sales-card-select"
                                                ></v-select>
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
                                            </div>

                                            <v-spacer></v-spacer>

                                            <v-text-field
                                                v-model.trim="search"
                                                append-icon="mdi-magnify"
                                                label="Search"
                                                single-line
                                                hide-details
                                                class="pa-2 mt-0"
                                                @keyup="getChildrenTable"
                                            ></v-text-field>
                                        </v-card-actions>
                                        <v-container fluid>
                                            <v-row class="py-4 px-6">
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
                                                        class="py-4 rounded-0"
                                                    >
                                                        <v-data-table
                                                            :headers="headers"
                                                            :items="memberRecords"
                                                            :options.sync="options"
                                                            :server-items-length="totalMemberRecords"
                                                            no-data-text="No Data."
                                                            :search="search"
                                                            no-results-text="No Result."
                                                        >
                                                            <template  v-slot:[`item.children_sales`]="{ item }">
                                                                <span>RM {{ parseFloat(item.children_sales).toFixed(2) }}</span>
                                                            </template>
                                                        </v-data-table>

                                                        <v-overlay
                                                            absolute
                                                            color="white"
                                                            opacity="0.36"
                                                            :value="memberLoading"
                                                            class="sales-overlay"
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
                    </v-tab-item>
                </v-tabs-items>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
import AppBar from './components/AppBar.vue';
import NavBar from './components/NavBar.vue';
import LineChart from '../charts/LineChart.js';
export default {
    components: {
        AppBar,
        NavBar,
        LineChart
    },
    data: ()=> {
        return {
            drawer: false,
            userLevel: 1,
            title: 'Direct Child Sales - Trofit Partner',
            mainMenu: 2,
            subMenus: [
                {
                    icon: 'mdi-currency-usd',
                    title: 'Direct Child Sales',
                }
            ],
            overviewTab: 0,
            sideMenu: 0,
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
                },
            ],
            lineData: null,
            lineTitle: 'Sales Growth by 7 days',
            children: [
                {
                    name: 'TG',
                    key: 0,
                    amount: 0,
                    percent: 0,
                    bonus: null,
                    secondary: null
                },
                {
                    name: 'TE',
                    key: 0,
                    amount: 0,
                    percent: 0,
                    bonus: null,
                    secondary: null
                },
                {
                    name: 'TP',
                    key: 0,
                    amount: 0,
                    percent: 0,
                    bonus: null,
                    secondary: null
                },
                {
                    name: 'TM',
                    key: 0,
                    amount: 0,
                    percent: 0,
                    bonus: null,
                    secondary: null
                }
            ],
            selectedLevel: {
                text: 'All',
                value: 0
            },
            filterLevels: [
                {
                    text: 'All',
                    value: 0
                },
                {
                    text: 'TG',
                    value: 0
                },
                {
                    text: 'TE',
                    value: 0
                },
                {
                    text: 'TP',
                    value: 0
                },
                {
                    text: 'TM',
                    value: 0
                }
            ],
            level: 0,
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
                    text: 'Member #',
                    align: 'start',
                    value: 'referral_code',
                },
                {
                    text: 'Member Name',
                    align: 'start',
                    value: 'children_name',
                },
                {
                    text: 'Member Phone',
                    align: 'start',
                    value: 'contact',
                },
                {
                    text: 'Level',
                    align: 'start',
                    value: 'level'
                },
                {
                    text: 'Sales',
                    align: 'start',
                    value: 'children_sales',
                }
            ],
            memberRecords: [],
            options: {},
            totalMemberRecords: 0,
            search: '',
            initialloading: false,
            childrenLoading: false,
            summaryLoading: true,
            chartLoading: true,
            memberLoading: true,
            totalSales: 0,
            totalChildren: 0,
        }
    },
    watch: {
        options: {
            handler () {
                this.getChildrenTable();
            },
            deep: true,
        },
    },

    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        }
    },

    methods: {
        openDrawer(value) {
            this.drawer = value;
        },

        closeDrawer(value) {
            this.drawer = value;
        },

        hexToRgbA(hex, opacity) {
            var c;
            if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
                c = hex.substring(1).split('');
                if(c.length == 3){
                    c = [c[0], c[0], c[1], c[1], c[2], c[2]];
                }

                c = '0x' + c.join('');

                return 'rgba(' + [(c>>16)&255, (c>>8)&255, c&255].join(',') + ',' + opacity + ')';
            }
            throw new Error('Bad Hex');
        },

        getPartnerDetails() {
            this.childrenLoading = true;
            axios.get('/user-direct-child/' + this.$key)
            .then(response =>{

                if(response.status == 200) {
                    this.userLevel = parseInt(response.data.user_role);

                    this.children = [];
                    this.filterLevels = [];
                    this.totalSales = 0;

                    let all = {
                        text: 'All',
                        value: 0
                    };

                    this.filterLevels.push(all);

                    for (let counter = 0; counter < 4; counter++) {
                        let child = {};
                        child.name = response.data.children_name[counter];
                        child.key = counter+1;
                        child.amount = parseInt(response.data.children_count[counter]);
                        child.percent = response.data.bonus_amount[counter];
                        child.bonus = response.data.bonus_name[counter];
                        child.sales = parseFloat(response.data.children_sales[counter]);

                        let level = {};
                        level.text = response.data.children_name[counter];
                        level.value = counter+1;

                        this.children.push(child);

                        this.filterLevels.push(level);

                        this.totalSales += child.sales;
                        this.totalChildren += child.amount;
                    }

                    this.childrenLoading = false;

                    this.reGet(1);
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            })
        },

        reGet(i) {
            this.calculateLevels();
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
                this.getChildrenTable();
            }
        },

        calculateLevels() {
            this.level = parseInt(this.selectedLevel.value);
        },

        calculatePredefinedDates() {
            if(parseInt(this.selectedLevel.value) == 0) {
                this.lineTitle = "All's ";
            }
            else if(parseInt(this.selectedLevel.value) == 1) {
                this.lineTitle = "TG's ";
            }
            else if(parseInt(this.selectedLevel.value) == 2) {
                this.lineTitle = "TE's ";
            }
            else if(parseInt(this.selectedLevel.value) == 3) {
                this.lineTitle = "TP's ";
            }
            else if(parseInt(this.selectedLevel.value) == 4) {
                this.lineTitle = "TM's ";
            }
            else {
                this.lineTitle = '';
            }


            if (this.overviewTab == 0) {
                this.lineTitle += 'Sales ';
            }
            else {
                this.lineTitle += 'Orders ';
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
            if(this.level != null && this.option != null) {

                this.summaryLoading = true;
                axios.get('/user-direct-child-sale/' + this.$key + '?o=' + this.option + '&l=' + this.level)
                .then(response =>{
                    this.tabs[0].number = response.data.group_sales;
                    this.tabs[0].previousNumber = response.data.past_sales,
                    this.tabs[0].status = response.data.sales_status;
                    this.tabs[0].percentage = response.data.sales_percentage;
                    this.tabs[0].caption = 'from previous period';

                    this.tabs[1].number = response.data.group_orders;
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
            if(this.level != null && this.option != null) {

                this.chartLoading = true;
                axios.get('/user-direct-child-sale-chart/' + this.$key + '?o=' + this.option + '&l=' + this.level)
                .then(response =>{
                    let labelsArr = [];
                    let datasetsArr = [];
                    response.data.current_records.forEach((obj) => {
                        labelsArr.push(obj.dates);
                        datasetsArr.push(Number(obj.level_sales));
                    });

                    let pastLabelsArr = [];
                    let pastDatasetsArr = [];
                    response.data.past_records.forEach((obj) => {
                        pastLabelsArr.push(obj.dates);
                        pastDatasetsArr.push(Number(obj.level_sales));
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
            if(this.level != null && this.option != null) {

                this.chartLoading = true;
                axios.get('/user-direct-child-order-chart/' + this.$key + '?o=' + this.option + '&l=' + this.level)
                .then(response =>{
                    let labelsArr = [];
                    let datasetsArr = [];
                    response.data.current_records.forEach((obj) => {
                        labelsArr.push(obj.dates);
                        datasetsArr.push(Number(obj.level_orders));
                    });

                    let pastLabelsArr = [];
                    let pastDatasetsArr = [];
                    response.data.past_records.forEach((obj) => {
                        pastLabelsArr.push(obj.dates);
                        pastDatasetsArr.push(Number(obj.level_orders));
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

                    this.chartLoading = false;
                })
                .catch(error =>{
                    if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                        this.$logout();
                    }
                })
            }
        },

        getChildrenTable() {
            if(this.level != null && this.option != null) {
                //const { sortBy, sortDesc, page, itemsPerPage } = this.options

                let order = 'children_sales';
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


                this.memberLoading = true;
                axios.get('/user-direct-child-sales/' + this.$key
                    + '?o=' + this.option
                    + '&l=' + this.level
                    + '&page=' + page
                    + '&order=' + order
                    + '&paginate=' + paginate
                    + '&sort=' + sort
                    + '&search=' + this.search)
                .then(response =>{
                    this.memberRecords = [];

                    if(response.status == 200) {
                        this.totalMemberRecords = response.data.records.total;

                        response.data.records.data.forEach(item => {
                            this.memberRecords.push(item);
                        });

                        this.memberLoading = false;
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
        },
    },

    created() {
        document.title = this.title;
        this.getPartnerDetails();
    }
}
</script>
