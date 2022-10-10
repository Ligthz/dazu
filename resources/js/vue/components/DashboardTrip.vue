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
                Trip Incentives
            </div>
            <div class="text-xxs-bold text--secondary">
                {{ data.start_date }} ~ {{ data.details.end_date }}
            </div>
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            Yearly count. Max of 2pax
        </v-card-subtitle>

        <v-container fluid>
            <v-row>
                <v-col cols="12">
                    <div id="chart">
                        <div class="pax-wrapper trip">
                            <v-card-subtitle class="text-center display-md-bold pa-0" v-bind:class="{ 'success--text': data.details.num_of_pax >= 1 }">
                                {{ data.details.num_of_pax }}
                                <span class="text-xxs">pax</span>
                            </v-card-subtitle>
                            <v-card-text class="text-center text-xxs text--secondary pa-0">Eligible</v-card-text>
                        </div>
                        <apexchart type="radialBar" height="220" :options="radialOption" :series="radialData"></apexchart>
                    </div>
                    <v-container fluid>
                        <v-row>
                            <v-col cols="4" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="color-blue">Personal Sales</span>
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
                                                <v-icon small v-if="parseFloat(data.details.personal_sales) >= parseFloat(data.details.trip_personal_one_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">1 pax: RM{{ commafy(parseFloat(data.details.trip_personal_one_person)) }}</span>
                                        </div>
                                        <div>
                                            <span>
                                                <v-icon small v-if="parseFloat(data.details.personal_sales) >= parseFloat(data.details.trip_personal_two_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">2 pax: RM{{ commafy(parseFloat(data.details.trip_personal_two_person)) }}</span>
                                        </div>
                                    </v-tooltip>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ commafy(parseFloat(data.details.personal_sales)) }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ commafy(parseFloat(data.details.trip_personal_two_person)) }}</v-card-text>
                                </div>
                            </v-col>

                            <v-col cols="4" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="color-green">Group Sales</span>
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
                                                <v-icon small v-if="parseFloat(data.details.group_sales) >= parseFloat(data.details.trip_group_one_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">1 pax: RM{{ commafy(parseFloat(data.details.trip_group_one_person)) }}</span>
                                        </div>
                                        <div>
                                            <span>
                                                <v-icon small v-if="parseFloat(data.details.group_sales) >= parseFloat(data.details.trip_group_two_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">2 pax: RM{{ commafy(parseFloat(data.details.trip_group_two_person)) }}</span>
                                        </div>
                                    </v-tooltip>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ commafy(parseFloat(data.details.group_sales)) }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ commafy(parseFloat(data.details.trip_group_two_person)) }}</v-card-text>
                                </div>
                            </v-col>

                            <v-col cols="4" class="text-center px-2 d-flex flex-column">
                                <v-card-subtitle class="text-xxs mb-1 pa-0">
                                    <span class="primary--text">TM Sales</span>
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
                                                <v-icon small v-if="parseFloat(data.details.all_bd_sales) >= parseFloat(data.details.trip_bd_one_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">1 pax: RM{{ commafy(parseFloat(data.details.trip_bd_one_person)) }}</span>
                                        </div>
                                        <div>
                                            <span>
                                                <v-icon small v-if="parseFloat(data.details.all_bd_sales) >= parseFloat(data.details.trip_bd_two_person)" color="success">mdi-check</v-icon>
                                                <v-icon small v-else color="error">mdi-close</v-icon>
                                            </span>
                                            <span class="text-xs">2 pax: RM{{ commafy(parseFloat(data.details.trip_bd_two_person)) }}</span>
                                        </div>
                                    </v-tooltip>
                                </v-card-subtitle>
                                <div class="flex-grow-1 d-flex flex-column justify-end">
                                    <v-card-text class="display-sm-bold pa-0"><span class="text-xxs text--secondary mr-1">RM</span>{{ commafy(parseFloat(data.details.all_bd_sales)) }}</v-card-text>
                                    <v-card-text class="text-xxs text--secondary pa-0">/ RM{{ commafy(parseFloat(data.details.trip_bd_two_person)) }}</v-card-text>
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
            let trip_data = [];

            if(parseFloat(this.data.details.personal_sales) == 0 || parseFloat(this.data.details.trip_personal_two_person) == 0) {
                trip_data[0] = 0;
            }
            else {
                trip_data[0] = parseFloat(this.data.details.personal_sales) / parseFloat(this.data.details.trip_personal_two_person) * 100;
            }

            if(parseFloat(this.data.details.group_sales) == 0 || parseFloat(this.data.details.trip_group_two_person) == 0) {
                trip_data[1] = 0;
            }
            else {
                trip_data[1] = parseFloat(this.data.details.group_sales) / parseFloat(this.data.details.trip_group_two_person) * 100;
            }

            if(parseFloat(this.data.details.all_bd_sales) == 0 || parseFloat(this.data.details.trip_bd_two_person) == 0) {
                trip_data[2] = 0;
            }
            else {
                trip_data[2] = parseFloat(this.data.details.all_bd_sales) / parseFloat(this.data.details.trip_bd_two_person) * 100;
            }

            return trip_data;
        },

        radialOption: function() {
            let chartOptions = {
                chart: {
                    height: 220,
                    type: 'radialBar',
                },
                plotOptions: {
                    radialBar: {
                        track: {
                            background: ['rgba(33, 150, 243, 0.25)', 'rgba(0, 227, 150, 0.25)', this.$primaryLightColor]
                        },
                        dataLabels: {
                            name: {
                                show: false,
                                fontSize: '22px',
                                formatter: function (val) {
                                    return '';
                                }
                            },
                            value: {
                                show: false,
                                fontSize: '16px',
                                formatter: function (val) {
                                    return ''
                                }
                            },
                            total: {
                                show: false,
                                formatter: function (w) {
                                    // By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
                                    return ''
                                }
                            }
                        }
                    }
                },
                fill: {
                    colors: [ 'rgba(33, 150, 243, 0.85)', 'rgba(0, 227, 150, 0.85)', this.$primaryColor]
                },
                stroke: {
                    lineCap: 'round'
                },
                labels: ['Personal Sales', 'Group Sales', 'TM Sales'],
            };

            return chartOptions;
        }

    },

    methods: {
        commafy( num ) {
			var str = num.toString().split('.');
			if (str[0].length >= 5) {
				str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1,');
			}
			if (str[1] && str[1].length >= 5) {
				str[1] = str[1].replace(/(\d{3})/g, '$1 ');
			}
			return str.join('.');
		}
    }
}
</script>
