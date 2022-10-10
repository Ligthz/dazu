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
                    <v-overlay
                        absolute
                        color="white"
                        opacity="0.36"
                        :value="overviewLoading"
                    >
                        <v-progress-circular
                            indeterminate
                            size="58"
                            width="5"
                            color="rgba(0, 0, 0, 0.2)"
                        ></v-progress-circular>
                    </v-overlay>

                    <v-card-title class="display-sm-bold pt-4 pb-8 px-6">
                        Overview
                    </v-card-title>

                    <v-container fluid>
                        <v-row>
                            <v-col class="pb-7">
                                <v-card-text  class="success--text display-md-bold text-center pa-3">
                                    RM {{ salesOverview.sales }}
                                </v-card-text>
                                <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 pb-3 px-3">Accumulated Sales</v-card-subtitle>
                            </v-col>

                            <v-divider vertical class="mt-5 mb-10"></v-divider>

                            <v-col class="pb-10">
                                <v-card-text  class="success--text display-md-bold text-center pa-3">
                                    {{ salesOverview.count }}
                                </v-card-text>
                                <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 pb-3 px-3">Number of Members</v-card-subtitle>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col
                cols="12"
                class="px-0 py-2"
            >
                <v-card
                    class="rounded-0"
                >
                    <v-overlay
                        absolute
                        color="white"
                        opacity="0.36"
                        :value="overviewLoading"
                    >
                        <v-progress-circular
                            indeterminate
                            size="58"
                            width="5"
                            color="rgba(0, 0, 0, 0.2)"
                        ></v-progress-circular>
                    </v-overlay>

                    <v-card-title class="display-sm-bold pb-8 pt-4 px-6">
                        Latest {{ setLevel }} Level TM
                    </v-card-title>

                    <v-list-item-group class="pb-4">
                        <v-list-item
                            v-for="(item, index) in children"
                            :key="index"
                            class="align-start align-lg-center align-xl-center py-3 px-7"
                        >
                            <div class="d-lg-flex d-xl-flex col-4">
                                <v-badge
                                    bottom
                                    bordered
                                    overlap
                                    :color="item.color"
                                    offset-x="32"
                                    offset-y="26"
                                    class="badge-small"
                                >
                                    <v-list-item-avatar class="ml-0 mt-3" size="64" :color="item.color">
                                        <span class="white--text display-md" v-if="item.avatar == null">{{ item.avatar_name }}</span>
                                        <v-img :src="item.avatar" v-else></v-img>
                                    </v-list-item-avatar>
                                </v-badge>

                                <v-list-item-content class="ml-1 ml-lg-4 ml-xl-4">
                                    <v-list-item-title class="text-lg-bold mb-1 title-color">{{ item.name }}</v-list-item-title>
                                    <v-list-item-subtitle class="text-xxs-bold text--disabled">{{ item.code }}</v-list-item-subtitle>
                                    <v-list-item-subtitle class="text-xxs-bold text--disabled">Mentor: {{ item.mentor }}</v-list-item-subtitle>
                                </v-list-item-content>
                            </div>
                            <div class="d-lg-flex d-xl-flex col-8">
                                <v-list-item-content class="subMenu-sales text-right px-2">
                                    <v-list-item-title class="success--text text-md-bold mb-1">RM {{ item.personal_sales }}</v-list-item-title>
                                    <v-list-item-subtitle class="text-xxs-bold text--disabled">Personal Sales</v-list-item-subtitle>
                                </v-list-item-content>
                                <v-list-item-content class="subMenu-sales text-right px-2">
                                    <v-list-item-title class="success--text text-md-bold mb-1">RM {{ item.grp_sales }}</v-list-item-title>
                                    <v-list-item-subtitle class="text-xxs-bold text--disabled">Group Sales</v-list-item-subtitle>
                                </v-list-item-content>
                                <v-list-item-content class="subMenu-sales text-right px-2">
                                    <v-list-item-title class="success--text text-md-bold mb-1">{{ item.num_grp_children }}</v-list-item-title>
                                    <v-list-item-subtitle class="text-xxs-bold text--disabled">Group Members</v-list-item-subtitle>
                                </v-list-item-content>
                            </div>
                        </v-list-item>
                    </v-list-item-group>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    props: [ 'level', 'overviewLoading', 'detailsLoading', 'salesOverview', 'children'],
    computed: {
        setLevel: function () {
            if(this.level == 1) {
                return '1st';
            }
            else if(this.level == 2) {
                return '2nd';
            }
            else {
                return 'Default';
            }
        }
    }

}
</script>
