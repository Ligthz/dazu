<template>
    <v-list
        nav
    >
        <v-btn icon large class="mb-2" v-if="isMobile" @click="closeDrawer">
            <v-icon>mdi-close</v-icon>
        </v-btn>

        <v-list-item-group
            v-model="localeActive"
            color="default"
            mandatory
        >
            <v-list-item
                class="mb-0"
                :href="getLink(0)"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-home</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.home') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                class="mb-0"
                :href="getLink(1)"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-account-outline</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.personal') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                class="mb-0"
                :href="getLink(2)"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-account-multiple-outline</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.direct_child') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                class="mb-0"
                :href="getLink(3)"
                v-if="$role == 4"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-numeric</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.bd') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                class="mb-0"
                :href="getLink(4)"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-currency-usd</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.payout') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                v-if="isMobile"
                class="mb-0"
                :href="getLink(5)"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-cog-outline</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.setting') }}</v-list-item-title>
            </v-list-item>

            <v-list-item
                v-if="isMobile"
                class="mb-0"
                @click="signOut"
            >
                <v-list-item-icon class="text-sm">
                    <v-icon>mdi-logout</v-icon>
                </v-list-item-icon>
                <v-list-item-title class="text-sm">{{ $t('main_nav.logout') }}</v-list-item-title>
                <v-progress-circular
                    v-if="loggingOut"
                    indeterminate
                    color="grey"
                    :height="4"
                ></v-progress-circular>
            </v-list-item>
        </v-list-item-group>
    </v-list>
</template>

<script>
export default {
    props: [ 'active' ],
    data: ()=> {
        return {
            loggingOut: false,
            link: [
                '',
                '/personal-sales',
                '/direct-child-sales',
                '/tm-sales',
                '/payouts',
                '/account-settings'
            ],
            localeActive: 0
        }
    },
    computed: {
        isMobile: function() {
            return this.$vuetify.breakpoint.smAndDown;
        }
    },
    methods: {
        init() {
            this.localeActive = this.active;
        },

        getLink(i) {
            return '/' + this.$i18n.locale + this.link[i];
        },

        closeDrawer() {
            this.$emit('close', false);
        },

        signOut() {
            this.loggingOut = true;
            this.$logout();
        }
    },

    mounted() {
        this.init();
    }
}
</script>
