<template>
    <v-app>
        <app-bar :drawer="drawer" @opened="openDrawer"/>

        <nav-bar :active="selectedMenu" :drawer="drawer" @closed="closeDrawer"/>

        <v-main>
            <v-container fluid class="py-2 py-lg-6 py-xl-6 px-lg-6 px-xl-6">
                <v-row>
                    <v-col cols="12" class="px-0 px-md-3 px-lg-3 px-xl-3">
                        <v-card
                            class="rounded-0"
                        >
                            <v-overlay
                                absolute
                                color="white"
                                opacity="0.36"
                                :value="payoutLoading"
                                class="payout-overlay"
                            >
                                <v-progress-circular
                                    indeterminate
                                    size="58"
                                    width="5"
                                    color="rgba(0, 0, 0, 0.2)"
                                ></v-progress-circular>
                            </v-overlay>

                            <div class="d-flex justify-space-between align-center px-6 pt-6">

                                <v-card-subtitle class="text-md-bold text--secondary px-0 pt-0 pb-2">
                                    <v-icon>mdi-swap-vertical</v-icon>
                                    <span>Payout</span>
                                </v-card-subtitle>

                                <!--<v-btn
                                    v-if="isMobile"
                                    color="secondary"
                                    outlined
                                    small
                                    class="d-none text-xs-bold"
                                >
                                    Download
                                </v-btn>

                                <v-btn
                                    v-else
                                    color="secondary"
                                    outlined
                                    class="d-none"
                                >
                                    Download
                                </v-btn>-->
                            </div>

                            <v-card-title class="display-xl-bold px-6 pt-0 pb-10">
                                RM {{ payout.amount }}
                                <v-chip
                                    :color="statusBgColor"
                                    :text-color="statusTxtColor"
                                    class="ml-4 text-xs-bold"
                                    small
                                    label
                                >
                                    {{ payout.status }}
                                </v-chip>
                            </v-card-title>

                            <div class="px-6 py-4">
                                <v-card-title class="display-md-bold px-1 py-4">
                                    Overview
                                </v-card-title>

                                <v-divider></v-divider>

                                <v-simple-table class="pt-7 pb-3">
                                    <template v-slot:default>
                                        <tbody>
                                            <tr>
                                                <td class="text--secondary text-lg payout-col payout-col-title">Date Created</td>
                                                <td class="text-lg payout-col">{{ payout.date_created }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text--secondary text-lg payout-col payout-col-title">Payout Date</td>
                                                <td class="text-lg payout-col">{{ payout.start_date }} ~ {{ payout.end_date }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text--secondary text-lg payout-col payout-col-title">Amount</td>
                                                <td class="text-lg payout-col">RM {{ payout.amount }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text--secondary text-lg payout-col payout-col-title">ID</td>
                                                <td class="text-lg payout-col">{{ payout.payout_id }}</td>
                                            </tr>
                                        </tbody>
                                    </template>
                                </v-simple-table>
                            </div>

                            <div class="px-6 py-4">
                                <v-card-title class="display-md-bold px-1 py-4">
                                    Account Details
                                </v-card-title>

                                <v-divider></v-divider>

                                <div class="pt-7 pb-1 d-flex px-4">
                                    <v-icon large>mdi-bank</v-icon>
                                    <div class="ml-4">
                                        <div>
                                            <span>{{ payout.bank }}</span>
                                            <v-chip
                                                class="ml-2 text-xs-bold"
                                                small
                                                label
                                            >
                                                MYR
                                            </v-chip>
                                        </div>
                                        <div class="text--secondary">{{ payout.acc_no }}</div>
                                    </div>
                                </div>

                                <v-simple-table class="py-3">
                                    <template v-slot:default>
                                        <tbody>
                                            <tr>
                                                <td class="text--secondary text-lg payout-col payout-col-title">Account Name</td>
                                                <td class="text-lg payout-col">{{ payout.acc_name }}</td>
                                            </tr>
                                        </tbody>
                                    </template>
                                </v-simple-table>
                            </div>

                            <div class="px-6 py-4">
                                <v-card-title class="display-md-bold px-1 py-4">
                                    Summary
                                </v-card-title>

                                <v-divider></v-divider>

                                <v-container fluid
                                    v-for="(payoutItem, index) in payoutArr"
                                    :key="index"
                                >
                                    <v-row>
                                        <v-col class="py-6">
                                            <v-data-table
                                                :headers="payoutHeaders"
                                                :items="payoutItem.details"
                                                :expanded="expanded"
                                                :single-expand="false"
                                                item-key="id"
                                                hide-default-footer
                                            >
                                                <template v-slot:item="{ item }">
                                                    <tr
                                                        v-if="item.expand.length != 0"
                                                        @click="toggleExpand(item)"
                                                        class="grey-bg"
                                                    >
                                                        <td>
                                                            <v-btn
                                                                icon
                                                                small
                                                            >
                                                                <v-icon v-if="!expanded.includes(item)">mdi-chevron-right</v-icon>
                                                                <v-icon v-if="expanded.includes(item)">mdi-chevron-down</v-icon>
                                                            </v-btn>
                                                        </td>
                                                        <td class="text-lg payout-col text--secondary">{{ item.name }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">RM {{ parseFloat(item.sales).toFixed(2) }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">{{ item.rate }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">RM {{ parseFloat(item.total).toFixed(2) }}</td>
                                                    </tr>
                                                    <tr v-else>
                                                        <td></td>
                                                        <td class="text-lg payout-col text--secondary">{{ item.name }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">RM {{ parseFloat(item.sales).toFixed(2) }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">{{ parseFloat(item.rate) * 100 }}%</td>
                                                        <td class="text-right text-lg payout-col text--secondary">RM {{ parseFloat(item.total).toFixed(2) }}</td>
                                                    </tr>
                                                </template>


                                                <template
                                                    v-slot:expanded-item="{ item }"
                                                >
                                                    <tr
                                                        v-for="(ele, index) in item.expand"
                                                        :key="index"
                                                    >
                                                        <td></td>
                                                        <td class="text--secondary pl-8">{{ ele.name }}</td>
                                                        <td class="text-right text--secondary">RM {{ parseFloat(ele.sales).toFixed(2) }}</td>
                                                        <td class="text-right text--secondary">{{ parseFloat(ele.rate) * 100 }}%</td>
                                                        <td class="text-right text--secondary">RM {{ parseFloat(ele.total).toFixed(2) }}</td>
                                                    </tr>
                                                </template>

                                                <template slot="body.append">
                                                    <tr>
                                                        <td></td>
                                                        <td colspan="3" class="text-lg black--text payout-col">Subtotal</td>
                                                        <td class="text-right display-sm-bold payout-col default-color">RM {{ parseFloat(payoutItem.subtotal).toFixed(2) }}</td>
                                                    </tr>
                                                </template>
                                            </v-data-table>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </div>

                            <div class="px-6 py-4">
                                <v-card-title class="display-md-bold px-1 py-4">
                                    Final Payout
                                </v-card-title>

                                <v-divider></v-divider>

                                <v-container fluid>
                                    <v-row>
                                        <v-col class="py-6">
                                            <v-data-table
                                                :headers="settlementHeaders"
                                                :items="settlementArr"
                                                hide-default-footer
                                            >
                                                <template v-slot:item="{ item }">
                                                    <tr>
                                                        <td class="text-lg payout-col text--secondary">{{ item.name }}</td>
                                                        <td class="text-right text-lg payout-col text--secondary">
                                                            <span>{{ item.type }} RM {{ parseFloat(item.total).toFixed(2) }}</span>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <template slot="body.append">
                                                    <tr>
                                                        <td class="text-lg black--text payout-col">Total Payout</td>
                                                        <td class="text-right display-sm-bold payout-col default-color">RM {{ payout.amount }}</td>
                                                    </tr>
                                                </template>
                                            </v-data-table>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
import AppBar from './components/AppBar.vue';
import NavBar from './components/NavBar.vue';
export default {
    components: {
        AppBar,
        NavBar
    },
    data: ()=> {
        return {
            drawer: false,
            title: 'Payout - Trofit Partner',
            payoutLoading: true,
            payoutID: '',
            payout: {
                payout_id: '',
                date_created: '',
                acc_type: '',
                bank: '',
                acc_no: '',
                acc_name: '',
                amount: '',
                status: '',
                start_date: '',
                end_date: ''
            },
            payoutHeaders: [
                {
                    text: '',
                    align: 'start',
                    value: 'expand',
                    sortable: false,
                    fixed: true,
                    width: '60px'
                },
                {
                    text: '',
                    align: 'start',
                    value: 'name',
                    sortable: false
                },
                {
                    text: 'Sales',
                    align: 'end',
                    value: 'sales',
                    sortable: false,
                    class: ['text-sm-bold']
                },
                {
                    text: 'Rate',
                    align: 'end',
                    value: 'rate',
                    sortable: false,
                    class: ['text-sm-bold']
                },
                {
                    text: 'Total',
                    align: 'end',
                    value: 'total',
                    sortable: false,
                    class: ['text-sm-bold']
                },
            ],
            expanded: [],
            payoutDetails: [
                {
                    name: 'Personal',
                    sales: 1000.01,
                    rate: '',
                    total: 50,
                    expand: [
                        {
                            name: 'Personal',
                            sales: 500.01,
                            rate: '0%',
                            total: 0
                        },
                        {
                            name: 'Referral',
                            sales: 500.00,
                            rate: '10%',
                            total: 50
                        }
                    ]
                },
                {
                    name: 'Direct Child',
                    sales: 2000.01,
                    rate: '10%',
                    total: 200,
                    expand: []
                },
                {
                    name: 'Group',
                    sales: 5000.01,
                    rate: '10%',
                    total: 500,
                    expand: []
                }
            ],
            payoutArr: [],
            settlementHeaders: [
                {
                    text: '',
                    align: 'start',
                    value: 'name',
                    sortable: false
                },
                {
                    text: 'Total',
                    align: 'end',
                    value: 'total',
                    sortable: false,
                    class: ['text-sm-bold']
                },
            ],
            settlementArr: []
        }
    },

    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        },

        selectedMenu: function() {
            if(this.$role == 4) {
                return 4;
            }
            else {
                return 3;
            }
        },

        statusBgColor: function() {
            if(this.payout.status == 'Pending') {
                return '#d2eafc';
            }
            else if (this.payout.status == 'Processing') {
                return 'orange accent-1';
            }
            else if (this.payout.status == 'Paid') {
                return 'green accent-1';
            }
            else if (this.payout.status == 'Failed') {
                return 'red accent-1';
            }
            else {
                return 'grey lighten-2';
            }
        },

        statusTxtColor: function() {
            if(this.payout.status == 'Pending') {
                return '#2196f3';
            }
            else if (this.payout.status == 'Processing') {
                return 'orange darken-4';
            }
            else if (this.payout.status == 'Paid') {
                return 'green darken-4';
            }
            else if (this.payout.status == 'Failed') {
                return 'red darken-4';
            }
            else {
                return 'grey darken-4';
            }
        }
    },

    methods: {
        openDrawer(value) {
            this.drawer = value;
        },

        closeDrawer(value) {
            this.drawer = value;
        },

        toggleExpand(item) {
            var index = this.expanded.indexOf(item);

            if (index > -1) {
                this.expanded.splice(index, 1);
            }
            else {
                this.expanded.push(item);
            }
        },

        getPayoutSummary() {
            // this.totalPayoutMoney = 0;
            this.payoutLoading = true;
            this.payoutArr = [];

            axios.get('/user-payout-details/' + this.payoutID)
            .then(response =>{

                if(response.status == 200) {
                    this.payout.payout_id = response.data.payout_id;
                    this.payout.date_created = response.data.date;

                    this.payout.acc_type = 'Bank Account';
                    this.payout.bank = (response.data.account_details.bank_name || '').toUpperCase();
                    this.payout.acc_no = response.data.account_details.beneficial_account;
                    this.payout.acc_name = (response.data.account_details.beneficiary || '').toUpperCase();

                    this.payout.amount = parseFloat(response.data.amount).toFixed(2);
                    this.payout.status = response.data.status;

                    this.payout.start_date = response.data.start_date;
                    this.payout.end_date = response.data.end_date;

                    this.payoutArr = response.data.payout_details;

                    this.settlementArr = response.data.settlement;

                    this.payoutLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            })
        }
    },

    created() {
        document.title = this.title;

        if(this.$role == 4) {
            this.mainMenu = 4;
        }

        this.payoutID = this.$route.params.id;
        this.getPayoutSummary();
    }
}
</script>
