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
                                <v-col cols="12" class="pa-0">
                                    <v-card
                                        class="rounded-0"
                                    >
                                        <v-card-title class="display-sm-bold px-6 pt-4 pb-4">
                                            Payouts
                                        </v-card-title>
                                        <v-container fluid>
                                            <v-row class="pb-4 px-6">
                                                <v-col
                                                    cols="12"
                                                    class="pa-0"
                                                >
                                                    <v-card
                                                        outlined
                                                        class="pb-4 rounded-0"
                                                    >
                                                        <v-data-table
                                                            :headers="headers"
                                                            :items="payoutRecords"
                                                            :options.sync="options"
                                                            :server-items-length="totalPayoutRecords"
                                                            no-data-text="No Data."
                                                            no-results-text="No Result."
                                                        >
                                                            <template v-slot:[`item.payout_id`]="{ item }">
                                                                <td>
                                                                    <router-link :to="'/payout/' + item.payout_id">{{ item.payout_id }}</router-link>
                                                                </td>
                                                            </template>

                                                            <template v-slot:[`item.actions`]="{ item }">
                                                                <v-icon
                                                                    @click="downloadPayout(item)"
                                                                >
                                                                    mdi-download
                                                                </v-icon>
                                                            </template>
                                                        </v-data-table>

                                                        <v-overlay
                                                            absolute
                                                            color="white"
                                                            opacity="0.36"
                                                            :value="payoutLoading"
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
export default {
    components: {
        AppBar,
        NavBar
    },
    data: ()=> {
        return {
            drawer: false,
            title: 'Payouts - Trofit Partner',
            mainMenu: 3,
            subMenus: [
                {
                    icon: 'mdi-currency-usd',
                    title: 'Payouts',
                },
            ],
            sideMenu: 0,
            headers: [
                {
                    text: 'Payout #',
                    align: 'start',
                    value: 'payout_id',
                },
                {
                    text: 'Date',
                    align: 'start',
                    value: 'month_name',
                },
                {
                    text: 'Amount',
                    align: 'start',
                    value: 'amount',
                },
                {
                    text: 'Status',
                    align: 'start',
                    value: 'status'
                }
            ],
            payoutRecords: [],
            options: {},
            totalPayoutRecords: 2,
            payoutLoading: false
        }
    },
    watch: {
        options: {
            handler () {
                this.getPayoutRecordsTable();
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

        getPayoutRecordsTable() {
            // const { sortBy, sortDesc, page, itemsPerPage } = this.options

            let order = 'date';
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


            this.payoutLoading = true;
            axios.get('/user-payout/' + this.$key
                + '?page=' + page
                + '&order=' + order
                + '&paginate=' + paginate
                + '&sort=' + sort)
            .then(response =>{
                this.payoutRecords = [];

                if(response.status == 200) {
                    this.totalPayoutRecords = response.data.records.total;

                    response.data.records.data.forEach(item => {
                        this.payoutRecords.push(item);
                    });

                    this.payoutLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            })

        },

        downloadPayout(i) {

        }
    },

    created() {
        document.title = this.title;
        if(this.$role == 4) {
            this.mainMenu = 4;
        }
    }
}
</script>
