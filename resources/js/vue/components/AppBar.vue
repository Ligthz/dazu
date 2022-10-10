<template>
    <v-app-bar
        app
        dense
        flat
        class="v-bar--underline"
    >
        <div class="pl-1">
            <v-img
                width="148px"
                src="/images/trofit_logo.png"
                class="mx-auto"
            />
        </div>

        <v-spacer></v-spacer>

        <!--<language-switcher />-->

        <div v-if="!isMobile" class="mr-4">
            <v-skeleton-loader
                v-if="avatar_loading"
                type="avatar"
                class="avatar-small"
            ></v-skeleton-loader>

            <v-tooltip bottom v-else>
                <template v-slot:activator="{ on, attrs }">
                    <v-btn
                        v-bind="attrs"
                        v-on="on"
                        icon
                        width="32px"
                        height="32px"
                        :input-value="toShop"
                        active-class="secondary-color"
                        class="default-color no-background-hover secondary-hover"
                        :href="getLoginUrl"
                        @click="toShop = true"
                    >
                        <v-icon size="30px">mdi-storefront-outline</v-icon>
                    </v-btn>
                </template>
                <span>{{ $t('main_nav.shop') }}</span>
            </v-tooltip>
        </div>

        <!--<v-tooltip bottom v-if="!isMobile">
            <template v-slot:activator="{ on, attrs }">
                <v-btn
                    v-bind="attrs"
                    v-on="on"
                    icon
                    large
                    :input-value="notification"
                    active-class="default-color"
                    class="mr-3 no-background-hover"
                    @click="notification = true"
                >
                    <v-icon>mdi-bell-outline</v-icon>
                </v-btn>
            </template>
            <span>{{ $t('main_nav.notice') }}</span>
        </v-tooltip>-->

        <div
            v-if="!isMobile"
            class="mr-2"
        >
            <v-skeleton-loader
                v-if="avatar_loading"
                type="avatar"
                class="avatar-small"
            ></v-skeleton-loader>

            <v-menu
                v-else
                bottom
                min-width="192px"
                rounded
                offset-y
            >
                <template v-slot:activator="{ on }">
                    <v-btn
                        icon
                        v-on="on"
                        width="32px"
                        height="32px"
                        class="no-background-hover"
                    >
                        <v-avatar
                            size="32"
                            color="primary"
                        >
                            <span class="white--text text-xs" v-if="avatar_path == null">{{ avatar_name }}</span>
                            <v-img :src="avatar_path" v-else></v-img>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-card>
                    <v-list-item-content class="pa-0">
                        <div>
                            <v-btn
                                depressed
                                block
                                text
                                height="60px"
                                href="/account-settings"
                                class="text-xs-bold text--secondary justify-start"
                            >
                                <v-icon size="22" class="mr-2">mdi-cog-outline</v-icon>
                                Account Settings
                            </v-btn>

                            <v-divider></v-divider>

                            <v-btn
                                depressed
                                block
                                text
                                height="60px"
                                @click="signOut"
                                class="text-xs-bold text--secondary justify-start"
                            >
                                <v-icon size="22" class="mr-2">mdi-logout</v-icon>
                                Logout
                            </v-btn>
                        </div>
                    </v-list-item-content>
                </v-card>
            </v-menu>
        </div>

        <v-app-bar-nav-icon large @click="openDrawer" v-if="isMobile"></v-app-bar-nav-icon>
    </v-app-bar>
</template>

<script>
import LanguageSwitcher from './LanguageSwitcher.vue';
export default {
  components: { LanguageSwitcher },
    props: [ 'drawer' ],
    data: ()=> {
        return {
            localeDrawer: false,
            toShop: false,
            notification: false,
            avatar_name: 'TF',
            avatar_path: null,
            avatar_loading: false
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
    },
    methods: {
        init() {
            this.getPartnerDetails();
        },

        reset() {
            this.localeDrawer = this.drawer;
        },

        openDrawer() {
            this.localeDrawer = true;
            this.$emit('opened', this.localeDrawer);
        },

        signOut() {
            this.$logout();
        },

        getPartnerDetails() {
            this.avatar_loading = true;
            axios.get('/user/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
                    if(response.data.avatar_path == null) {
                        if(response.data.last_name == null && response.data.first_name == null) {
                            this.avatar_name = 'TF';
                        }
                        else {
                            this.avatar_name = '';
                            if(response.data.first_name != null) {
                                this.avatar_name += (response.data.first_name).charAt(0).toUpperCase();
                            }

                            if(response.data.last_name != null) {
                                this.avatar_name += (response.data.last_name).charAt(0).toUpperCase();
                            }
                        }
                    }
                    else {
                        this.avatar_name = response.data.avatar_name;
                        this.avatar_path = response.data.avatar_path;
                    }

                    this.avatar_loading = false;
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
        this.init();
    }
}
</script>
