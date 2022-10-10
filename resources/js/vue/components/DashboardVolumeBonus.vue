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
            <div>3 Level Volume Bonus</div>
            <div class="text-xxs-bold text--secondary">
                Last Update: {{ data.date }} 23:59:59
            </div>   
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            Monthly count. Boost monthly sales and grow with your team to get extra bonuses for the month
        </v-card-subtitle>

        <v-container fluid>
            <v-row>
                <v-col 
                    cols="12" 
                    lg="4"
                    xl="4"
                    class="pl-0 pt-5 pb-0 py-lg-12 py-xl-12 pr-lg-5 pr-xl-5"
                >
                    <div class="py-1">
                        <div class="text-sm d-flex">
                            <div class="mr-2">
                                <v-icon v-if="data.personal_vol_kpi_hit == 1" color="success">mdi-check</v-icon>
                                <v-icon v-else color="grey">mdi-close</v-icon>
                            </div>
                            <div class="line-height-normal">
                                <span v-if="data.personal_vol_kpi_hit == 1" class="success--text">Personal Volume Bonus {{ parseFloat(data.personal_volume_bonus) * 100 }}%</span>
                                <span v-else class="text--disabled">Personal Volume Bonus {{ parseFloat(data.personal_volume_bonus) * 100 }}%</span>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-icon
                                            v-bind="attrs"
                                            v-on="on"
                                            small
                                            class="info-icon"
                                            v-bind:class="{ 'success--text' : data.personal_vol_kpi_hit == 1 }"
                                        >
                                            mdi-information-outline
                                        </v-icon>
                                    </template>
                                    <div>
                                        <span>
                                            <v-icon small v-if="data.personal_vol_kpi_hit == 1" color="success">mdi-check</v-icon>
                                            <v-icon small v-else color="error">mdi-close</v-icon>
                                        </span>
                                        <span class="text-xs">Personal Sales: RM{{ parseFloat(data.personal_vol_kpi) }}</span>
                                    </div>
                                </v-tooltip>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="text-sm d-flex">
                            <div class="mr-2">
                                <v-icon v-if="data.first_bd_kpi_hit == 1" color="success">mdi-check</v-icon>
                                <v-icon v-else color="grey">mdi-close</v-icon>
                            </div>
                            <div class="line-height-normal">
                                <span v-if="data.first_bd_kpi_hit == 1" class="success--text">1st Level Volume Bonus {{ parseFloat(data.first_bd_bonus) * 100 }}%</span>
                                <span v-else class="text--disabled">1st Level Volume Bonus {{ parseFloat(data.first_bd_bonus) * 100 }}%</span>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-icon
                                            v-bind="attrs"
                                            v-on="on"
                                            small
                                            class="info-icon"
                                            v-bind:class="{ 'success--text' : data.first_bd_kpi_hit == 1 }"
                                        >
                                            mdi-information-outline
                                        </v-icon>
                                    </template>
                                    <div>
                                        <span>
                                            <v-icon small v-if="data.personal_vol_kpi_hit == 1" color="success">mdi-check</v-icon>
                                            <v-icon small v-else color="error">mdi-close</v-icon>
                                        </span>
                                        <span class="text-xs">Personal Sales: RM{{ parseFloat(data.personal_vol_kpi) }}</span>
                                    </div>
                                    <div>
                                        <span>
                                            <v-icon small v-if="(parseFloat(data.personal_volume_sales) >= parseFloat(data.first_bd_kpi)) ||
                                                data.first_bd_kpi_hit == 1" color="success"
                                            >
                                                mdi-check
                                            </v-icon>
                                            <v-icon small v-else color="error">mdi-close</v-icon>
                                        </span>
                                        <span class="text-xs">Personal Volume Sales: RM{{ parseFloat(data.first_bd_kpi) }}</span>
                                    </div>
                                </v-tooltip>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="text-sm d-flex">
                            <div class="mr-2">
                                <v-icon v-if="data.second_bd_kpi_hit == 1" color="success">mdi-check</v-icon>
                                <v-icon v-else color="grey">mdi-close</v-icon>
                            </div>
                            <div class="line-height-normal">
                                <span v-if="data.second_bd_kpi_hit == 1" class="success--text">2nd Level Volume Bonus {{ parseFloat(data.second_bd_bonus) * 100 }}%</span>
                                <span v-else class="text--disabled">2nd Level Volume Bonus {{ parseFloat(data.second_bd_bonus) * 100 }}%</span>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-icon
                                            v-bind="attrs"
                                            v-on="on"
                                            small
                                            class="info-icon"
                                            v-bind:class="{ 'success--text' : data.second_bd_kpi_hit == 1 }"
                                        >
                                            mdi-information-outline
                                        </v-icon>
                                    </template>
                                    <div>
                                        <span>
                                            <v-icon small v-if="data.personal_vol_kpi_hit == 1" color="success">mdi-check</v-icon>
                                            <v-icon small v-else color="error">mdi-close</v-icon>
                                        </span>
                                        <span class="text-xs">Personal Sales: RM{{ parseFloat(data.personal_vol_kpi) }}</span>
                                    </div>
                                    <div>
                                        <span>
                                            <v-icon small v-if="(parseFloat(data.personal_volume_sales) >= parseFloat(data.second_bd_kpi)) ||
                                                data.second_bd_kpi_hit == 1" color="success"
                                            >
                                                mdi-check
                                            </v-icon>
                                            <v-icon small v-else color="error">mdi-close</v-icon>
                                        </span>
                                        <span class="text-xs">Personal Volume Sales: RM{{ parseFloat(data.second_bd_kpi) }}</span>
                                    </div>
                                </v-tooltip>
                            </div>
                        </div>
                    </div>
                </v-col>

                <v-col
                    cols="12" 
                    lg="8"
                    xl="8"
                    class="pt-0 pt-lg-3 pt-xl-3 pl-lg-5 pl-xl-5"
                >
                    <v-container fluid>
                        <v-row>
                            <v-col 
                                cols="12" 
                                class="col-lg-6 col-xl-6 py-0"
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
                                                <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ parseFloat(data.personal_sales) }}</v-card-text>
                                                <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ parseFloat(data.personal_vol_kpi) }}</v-card-text>
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </v-col>
                                        
                            <v-col
                                cols="12" 
                                class="col-lg-6 col-xl-6 py-0"
                            >
                                <div id="chart" class="pb-3 pb-lg-5 pb-xl-5">
                                    <apexchart type="radialBar" height="240" :options="radialOption" :series="radialGroupData"></apexchart>
                                </div>
                                <v-container fluid>
                                    <v-row>
                                        <v-col cols="12" class="text-center px-2 d-flex flex-column">
                                            <v-card-subtitle class="text-xxs mb-1 pa-0">
                                                <span class="primary--text">Personal Volume Sales</span>
                                            </v-card-subtitle>
                                            <div class="flex-grow-1 d-flex flex-column justify-end">
                                                <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ parseFloat(data.personal_volume_sales) }}</v-card-text>
                                                <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ parseFloat(data.second_bd_kpi) }}</v-card-text>
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-container>
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

            if (parseFloat(this.data.personal_vol_kpi) == 0) {
                dataLocale[0] = 100;
            }
            else if(parseFloat(this.data.personal_sales) == 0) {
                dataLocale[0] = 0;
            }
            else {
                dataLocale[0] = parseFloat(this.data.personal_sales) / parseFloat(this.data.personal_vol_kpi) * 100;
            }

            return dataLocale;
        },

        radialGroupData: function() {
            let dataLocale = [];

            if (parseFloat(this.data.second_bd_kpi) == 0) {
                dataLocale[0] = 100;
            }
            else if(parseFloat(this.data.personal_volume_sales) == 0) {
                dataLocale[0] = 0;
            }
            else {
                dataLocale[0] = parseFloat(this.data.personal_volume_sales) / parseFloat(this.data.second_bd_kpi) * 100;
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
        },
    }
}
</script>
