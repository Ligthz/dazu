import { Doughnut, mixins } from 'vue-chartjs';
const { reactiveProp } = mixins;

export default {
    data: ()=> {
        return {
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        }
    },
    extends: Doughnut,
    mixins: [reactiveProp],
    props: ['chartData'],
    mounted () {
        // this.chartData is created in the mixin.
        // If you want to pass options please create a local options object
        this.renderChart(this.chartData, this.options)
    }
}