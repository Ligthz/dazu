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
            {{ rankingGeneral.title }}
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            <span class="me-1">
				{{ rankingGeneral.subtitle }}
			</span>
			<span>{{ rankingGeneral.subtitle2 }}</span>
        </v-card-subtitle>

		<v-card-text
			v-if="rankingData.length == 0"
			class="display-sm-bold text-center text--disabled py-10 px-0">
			No Data
		</v-card-text>

		<v-card-text
			v-else
			class="py-16 px-0"
		>
			<div
				v-for="(data,index) in rankingData"
				:key="index"
				:class="`d-flex align-start ${index >0 ? 'mt-8':''}`"
			>
				<v-avatar
					rounded
					size="38"
					color="#5e56690a"
					class="me-4"
				>
					<v-icon :color="data.color">
						{{ data.avatar }}
					</v-icon>
				</v-avatar>

				<div class="d-flex align-start flex-grow-1 flex-wrap">
					<v-col cols="8" class="pa-0 pr-1">
						<h4 class="font-weight-medium">
							{{ data.title }}
						</h4>
					</v-col>

					<v-spacer></v-spacer>

					<v-col class="pa-0 text-end" cols="4">
						<p class="text--primary text-sm-bold font-weight-medium mb-1">
							{{ data.amount }}
							<span class="text-xxs text--secondary">units</span>
						</p>
						<p class="text--primary text-sm-bold mb-1">
							{{ data.number }}
						</p>
						<v-progress-linear
							rounded
							:value="data.progress"
							:color="data.color"
						></v-progress-linear>
					</v-col>
				</div>
			</div>
		</v-card-text>
	</v-card>
</template>

<script>
import AppBar from './AppBar.vue'

export default {
  components: { AppBar },
  props: {
    rankingGeneral: {
      type: Object,
      default: () => {}
    },
    rankingData: {
      type: Array,
      default: () => []
    },
	loading: {
		type: Boolean,
		default: true
	}
  },
  data: () => {
    return {
      totalEarning: [
        {
          avatar: 'mdi-crown',
          title: 'Trofit',
          subtitle: 'Trofit',
          earning: '$24,895.65',
          progress: '85',
          color: 'primary',
        },
        {
          avatar: 'mdi-crown',
          title: 'Trofit',
          subtitle: 'Trofit',
          earning: '$8,6500.20',
          progress: '65',
          color: 'rgba(2, 177, 119, 0.85)',
        },
        {
          avatar: 'mdi-crown',
          title: 'Trofit',
          subtitle: 'Trofit',
          earning: '$1,2450.80',
          progress: '30',
          color: 'rgba(33, 150, 243, 0.85)',
        }
      ]
    }
  }
}
</script>
