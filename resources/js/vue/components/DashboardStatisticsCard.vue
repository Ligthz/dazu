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

        <v-card-title class="display-sm-bold pa-0 pb-4">
            {{ statisticGeneral.title }}
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            <span class="me-1">
				{{ statisticGeneral.subtitle }}
				<span class="success--text font-weight-bold">{{ percentageData.percentage }}</span> 
				{{ statisticGeneral.subtitle2 }}
			</span>
			<span>{{ statisticGeneral.subtitle3 }}</span>
        </v-card-subtitle>

		<div class="flex-grow-1 d-flex align-flex-end pa-0 pt-16">
			<v-row>
				<v-col
					v-for="data in statisticsData"
					:key="data.title"
					cols="6"
					md="3"
					class="d-flex align-end mb-1"
				>
					<v-avatar
						size="44"
						:color="resolveStatisticsIconVariation (data.title).color"
						rounded
						class="elevation-1"
					>
						<v-icon
							dark
							color="white"
							size="30"
						>
							{{ resolveStatisticsIconVariation (data.title).icon }}
						</v-icon>
					</v-avatar>

					<div class="ms-3">
						<p class="text-xs mb-0">
							{{ data.title }}
						</p>
						<h3 class="text-xl font-weight-semibold">
							<span v-if="data.title == 'Sales'">RM </span>{{ data.total }}
						</h3>
					</div>
				</v-col>
			</v-row>
		</div>
	</v-card>
</template>

<script>

export default {
  props: {
    statisticGeneral: {
      type: Object,
      default: () => {},
    },
    percentageData: {
      type: Object,
      default: () => {},
    },
    statisticsData: {
      type: Array,
      default: () => [],
    },
	loading: {
		type: Boolean,
		default: true
	}
  },
  data: () => {
    return {
      resolveStatisticsIconVariation: data => {
        if (data === 'Sales') return { icon: 'mdi-currency-usd', color: 'red' }
        if (data === 'Customers') return { icon: 'mdi-account-outline', color: 'success' }
        if (data === 'Products') return { icon: 'mdi-label-outline', color: 'warning' }
        if (data === 'Orders') return { icon: 'mdi-trending-up', color: 'info' }

        return { icon: 'mdi-account-outline', color: 'success' }
      }
    }
  }
}
</script>
