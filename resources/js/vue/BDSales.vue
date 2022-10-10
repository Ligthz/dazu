<template>
    <v-app>
        <app-bar :drawer="drawer" @opened="openDrawer"/>

        <nav-bar :active="mainMenu" :drawer="drawer" @closed="closeDrawer"/>

        <v-navigation-drawer
            fixed
            permanent
            right
            color="#F2F2F2"
            class="mt-12 subMenu"
            v-if="!isMobile"
        >
            <div class="subMenu-div">
                <div class="mb-3 mx-2 text-xs-bold text--secondary">Last Update: {{ lastUpdated }}</div>
                <v-card
                    class="rounded-lg"
                >
                    <v-overlay
                        absolute
                        color="white"
                        opacity="0.36"
                        :value="detailsLoading"
                    >
                        <v-progress-circular
                            indeterminate
                            size="58"
                            width="5"
                            color="rgba(0, 0, 0, 0.2)"
                        ></v-progress-circular>
                    </v-overlay>

                    <v-card-title class="text--secondary text-md-bold pt-7">Top Performers</v-card-title>

                    <v-card-text
                        v-if="orderedChildren.length == 0"
                        class="text-sm-bold text-center text--disabled pt-7"
                    >
                        No Data
                    </v-card-text>

                    <div v-else>
                        <v-card-subtitle class="text-right text-xs-bold text--disabled pt-5 pb-0">Accumulated Sales</v-card-subtitle>

                        <v-list two-line class="pb-4">
                            <template v-for="(item, index) in orderedChildren">
                                <div
                                    :key="index"
                                >
                                    <v-list-item
                                        class="align-items-start"
                                    >
                                        <v-badge
                                            bottom
                                            dot
                                            bordered
                                            overlap
                                            :color="item.color"
                                            offset-x="25"
                                            offset-y="25"
                                        >
                                            <v-list-item-avatar class="ml-0 mt-3" size="40" :color="item.color">
                                                <span class="white--text" v-if="item.avatar == null">{{ item.avatar_name }}</span>
                                                <v-img :src="item.avatar" v-else></v-img>
                                            </v-list-item-avatar>
                                        </v-badge>

                                        <v-list-item-content class="align-self-start pr-2">
                                            <v-list-item-title v-html="item.name" class="text-sm-bold title-color"></v-list-item-title>
                                            <v-list-item-subtitle class="text-xxs-bold text--disabled">{{ item.code }}</v-list-item-subtitle>
                                        </v-list-item-content>
                                        <v-list-item-content class="align-self-start subMenu-sales">
                                            <v-list-item-title class="success--text text-sm-bold">RM {{ parseFloat(item.total_sales).toFixed(2) }}</v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>

                                    <v-divider :key="index" v-if="index != orderedChildren.length-1" class="mx-4 my-1"></v-divider>
                                </div>
                            </template>
                        </v-list>
                    </div>
                </v-card>
            </div>
        </v-navigation-drawer>

        <v-main class="bd-main">
            <v-container fluid class="py-2 px-0 py-lg-6 py-xl-6 px-lg-6 px-xl-6">
                <v-tabs
                    v-model="topMenu"
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
                <v-tabs-items v-model="topMenu">
                    <v-tab-item>
                        <group-sales
                            :overviewLoading="overviewLoading"
                            :detailsLoading="detailsLoading"
                            :salesOverview="groupSalesOverview"
                            :children="children"
                        />
                    </v-tab-item>
                    <v-tab-item>
                        <levels-sales
                            :level="1"
                            :overviewLoading="firstBDOverviewLoading"
                            :detailsLoading="firstBDDetailsLoading"
                            :salesOverview="firstBDSalesOverview"
                            :children="firstBDs"
                        />
                    </v-tab-item>
                    <v-tab-item>
                        <levels-sales
                            :level="2"
                            :overviewLoading="secondBDOverviewLoading"
                            :detailsLoading="secondBDDetailsLoading"
                            :salesOverview="secondBDSalesOverview"
                            :children="secondBDs"
                        />
                    </v-tab-item>
                </v-tabs-items>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
import AppBar from './components/AppBar.vue';
import NavBar from './components/NavBar.vue';
import GroupSales from './components/GroupSales.vue';
import LevelsSales from './components/LevelsSales.vue';
import _ from 'lodash';
export default {
    components: {
        AppBar,
        NavBar,
        GroupSales,
        LevelsSales
    },
    data: ()=> {
        return {
            drawer: false,
            userLevel: 1,
            title: 'TM Sales - Trofit Partner',
            mainMenu: 3,
            subMenus: [
                {
                    icon: 'mdi-account-group-outline',
                    title: 'Personal Volume Sales',
                },
                {
                    icon: 'mdi-account-group-outline',
                    title: '1st Group Volume Sales',
                },
                {
                    icon: 'mdi-account-group-outline',
                    title: '2nd Group Volume Sales',
                }
            ],
            topMenu: 0,
            groupSalesOverview: {
                ba_count: 0,
                ba_sales: 0,
                be_count: 0,
                total_count: 0,
                be_sales: 0,
                bm_count: 0,
                bm_sales: 0,
                total_sales: 0,
                lastUpdate: ''
            },
            overviewLoading: false,
            detailsLoading: false,
            children: [],
            firstBDSalesOverview: {
                count: 0,
                sales: 0.0,
                lastUpdate: ''
            },
            firstBDOverviewLoading: false,
            firstBDDetailsLoading: false,
            firstBDs: [],
            secondBDSalesOverview: {
                count: 0,
                sales: 0.0,
                lastUpdate: ''
            },
            secondBDOverviewLoading: false,
            secondBDDetailsLoading: false,
            secondBDs: []
        }
    },

    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        },

        lastUpdated: function () {
            if(this.topMenu == 0) {
                return this.groupSalesOverview.lastUpdate;
            }
            else if(this.topMenu == 1){
                return this.firstBDSalesOverview.lastUpdate;
            }
            else if(this.topMenu == 2){
                return this.secondBDSalesOverview.lastUpdate;
            }
            else {
                return '';
            }
        },

        orderedChildren: function () {
            if(this.topMenu == 0) {
                let reordered = _.orderBy(this.children, ['total_sales', 'role'], ['desc', 'desc']);
                return _.take(reordered, 5);
            }
            else if(this.topMenu == 1){
                let reordered = _.orderBy(this.firstBDs, ['total_sales', 'num_grp_children'], ['desc', 'desc']);
                return _.take(reordered, 5);
            }
            else if(this.topMenu == 2){
                let reordered = _.orderBy(this.secondBDs, ['total_sales', 'num_grp_children'], ['desc', 'desc']);
                return _.take(reordered, 5);
            }
            else {
                return [];
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

        getGroupOverview() {
            this.overviewLoading = true;
            axios.get('/group-sale/' + this.$key)
            .then(response =>{

                if(response.status == 200) {
                    this.groupSalesOverview.ba_count = parseInt(response.data.count.grp_ba_count);
                    this.groupSalesOverview.be_count = parseInt(response.data.count.grp_be_count);
                    this.groupSalesOverview.bm_count = parseInt(response.data.count.grp_bm_count);
                    this.groupSalesOverview.total_count = this.groupSalesOverview.ba_count + this.groupSalesOverview.be_count + this.groupSalesOverview.bm_count;

                    this.groupSalesOverview.ba_sales = parseFloat(response.data.sales.grp_ba_sales).toFixed(2);
                    this.groupSalesOverview.be_sales = parseFloat(response.data.sales.grp_be_sales).toFixed(2);
                    this.groupSalesOverview.bm_sales = parseFloat(response.data.sales.grp_bm_sales).toFixed(2);
                    this.groupSalesOverview.total_sales = parseFloat(response.data.sales.total_grp_sales).toFixed(2);

                    if(response.data.count.date == null) {
                        this.groupSalesOverview.lastUpdate = '-';
                    }
                    else {
                        this.groupSalesOverview.lastUpdate = response.data.count.date + ' 23:59:59';
                    }

                    this.overviewLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },

        getGroupDetails() {
            this.detailsLoading = true;
            axios.get('/group-sales/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    this.children = [];

                    response.data.group.forEach(element => {
                        let child = {
                            avatar: element.avatar_path,
                            avatar_name: element.avatar_name,
                            name: element.fname + ' '  + element.lname,
                            date_joined: element.partner_joined_at,
                            contact: element.contact,
                            code: element.referral_code,
                            role: element.roles,
                            role_name: element.roles_name,
                            mentor: element.mentor_id,
                            color: element.roles_color,
                            personal_sales: 0,
                            total_sales: 0
                        }

                        if(element.avatar_path == null) {
                            if(element.lname == null && element.fname == null) {
                                child.avatar_name = 'BW';
                            }
                            else {
                                child.avatar_name = '';

                                if(element.fname != null) {
                                    child.avatar_name += (element.fname).charAt(0).toUpperCase();
                                }

                                if(element.lname != null) {
                                    child.avatar_name += (element.lname).charAt(0).toUpperCase();
                                }
                            }
                        }

                        response.data.sales.every(element2 => {
                            if(child.code == element2.referral_code) {
                                child.personal_sales = parseFloat(element2.total_sales);
                                child.total_sales = parseFloat(element2.total_sales);

                                return false;
                            }

                            return true;
                        });

                        this.children.push(child);
                    });

                    this.detailsLoading = false;

                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },

        getFirstBDOverview() {
            this.firstBDOverviewLoading = true;
            axios.get('/first-bd-sale/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    this.firstBDSalesOverview.count = parseInt(response.data.count.first_level_bd_count);
                    this.firstBDSalesOverview.sales = parseFloat(response.data.sales.first_level_bd_sales).toFixed(2);

                    if(response.data.count.date == null) {
                        this.firstBDSalesOverview.lastUpdate = '-';
                    }
                    else {
                        this.firstBDSalesOverview.lastUpdate = response.data.count.date + ' 23:59:59';
                    }

                    this.firstBDOverviewLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },

        getFirstBDDetails() {
            this.firstBDDetailsLoading = true;
            axios.get('/first-bd-sales/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    this.firstBDs = [];

                    response.data.bds.forEach(element => {
                        let child = {
                            avatar: element.avatar_path,
                            avatar_name: element.avatar_name,
                            name: element.fname + ' '  + element.lname,
                            date_joined: element.partner_joined_at,
                            contact: element.contact,
                            code: element.referral_code,
                            role: element.roles,
                            role_name: element.roles_name,
                            mentor: element.mentor_id,
                            color: element.roles_color,
                            num_grp_children: 0,
                            grp_sales: 0,
                            personal_sales: 0,
                            total_sales: 0
                        }

                        if(element.avatar_path == null) {
                            if(element.lname == null && element.fname == null) {
                                child.avatar_name = 'BW';
                            }
                            else {
                                child.avatar_name = '';

                                if(element.fname != null) {
                                    child.avatar_name += (element.fname).charAt(0).toUpperCase();
                                }

                                if(element.lname != null) {
                                    child.avatar_name += (element.lname).charAt(0).toUpperCase();
                                }
                            }
                        }

                        response.data.count.every(element2 => {
                            if(child.code == element2.referral_code) {
                                child.num_grp_children = parseInt(element2.grp_ba_count) + parseInt(element2.grp_be_count) + parseInt(element2.grp_bm_count);

                                return false;
                            }

                            return true;
                        });

                        response.data.sales.every(element3 => {
                            if(child.code == element3.referral_code) {
                                child.personal_sales = element3.personal_sales;
                                child.grp_sales = element3.total_grp_sales;
                                child.total_sales = parseFloat(element3.personal_sales) + parseFloat(element3.total_grp_sales);

                                return false;
                            }

                            return true;
                        });


                        this.firstBDs.push(child);
                    });

                    this.firstBDDetailsLoading = false;

                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },

        getSecondBDOverview() {
            this.secondBDOverviewLoading = true;
            axios.get('/second-bd-sale/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    this.secondBDSalesOverview.count = parseInt(response.data.count.second_level_bd_count);
                    this.secondBDSalesOverview.sales = parseFloat(response.data.sales.second_level_bd_sales).toFixed(2);

                    if(response.data.count.date == null) {
                        this.secondBDSalesOverview.lastUpdate = '-';
                    }
                    else {
                        this.secondBDSalesOverview.lastUpdate = response.data.count.date + ' 23:59:59';
                    }

                    this.secondBDOverviewLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },

        getSecondBDDetails() {
            this.secondBDDetailsLoading = true;
            axios.get('/second-bd-sales/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    this.secondBDs = [];

                    response.data.bds.forEach(element => {
                        let child = {
                            avatar: element.avatar_path,
                            avatar_name: element.avatar_name,
                            name: element.fname + ' '  + element.lname,
                            date_joined: element.partner_joined_at,
                            contact: element.contact,
                            code: element.referral_code,
                            role: element.roles,
                            role_name: element.roles_name,
                            mentor: element.mentor_id,
                            color: element.roles_color,
                            num_grp_children: 0,
                            grp_sales: 0,
                            personal_sales: 0,
                            total_sales: 0
                        }

                        if(element.avatar_path == null) {
                            if(element.lname == null && element.fname == null) {
                                child.avatar_name = 'BW';
                            }
                            else {
                                child.avatar_name = '';

                                if(element.fname != null) {
                                    child.avatar_name += (element.fname).charAt(0).toUpperCase();
                                }

                                if(element.lname != null) {
                                    child.avatar_name += (element.lname).charAt(0).toUpperCase();
                                }
                            }
                        }

                        response.data.count.every(element2 => {
                            if(child.code == element2.referral_code) {
                                child.num_grp_children = parseInt(element2.grp_ba_count) + parseInt(element2.grp_be_count) + parseInt(element2.grp_bm_count);

                                return false;
                            }

                            return true;
                        });

                        response.data.sales.every(element3 => {
                            if(child.code == element3.referral_code) {
                                child.personal_sales = element3.personal_sales;
                                child.grp_sales = element3.total_grp_sales;
                                child.total_sales = parseFloat(element3.personal_sales) + parseFloat(element3.total_grp_sales);

                                return false;
                            }

                            return true;
                        });

                        this.secondBDs.push(child);
                    });

                    this.secondBDDetailsLoading = false;

                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
        },


        init() {
            this.getGroupOverview();
            this.getGroupDetails();
            this.getFirstBDOverview();
            this.getFirstBDDetails();
            this.getSecondBDOverview();
            this.getSecondBDDetails();
        },
    },

    created() {
        document.title = this.title;
        this.init();
        this.topMenu = 0;
    }
}
</script>
