<template>
	<v-app>
		<app-bar :drawer="drawer" @opened="openDrawer"/>

		<nav-bar :active="mainMenu" :drawer="drawer" @closed="closeDrawer"/>

		<v-main>
			<v-container fluid class="py-2 px-0 py-lg-6 py-xl-6 px-lg-6 px-xl-6">
				<v-row no-gutters>
					<v-col
						cols="12"
						md="5"
						order-md="2"
						order-lg="2"
						order-xl="2"
						class="pl-md-2 pl-md-2 pl-xl-2"
					>
						<dashboard-profile :profileData="profileData" :loading="userLoading"></dashboard-profile>
					</v-col>

					<v-col
						cols="12"
						md="7"
						order-md="1"
						order-lg="1"
						order-xl="1"
						class="pt-2 pt-md-0 pt-lg-0 pt-xl-0"
					>
						<dashboard-statistics-card
							:statisticGeneral="statisticGeneral"
							:percentageData="percentageData"
							:statisticsData="statisticsData"
							:loading="dataLoading"
						>
						</dashboard-statistics-card>
					</v-col>
        		</v-row>

				<v-row no-gutters>
					<v-col
						cols="12"
						md="3"
						class="pt-2 pr-md-1 pr-lg-1 pr-xl-1"
					>
						<v-card class="fill-height pt-4 rounded-0">
							<v-overlay
								absolute
								color="white"
								opacity="0.36"
								:value="dataLoading"
							>
								<v-progress-circular
									indeterminate
									size="58"
									width="5"
									color="rgba(0, 0, 0, 0.2)"
								></v-progress-circular>
							</v-overlay>

							<apexchart width="100%" type="area" :options="optionsSpark1" :series="seriesSpark1"></apexchart>
						</v-card>
					</v-col>

					<v-col
						cols="12"
						md="3"
						class="pt-2 px-md-1 px-lg-1 px-xl-1"
					>
						<v-card class="fill-height pt-4 rounded-0">
							<v-overlay
								absolute
								color="white"
								opacity="0.36"
								:value="dataLoading"
							>
								<v-progress-circular
									indeterminate
									size="58"
									width="5"
									color="rgba(0, 0, 0, 0.2)"
								></v-progress-circular>
							</v-overlay>

							<apexchart width="100%" type="area" :options="optionsSpark2" :series="seriesSpark2"></apexchart>
						</v-card>
					</v-col>

					<v-col
						cols="12"
						md="3"
						class="pt-2 px-md-1 px-lg-1 px-xl-1"
					>
						<v-card class="fill-height pt-4 rounded-0">
							<v-overlay
								absolute
								color="white"
								opacity="0.36"
								:value="dataLoading"
							>
								<v-progress-circular
									indeterminate
									size="58"
									width="5"
									color="rgba(0, 0, 0, 0.2)"
								></v-progress-circular>
							</v-overlay>

							<apexchart width="100%" type="area" :options="optionsSpark3" :series="seriesSpark3"></apexchart>
						</v-card>
					</v-col>

					<v-col
						cols="12"
						md="3"
						class="pt-2 pl-md-1 pl-lg-1 pl-xl-1"
					>
						<v-card class="fill-height pt-4 rounded-0">
							<v-overlay
								absolute
								color="white"
								opacity="0.36"
								:value="dataLoading"
							>
								<v-progress-circular
									indeterminate
									size="58"
									width="5"
									color="rgba(0, 0, 0, 0.2)"
								></v-progress-circular>
							</v-overlay>

							<apexchart width="100%" type="area" :options="optionsSpark4" :series="seriesSpark4"></apexchart>
						</v-card>
					</v-col>
				</v-row>

				<v-row no-gutters>
					<v-col
						cols="12"
						md="5"
						class="pt-2 pr-md-2 pr-lg-2 pr-xl-2"
					>
						<dashboard-min-monthly-sales :loading="kpiLoading" :data="minMonthlySales" />
					</v-col>
					<v-col
						cols="12"
						md="7"
						class="pt-2"
					>
						<dashboard-volume-bonus
							v-if="$role == 4"
							:loading="volumeLoading"
							:data="volume"
						/>
						<dashboard-lvl-upgrade
							v-else
							:loading="upgradeLoading"
							:data="levelUpgrade"
						/>
					</v-col>
				</v-row>

        		<v-row no-gutters v-if="$role >= 4">
					<v-col
						cols="12"
						md="6"
						class="pt-2 pr-md-2 pr-lg-2 pr-xl-2"
					>
						<dashboard-trip :loading="tripLoading" :data="trip" />
					</v-col>
					<v-col
						cols="12"
						md="6"
						class="pt-2"
					>
						<dashboard-crown :loading="crownLoading" :data="crown" />
					</v-col>
				</v-row>
     		</v-container>
    	</v-main>
  	</v-app>
</template>

<script>
import AppBar from "./components/AppBar.vue";
import NavBar from "./components/NavBar.vue";
import DashboardStatisticsCard from "./components/DashboardStatisticsCard.vue";
import DashboardProfile from "./components/DashboardProfile.vue";
import DashboardMinMonthlySales from './components/DashboardMinMonthlySales.vue';
import DashboardLvlUpgrade from './components/DashboardLvlUpgrade.vue';
import DashboardVolumeBonus from './components/DashboardVolumeBonus.vue';
import DashboardTrip from "./components/DashboardTrip.vue";
import DashboardCrown from "./components/DashboardCrown.vue";

export default {
	components: {
		AppBar,
		NavBar,
		DashboardStatisticsCard,
		DashboardProfile,
		DashboardMinMonthlySales,
		DashboardLvlUpgrade,
		DashboardVolumeBonus,
		DashboardTrip,
		DashboardCrown
	},
	data: () => {
		return {
			drawer: false,
			mainMenu: 0,
			title: "Dashboard - Trofit Partner",
			startDate: null,
			endDate: null,
			optionsSpark1: {},
			seriesSpark1: [{ data: [] }],
			optionsSpark2: {},
			seriesSpark2: [{ data: [] }],
			optionsSpark3: {},
			seriesSpark3: [{ data: [] }],
			optionsSpark4: {},
			seriesSpark4: [{ data: [] }],
			statisticGeneral: {
				title: "Current Month Statistics",
				subtitle: "Hooray! Total",
				subtitle2: "Sales Growth",
				subtitle3: "😎 over this month",
			},
			percentageData: {
				status: 1,
				percentage: "0%",
			},
			statisticsData: [
				{
					title: "Sales",
					total: "0.00",
				},
				{
					title: "Orders",
					total: 0,
				},
				{
					title: "Products",
					total: 0,
				},
				{
					title: "Customers",
					total: 0,
				},
			],
			profileData: {
				avatarName: "BW",
				avatarImg: null,
				fullName: "Full Name",
				code: "Code",
				role: "Role",
			},
			levelUpgrade: {
				status: false,
				personal: {
					sales: 0,
					target: 0
				},
				group: {
					sales: 0,
					target: 0,
					target2: 0
				}
			},
			personalSeriesRadial: [],
			groupSeriesRadial: [],
			optionsRadial: {},
			trip: {
				start_date: '',
				details: {
					all_bd_sales: 0,
					end_date: '',
					num_of_pax: 0,
					personal_sales: 0,
					group_sales: 0,
					referral_code: '',
					trip_bd_one_hit: 0,
					trip_bd_one_person: 0,
					trip_bd_two_hit: 0,
					trip_bd_two_person: 0,
					trip_group_one_hit: 0,
					trip_group_one_person: 0,
					trip_group_two_hit: 0,
					trip_group_two_person: 0,
					trip_personal_one_hit: 0,
					trip_personal_one_person: 0,
					trip_personal_two_hit: 0,
					trip_personal_two_person: 0
				}
			},
			crown: {
				start_date: '',
				details: {
					all_bd_sales: 0,
					crown_bd: 0,
					crown_bd_hit: 0,
					crown_personal: 0,
					crown_personal_hit: 0,
					crown_group: 0,
					crown_group_hit: 0,
					personal_sales: 0,
					group_sales: 0,
					referral_code: '',
					end_date: ''
				}
			},
			minMonthlySales: {
				sales: 0,
				target: 0
			},
			volume: {
				date: '',
				first_bd_bonus: 0,
				first_bd_kpi: 0,
				first_bd_kpi_hit: 0,
				personal_sales: 0,
				personal_vol_kpi: 0,
				personal_vol_kpi_hit: 0,
				personal_volume_bonus: 0,
				personal_volume_sales: 0,
				referral_code: '',
				second_bd_bonus: 0,
				second_bd_kpi: 0,
				second_bd_kpi_hit: 0
			},
			userLoading: true,
			dataLoading: true,
			kpiLoading: true,
			upgradeLoading: true,
			volumeLoading: true,
			tripLoading: true,
			crownLoading: true
		};
	},
  methods: {
    openDrawer(value) {
      this.drawer = value;
    },

    closeDrawer(value) {
      this.drawer = value;
    },

    calculateDates() {
      this.endDate = new Date();
      this.startDate = new Date(
        this.endDate.getFullYear(),
        this.endDate.getMonth(),
        1
      );

      this.startDate =
        this.startDate.getFullYear() +
        "-" +
        ("0" + (this.startDate.getMonth() + 1)).slice(-2) +
        "-" +
        ("0" + this.startDate.getDate()).slice(-2) +
        " 00:00:00";

      this.endDate =
        this.endDate.getFullYear() +
        "-" +
        ("0" + (this.endDate.getMonth() + 1)).slice(-2) +
        "-" +
        ("0" + this.endDate.getDate()).slice(-2) +
        " " +
        ("0" + this.endDate.getHours()).slice(-2) +
        ":" +
        ("0" + this.endDate.getMinutes()).slice(-2) +
        ":" +
        ("0" + this.endDate.getSeconds()).slice(-2);

      this.getPersonalStatistic();
    },

    getPartnerDetails() {
      this.userLoading = true;
      axios
        .get("/user/" + this.$key)
        .then((response) => {
          if (response.status == 200) {
            if (response.data.avatar_path == null) {
              if (response.data.last_name == null && response.data.first_name == null) {
                this.profileData.avatarName = "BW";
              } else {
                this.profileData.avatarName = "";
                if (response.data.first_name != null) {
                  this.profileData.avatarName += response.data.first_name.charAt(0).toUpperCase();
                }

                if (response.data.last_name != null) {
                  this.profileData.avatarName += response.data.last_name.charAt(0).toUpperCase();
                }
              }
            } else {
              this.profileData.avatarName = response.data.avatar_name;
              this.profileData.avatarImg = response.data.avatar_path;
            }


            if(response.data.last_name == null && response.data.first_name == null) {
              this.profileData.fullName = 'User';
            }
            else {
              this.profileData.fullName = response.data.first_name + ' ' + response.data.last_name;
            }

            this.profileData.code = response.data.referral_code;
            this.profileData.role = response.data.role.role;
          }

          this.userLoading = false;
        })
        .catch((error) => {
          // if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
          //   this.$logout();
          // }
        });
    },

    getPersonalStatistic() {
		this.dataLoading = true;

		axios.get("/summary-personal-statistic/" + this.$key)
		.then((response) => {
			var datesArr = [];
			var salesArr = [];
			var ordersArr = [];
			var productsArr = [];
			var customersArr = [];

			if (response.status == 200) {
				response.data.charts.forEach((item) => {
					datesArr.push(item.dates);
					salesArr.push(parseFloat(item.personal_sales_amount));
					ordersArr.push(parseInt(item.personal_orders_amount));
					productsArr.push(parseInt(item.personal_products_amount));
					customersArr.push(parseInt(item.personal_customers_amount));
				});

				this.fillStatisticCard(
					response.data.status_sales,
					response.data.percent_sales,
					response.data.numbers[0].total_personal_sales,
					response.data.numbers[0].total_personal_orders,
					response.data.numbers[0].total_personal_products,
					response.data.numbers[0].total_personal_customers
				);

				this.draw4Sparks(
					datesArr,
					salesArr,
					ordersArr,
					productsArr,
					customersArr,
					response.data.numbers[0].total_personal_sales,
					response.data.numbers[0].total_personal_orders,
					response.data.numbers[0].total_personal_products,
					response.data.numbers[0].total_personal_customers
				);

				this.dataLoading = false;
			}
		})
		.catch((error) => {
			// if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
			//   this.$logout();
			// }
		});
    },


	getMonthlyTarget() {
		this.kpiLoading = true;

		axios.get('/monthly-kpi/' + this.$key)
		.then(response =>{
			if(response.status == 200) {

				this.minMonthlySales.sales = parseFloat(response.data.result.personal_sales);
				this.minMonthlySales.target = parseFloat(response.data.result.maintain_amount);
				this.minMonthlySales.last_update = response.data.result.date + ' 23:59:59';

				this.kpiLoading = false;
			}
		})
		.catch(error =>{
			if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
				this.$logout();
			}
		});
	},

	getUpgradeStatistics() {
		this.upgradeLoading = true;

		axios.get('/level-upgrade/' + this.$key)
		.then(response =>{

			if(response.status == 200) {
				this.levelUpgrade.status = response.data.result.status;
				this.levelUpgrade.last_update = response.data.result.details.date + ' 23:59:59';

				this.levelUpgrade.personal.sales = parseFloat(response.data.result.details.personal_sales);
				this.levelUpgrade.personal.target = parseFloat(response.data.result.details.personal_sales_requirement);

				this.levelUpgrade.group.sales = parseFloat(response.data.result.details.group_sales);
				this.levelUpgrade.group.target = parseFloat(response.data.result.details.group_sales_requirement);
				this.levelUpgrade.group.target2 = parseFloat(response.data.result.details.group_sales_requirement_2);

				this.upgradeLoading = false;
			}
		})
		.catch(error =>{
			if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
				this.$logout();
			}
		});
	},

    fillStatisticCard(status, percent, ps, po, pp, pc) {
      (this.statisticGeneral = {}), (this.percentageData = {});
      this.statisticsData = [];

      if (status == 1) {
        this.statisticGeneral = {
          title: "Current Month Statistics",
          subtitle: "Hooray! Total",
          subtitle2: "Sales Growth",
          subtitle3: "😎 over this month",
        };
      } else {
        this.statisticGeneral = {
          title: "Current Month Statistics",
          subtitle: "Keep it up! Total",
          subtitle2: "Sales Dropped",
          subtitle3: "💪 over this month",
        };
      }

      this.percentageData = {
        status: status,
        percentage: percent,
      };

      this.statisticsData = [
        {
          title: "Sales",
          total: ps,
        },
        {
          title: "Orders",
          total: po,
        },
        {
          title: "Products",
          total: pp,
        },
        {
          title: "Customers",
          total: pc,
        },
      ];
    },

    draw4Sparks(dates, sales, orders, products, customers, ps, po, pp, pc) {
      this.optionsSpark1 = {
        colors: [this.$primaryColor],
        chart: {
          id: "sparkLine1",
          group: "sparklines",
          type: "area",
          toolbar: {
            show: false,
          },
          height: "150px",
        },
        stroke: {
          width: 3,
        },
        sparkline: {
          enabled: true,
        },
        fill: {
          opacity: 1,
        },
        dataLabels: {
          enabled: false,
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            right: 0,
            bottom: -12,
            left: -9,
          },
        },
        yaxis: {
          min: 0,
          labels: {
            show: false,
          },
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
        },
        xaxis: {
          categories: dates,
          type: "date",
          labels: {
            show: false,
          },
          axisBorder: {
            show: true,
          },
          axisTicks: {
            show: false,
          },
          tooltip: {
            enabled: false,
          },
        },
        title: {
          text: 'RM ' + ps,
          offsetX: 30,
          style: {
            fontSize: "20px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
        subtitle: {
          text: "Sales",
          offsetX: 30,
          margin: 8,
          floating: true,
          style: {
            fontSize: "14px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
      };

      this.seriesSpark1 = [
        {
          name: "sales",
          data: sales,
        },
      ];

      this.optionsSpark2 = {
        colors: [this.$primaryColor],
        chart: {
          id: "sparkLine2",
          group: "sparklines",
          type: "area",
          toolbar: {
            show: false,
          },
          height: "150",
        },
        stroke: {
          width: 3,
        },
        sparkline: {
          enabled: true,
        },
        fill: {
          opacity: 1,
        },
        dataLabels: {
          enabled: false,
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            right: 0,
            bottom: -12,
            left: -9,
          },
        },
        yaxis: {
          min: 0,
          labels: {
            show: false,
          },
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
        },
        xaxis: {
          categories: dates,
          type: "date",
          labels: {
            show: false,
          },
          axisBorder: {
            show: true,
          },
          axisTicks: {
            show: false,
          },
          tooltip: {
            enabled: false,
          },
        },
        title: {
          text: po,
          offsetX: 30,
          style: {
            fontSize: "20px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
        subtitle: {
          text: "Orders",
          offsetX: 30,
          margin: 8,
          floating: true,
          style: {
            fontSize: "14px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
      };

      this.seriesSpark2 = [
        {
          name: "orders",
          data: orders,
        },
      ];

      this.optionsSpark3 = {
        colors: [this.$primaryColor],
        chart: {
          id: "sparkLine3",
          group: "sparklines",
          type: "area",
          toolbar: {
            show: false,
          },
          height: "150",
        },
        stroke: {
          width: 3,
        },
        sparkline: {
          enabled: true,
        },
        fill: {
          opacity: 1,
        },
        dataLabels: {
          enabled: false,
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            right: 0,
            bottom: -12,
            left: -9,
          },
        },
        yaxis: {
          min: 0,
          labels: {
            show: false,
          },
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
        },
        xaxis: {
          categories: dates,
          type: "date",
          labels: {
            show: false,
          },
          axisBorder: {
            show: true,
          },
          axisTicks: {
            show: false,
          },
          tooltip: {
            enabled: false,
          },
        },
        title: {
          text: pp,
          offsetX: 30,
          style: {
            fontSize: "20px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
        subtitle: {
          text: "Products",
          offsetX: 30,
          margin: 8,
          floating: true,
          style: {
            fontSize: "14px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
      };

      this.seriesSpark3 = [
        {
          name: "products",
          data: products,
        },
      ];

      this.optionsSpark4 = {
        colors: [this.$primaryColor],
        chart: {
          id: "sparkLine4",
          group: "sparklines",
          type: "area",
          toolbar: {
            show: false,
          },
          height: "150",
        },
        stroke: {
          width: 3,
        },
        sparkline: {
          enabled: true,
        },
        fill: {
          opacity: 1,
        },
        dataLabels: {
          enabled: false,
        },
        grid: {
          show: false,
          padding: {
            top: 0,
            right: 0,
            bottom: -12,
            left: -9,
          },
        },
        yaxis: {
          min: 0,
          labels: {
            show: false,
          },
          axisBorder: {
            show: false,
          },
          axisTicks: {
            show: false,
          },
        },
        xaxis: {
          categories: dates,
          type: "date",
          labels: {
            show: false,
          },
          axisBorder: {
            show: true,
          },
          axisTicks: {
            show: false,
          },
          tooltip: {
            enabled: false,
          },
        },
        title: {
          text: pc,
          offsetX: 30,
          style: {
            fontSize: "20px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
        subtitle: {
          text: "Customers",
          offsetX: 30,
          margin: 8,
          floating: true,
          style: {
            fontSize: "14px",
            cssClass: "apexcharts-yaxis-title",
          },
        },
      };

      this.seriesSpark4 = [
        {
          name: "customers",
          data: customers,
        },
      ];
    	},

		getTripIncentives() {
            this.tripLoading = true;
            axios.get('/trip-incentive/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
					this.trip.start_date = response.data.start_date;
					this.trip.details = response.data.result;

            		this.tripLoading = false;
				}
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
		},


		getCrownIncentives() {
            this.crownLoading = true;
            axios.get('/crown-incentive/' + this.$key)
            .then(response =>{
                if(response.status == 200) {
					this.crown.start_date = response.data.start_date;
					this.crown.details = response.data.result;

            		this.crownLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
		},


		getVolumeBonusIncentives() {
            this.volumeLoading = true;
            axios.get('/volume-incentive/' + this.$key)
            .then(response =>{

                if(response.status == 200) {
					this.volume = response.data.result;

            		this.volumeLoading = false;
                }
            })
            .catch(error =>{
                if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
                    this.$logout();
                }
            });
		},

		init() {
			this.getPartnerDetails();
			this.calculateDates();
			this.getMonthlyTarget();

			if(this.$role == 4) {
				this.getTripIncentives();
				this.getCrownIncentives();
				this.getVolumeBonusIncentives();
			}
			else {
				this.getUpgradeStatistics();
			}
		}

	},

	created() {
		document.title = this.title;
		this.init();
	},
};
</script>
