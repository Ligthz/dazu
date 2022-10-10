<template>
    <div>
        <v-navigation-drawer
            fixed
            permanent
            color="white"
            class="mt-12"
            v-if="!isMobile"
        >
            <nav-bar-list :active="active"/>
        </v-navigation-drawer>

        <v-navigation-drawer
            v-model="localeDrawer"
            app
            right
            color="white"
            v-if="isMobile"
            class="nv-drawer"
        >
            <nav-bar-list :active="active" @close="closeDrawer"/>

            <v-list
                nav
                dense
                class="px-0"
            >
                <v-divider></v-divider>
                <v-list-item-group
                    class="mt-2 mx-2 text-center"
                >
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                v-bind="attrs"
                                v-on="on"
                                icon
                                :input-value="toShop"
                                active-class="default-color"
                                :href="getLoginUrl"
                                @click="toShop = true"
                            >
                                <v-icon>mdi-storefront-outline</v-icon>
                            </v-btn>

                        </template>
                        <span>{{ $t('main_nav.shop') }}</span>
                    </v-tooltip>
                </v-list-item-group>
            </v-list>
        </v-navigation-drawer>
    </div>
</template>

<script>
import NavBarList from './NavBarList.vue';
export default {
  components: { NavBarList },
    props: [ 'active', 'drawer' ],
    data: ()=> {
        return {
            localeDrawer: false,
            toShop: false,
        }
    },
    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        },
        getLoginUrl: function(){
            return this.$url + "/login";
        }
    },
    watch: {
        drawer: function() {
            this.reset();
        },

        localeDrawer: function() {
            this.$emit('closed', this.localeDrawer);
        }
    },
    methods: {
        reset() {
            this.localeDrawer = this.drawer;
        },

        closeDrawer() {
            this.localeDrawer = false;
            this.$emit('closed', this.localeDrawer);
        },

        backToShop() {
            window.location.href = "https://sb.trofitshop.com/login";
        }
    }
}
</script>
