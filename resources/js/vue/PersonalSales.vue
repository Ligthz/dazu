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
                        <self-purchase />
                    </v-tab-item>
                    <v-tab-item>
                        <referral-sales />
                    </v-tab-item>
                </v-tabs-items>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
import AppBar from './components/AppBar.vue';
import NavBar from './components/NavBar.vue';
import ReferralSales from './components/ReferralSales.vue';
import SelfPurchase from './components/SelfPurchase.vue';
export default {
    components: {
        AppBar,
        NavBar,
        SelfPurchase,
        ReferralSales
    },
    data: ()=> {
        return {
            drawer: false,
            title: 'Personal Sales - Trofit Partner',
            mainMenu: 1,
            subMenus: [
                {
                    icon: 'mdi-currency-usd',
                    title: 'Self Purchases',
                },
                {
                    icon: 'mdi-currency-usd',
                    title: 'Referral Sales',
                },
            ],
            sideMenu: 0,

        }
    },

    methods: {
        openDrawer(value) {
            this.drawer = value;
        },

        closeDrawer(value) {
            this.drawer = value;
        }
    },

    created() {
        document.title = this.title;
    }
}
</script>
