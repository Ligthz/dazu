<template>
    <v-card class="fill-height rounded-0 px-6 py-4">
        <v-overlay
            absolute
            color="white"
            opacity="0.36"
            :value="loading"
        >
            <v-progress-circular
                indeterminate
                size="58"
                width="5"
                color="rgba(0, 0, 0, 0.2)"
            ></v-progress-circular>
        </v-overlay>

        <v-card-title class="display-sm-bold pa-0 pb-4 justify-space-between">
            <div>
                Level Upgrade Requirement
                <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                        <v-icon
                            v-bind="attrs"
                            v-on="on" 
                            color="grey lighten-1" 
                            class="ml-2"
                            v-bind:class="{ 'success--text' : data.status }"
                        >
                            mdi-check-circle
                        </v-icon>
                    </template>
                    <div v-if="parseFloat(data.personal.target) != 0">
                        <span class="mr-1">
                            <v-icon small color="success" v-if="parseFloat(data.personal.sales) >= parseFloat(data.personal.target)">mdi-check</v-icon>
                            <v-icon small v-else color="error">mdi-close</v-icon>
                        </span>
                        <span class="text-xs">
                            Personal sales: RM{{ parseFloat(data.personal.target) }}
                        </span>
                    </div>
                    <div v-if="parseFloat(data.group.target) != 0">
                        <span class="mr-1">
                            <v-icon small color="success" 
                                v-if="(parseFloat(data.group.sales) >= parseFloat(data.group.target)) 
                                    && (parseFloat(data.personal.sales) >= parseFloat(data.group.target2))"
                            >mdi-check</v-icon>
                            <v-icon small v-else color="error">mdi-close</v-icon>
                        </span>
                        <span class="text-xs">
                            Group Sales: RM{{ parseFloat(data.group.target) }} + Personal sales: RM{{ parseFloat(data.group.target2) }}
                        </span>
                    </div>
                </v-tooltip>
            </div>
            <div class="text-xxs-bold text--secondary">
                Last Update: {{ data.last_update }}
            </div>
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            Monthly count. Effective on the next day!<br/>Achieve either one requirement below to upgrade your level.
        </v-card-subtitle>

        <v-container fluid>
            <v-row>
                <v-col 
                    cols="12" 
                    class="col-lg-6 col-xl-6"
                    v-if="parseFloat(data.personal.target) != 0" 
                    v-bind:class="{ 'col-lg-12': parseFloat(data.group.target) == 0 }"
                >
                    <div id="chart" class="pb-3 pb-lg-5 pb-xl-5">
                        <apexchart type="radialBar" height="240" :options="radialOption" :series="radialPersonalData"></apexchart>
                    </div>
                    <v-container fluid>
                        <v-row>
                            <v-col cols="12" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="primary--text">Personal Sales</span>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ parseFloat(data.personal.sales) }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ parseFloat(data.personal.target) }}</v-card-text>
                                </div>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-col>
                <v-col
                    cols="12" 
                    class="col-lg-6 col-xl-6"
                    v-if="parseFloat(data.group.target) != 0" 
                    v-bind:class="{ 'col-lg-12' : parseFloat(data.group.target) == 0 }"
                >
                    <div id="chart" class="pb-3 pb-lg-5 pb-xl-5">
                        <apexchart type="radialBar" height="240" :options="radialOption" :series="radialGroupData"></apexchart>
                    </div>
                    <v-container fluid>
                        <v-row>
                            <v-col cols="12" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="primary--text">Group Sales</span>
                                    <v-tooltip top>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-icon
                                                v-bind="attrs"
                                                v-on="on"
                                                x-small
                                                class="info-icon"
                                            >
                                                mdi-information-outline
                                            </v-icon>
                                        </template>
                                        <div>
                                            <span>
                                                <v-icon small v-if="parseFloat(data.personal.sales) >= parseFloat(data.group.target2)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">Personal Sales: RM{{ parseFloat(data.group.target2) }}</span>
                                        </div>
                                    </v-tooltip>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ parseFloat(data.group.sales) }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ parseFloat(data.group.target) }}</v-card-text>
                                </div>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-col>
            </v-row>
        </v-container>
    </v-card>
</template>

<script>
export default {
    props: [ 'loading', 'data' ],
    data: ()=> {
        return {

        }
    },

    computed: {
        radialPersonalData: function() {
            let dataLocale = [];

            if (this.data.personal.target == 0) {
                dataLocale[0] = 100;
            }
            else if(this.data.personal.sales == 0) {
                dataLocale[0] = 0;
            }
            else {
                dataLocale[0] = parseFloat(this.data.personal.sales) / parseFloat(this.data.personal.target) * 100;
            }

            return dataLocale;
        },

        radialGroupData: function() {
            let dataLocale = [];

            if (this.data.group.target == 0) {
                dataLocale[0] = 100;
            }
            else if(this.data.group.sales == 0) {
                dataLocale[0] = 0;
            }
            else {
                dataLocale[0] = parseFloat(this.data.group.sales) / parseFloat(this.data.group.target) * 100;
            }

            return dataLocale;
        },

        radialOption: function() {
            let chartOptions = {
                chart: {
                    height: 240,
                    type: 'radialBar'
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 135,
                        track: {
                            background: this.$primaryLightColor,
                            startAngle: -135,
                            endAngle: 135
                        },
                        dataLabels: {
                            name: {
                                show: false
                            },
                            value: {
                                show: false
                            },
                            total: {
                                show: false
                            }
                        }
                    }
                },
                fill: {
                    colors: this.$primaryColor
                },
                stroke: {
                    lineCap: 'round'
                },
                labels: [''],
            };

            return chartOptions;
        }
    }
}
</script>
