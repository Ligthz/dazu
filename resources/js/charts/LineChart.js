import { Line, mixins } from 'vue-chartjs';
const { reactiveProp } = mixins;

export default {
    data: ()=> {
        return {
            options: {
                legend: {
                    display: false
                },
                tooltips: {
                    callbacks: {
                        title: function (tooltipItem, data) {
                            return data.datasets[tooltipItem[0].datasetIndex].label[tooltipItem[0].index];
                        },
                        label: function (tooltipItem, data) {
                            return tooltipItem.value;
                        }
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            callback: function(value, index, values) {
                                return ' ';
                            }
                        },

                        gridLines: {
                            display: false
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        },

                        gridLines: {
                            display: false
                        }
                    }]
                },
                elements: {
                    point:{
                        radius: 2
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        }
    },
    extends: Line,
    mixins: [reactiveProp],
    props: ['chartData', 'manualAlert'],
    mounted () {
        // this.chartData is created in the mixin.
        // If you want to pass options please create a local options object
        this.renderChart(this.updatedData, this.options)
    },
    computed: {
        updatedData: function() {
          return this.chartData;
        }
    },
    watch: {
        manualAlert: function() {
            this.$data._chart.data = this.chartData;
            this.$data._chart.update();
        }
    }
}