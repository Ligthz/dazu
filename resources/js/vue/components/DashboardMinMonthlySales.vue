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
                Monthly Target
                <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                        <v-icon
                            v-bind="attrs"
                            v-on="on" 
                            color="grey lighten-1" 
                            class="ml-2"
                            v-bind:class="{ 'success--text' : parseFloat(data.sales) >= parseFloat(data.target) }"
                        >
                            mdi-check-circle
                        </v-icon>
                    </template>
                    <div>
                        <span class="mr-1">
                            <v-icon small color="success" v-if="parseFloat(data.sales) >= parseFloat(data.target)">mdi-check</v-icon>
                            <v-icon small v-else color="error">mdi-close</v-icon>
                        </span>
                        <span class="text-xs">
                            Minimum monthly personal sales: RM{{ parseFloat(data.target) }}
                        </span>
                    </div>
                </v-tooltip>
            </div>
            <div class="text-xxs-bold text--secondary">
                Last Update: {{ data.last_update }}
            </div>            
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            Minimum monthly personal sales to get basic monthly commisssion.
        </v-card-subtitle>

        <v-container fluid>
            <v-row>
                <v-col cols="12">
                    <div id="chart" class="pb-3 pb-lg-5 pb-xl-5">
                        <apexchart type="radialBar" height="240" :options="radialOption" :series="radialData"></apexchart>
                    </div>
                    <v-container fluid>
                        <v-row>
                            <v-col cols="12" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="primary--text">Personal Sales</span>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ data.sales }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ data.target }}</v-card-text>
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
        radialData: function() {
            let dataLocale = [];

            if (this.data.target == 0) {
                dataLocale[0] = 100;
            }
            else if(this.data.sales == 0) {
                dataLocale[0] = 0;
            }
            else {
                dataLocale[0] = parseFloat(this.data.sales) / parseFloat(this.data.target) * 100;
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
                labels: ['Min Monthly Personal Sales'],
            };

            return chartOptions;
        }
    }
}
</script>
