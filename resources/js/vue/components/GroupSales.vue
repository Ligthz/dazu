<template>
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
                        :value="overviewLoading"
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
                                    RM {{ salesOverview.total_sales }}
                                </v-card-text>
                                <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 px-3">Accumulated Sales</v-card-subtitle>
                            </v-col>
                        </v-row>

                        <v-row class="pa-5">
                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    RM {{ salesOverview.ba_sales }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TG Sales</v-card-subtitle>
                            </v-col>

                            <v-divider vertical class="my-2"></v-divider>

                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    RM {{ salesOverview.be_sales }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TE Sales</v-card-subtitle>
                            </v-col>

                            <v-divider vertical class="my-2"></v-divider>

                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    RM {{ salesOverview.bm_sales }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TP Sales</v-card-subtitle>
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
                        :value="overviewLoading"
                    >
                        <v-progress-circular
                            indeterminate
                            size="58"
                            width="5"
                            color="rgba(0, 0, 0, 0.2)"
                        ></v-progress-circular>
                    </v-overlay>

                    <v-card-title class="display-sm-bold py-4 px-6">
                        Members
                    </v-card-title>

                    <v-container fluid>
                        <v-row>
                            <v-col class="pb-5">
                                <v-card-text  class="success--text display-md-bold text-center pa-3">
                                    {{ salesOverview.total_count }}
                                </v-card-text>
                                <v-card-subtitle class="text-xs-bold text--disabled text-center py-0 px-3">Number of Members</v-card-subtitle>
                            </v-col>
                        </v-row>

                        <v-row class="pa-5">
                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    {{ salesOverview.ba_count }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TG Members</v-card-subtitle>
                            </v-col>

                            <v-divider vertical class="my-2"></v-divider>

                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    {{ salesOverview.be_count }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TE Members</v-card-subtitle>
                            </v-col>

                            <v-divider vertical class="my-2"></v-divider>

                            <v-col>
                                <v-card-text  class="text--disabled text-sm-bold text-center pa-0 pb-2">
                                    {{ salesOverview.bm_count }}
                                </v-card-text>
                                <v-card-subtitle class="text-xxs-bold text--disabled text-center pa-0">TP Members</v-card-subtitle>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card>
            </v-col>
        </v-row>


        <v-row>
            <v-col cols="12" class="px-0 py-2">
                <v-card
                    class="rounded-0"
                >
                    <v-card-title class="display-sm-bold px-7 py-5">
                        Latest Members
                    </v-card-title>
                    <v-card-actions class="text-sm px-7 d-block d-md-flex d-lg-flex d-xl-flex">
                        <v-spacer></v-spacer>

                        <v-text-field
                            v-model.trim="search"
                            append-icon="mdi-magnify"
                            label="Search"
                            single-line
                            hide-details
                            class="pa-2 mt-0"
                        ></v-text-field>
                    </v-card-actions>
                    <v-container fluid>
                        <v-row class="py-5 px-7">
                            <v-col
                                cols="12"
                                class="pa-0"
                            >
                                <v-card
                                    outlined
                                    class="py-5 rounded-0"
                                >
                                    <v-data-table
                                        :headers="headers"
                                        :items="children"
                                        :loading="detailsLoading"
                                        loading-text="Loading . . ."
                                        no-data-text="No Data."
                                        :search="search"
                                        no-results-text="No Result."
                                    >
                                        <template  v-slot:[`item.personal_sales`]="{ item }">
                                            <span>RM {{ parseFloat(item.personal_sales).toFixed(2) }}</span>
                                        </template>
                                    </v-data-table>

                                    <v-overlay
                                        absolute
                                        color="white"
                                        opacity="0.36"
                                        :value="detailsLoading"
                                    ></v-overlay>
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
export default {
    props: [ 'overviewLoading', 'detailsLoading', 'salesOverview', 'children'],
    data: ()=> {
        return {
            headers: [
                {
                    text: 'Member #',
                    align: 'start',
                    value: 'code',
                },
                {
                    text: 'Member Name',
                    align: 'start',
                    value: 'name',
                },
                {
                    text: 'Member Phone',
                    align: 'start',
                    value: 'contact',
                },
                {
                    text: 'Level',
                    align: 'start',
                    value: 'role_name'
                },
                {
                    text: 'Personal Sales',
                    align: 'start',
                    value: 'personal_sales',
                },
                {
                    text: 'Mentor',
                    align: 'start',
                    value: 'mentor',
                }
            ],
            search: ''
        }
    }
}
</script>
