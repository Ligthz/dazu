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
            	Trofit Crown 👑
			</div>
			<div class="text-xxs-bold text--secondary">
                {{ data.start_date }} ~ {{ data.details.end_date }}
            </div>
        </v-card-title>

        <v-card-subtitle class="text-sm pa-0">
            Yearly count. Be the king and lead your team to the success!
        </v-card-subtitle>

		<v-container fluid>
            <v-row>
                <v-col cols="9" class="pl-0">
					<v-card-text class="pl-0 pr-6 py-2">
						<div>
							<div class="display-lg-bold mb-3">
								RM{{ nFormatter(parseFloat(data.details.personal_sales) + parseFloat(data.details.group_sales) + parseFloat(data.details.all_bd_sales)) }}
							</div>
							<div class="d-flex align-center">
								<span class="text-xxs pa-0 color-blue col-2 mr-2">
									Personal Sales
								</span>
								<v-progress-linear
									:buffer-value="parseFloat(data.details.personal_sales) / parseFloat(data.details.crown_personal) * 100"
									:value="parseFloat(data.details.personal_sales) / parseFloat(data.details.crown_personal) * 100"
									height="10"
									stream
									rounded
									color="rgba(33, 150, 243, 0.85)"
									class="my-4"
								></v-progress-linear>
								<span class="text-xs pa-0 color-blue col-3 ml-1">
									<span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.personal_sales)) }} / <span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.crown_personal)) }}
								</span>
							</div>

							<div class="d-flex align-center">
								<span class="text-xxs pa-0 color-green col-2 mr-2">
									Group Sales
								</span>
								<v-progress-linear
									:buffer-value="parseFloat(data.details.group_sales) / parseFloat(data.details.crown_group) * 100"
									:value="parseFloat(data.details.group_sales) / parseFloat(data.details.crown_group) * 100"
									height="10"
									stream
									rounded
									color="rgba(0, 227, 150, 0.85)"
									class="my-4"
								></v-progress-linear>
								<span class="text-xxs pa-0 color-green col-3 ml-1">
									<span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.group_sales)) }} / <span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.crown_group)) }}
								</span>
							</div>

							<div class="d-flex align-center">
								<span class="text-xxs pa-0 color-orange col-2 mr-2">
									TM Sales
								</span>
								<v-progress-linear
									:buffer-value="parseFloat(data.details.all_bd_sales) / parseFloat(data.details.crown_bd) * 100"
									:value="parseFloat(data.details.all_bd_sales) / parseFloat(data.details.crown_bd) * 100"
									height="10"
									stream
									rounded
									color="rgba(245, 161, 32, 0.85)"
									class="my-4"
								></v-progress-linear>
								<span class="text-xxs pa-0 color-orange col-3 ml-1">
									<span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.all_bd_sales)) }} / <span class="text-xxs">RM </span>{{ nFormatter(parseFloat(data.details.crown_bd)) }}
								</span>
							</div>

							<v-btn
								small
								color="primary"
								class="mt-4"
								href="/tm-sales"
							>
								View Sales
							</v-btn>
						</div>
					</v-card-text>
				</v-col>

				<v-col cols="3">
					<v-img
						contain
						height="160"
						width="139"
						src="./images/misc/triangle-light.png"
						class="greeting-card-bg"
					></v-img>
					<v-img
						contain
						height="106"
						max-width="82"
						class="greeting-card-trophy"
						src="./images/misc/trophy.png"
					></v-img>
				</v-col>
			</v-row>
		</v-container>
	</v-card>
</template>

<script>
export default {
	props: [ 'data', 'loading' ],
	methods: {
		nFormatter (num) {
			if (num >= 1000000000) {
				return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
			}
			if (num >= 1000000) {
				return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
			}
			if (num >= 1000) {
				return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
			}
			return num;
		},

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
