<template>
  <v-app>
    <app-bar :drawer="drawer" @opened="openDrawer"/>

    <nav-bar :active="mainMenu" :drawer="drawer" @closed="closeDrawer"/>

    <v-main>
      <v-container fluid class="py-2 px-0 py-lg-6 py-xl-6 px-lg-6 px-xl-6">
          <v-row>
              <v-col
                  cols="12"
              >
                <v-card class="rounded-0" id="card-account-settings">
                  <v-overlay
                    absolute
                    color="white"
                    opacity="0.36"
                    :value="dataLoading && bankLoading"
                  >
                    <v-progress-circular
                        indeterminate
                        size="58"
                        width="5"
                        color="rgba(0, 0, 0, 0.2)"
                    ></v-progress-circular>
                  </v-overlay>

                  <!-- tabs -->
                  <v-tabs
                    v-model="tab"
                    show-arrows
                  >
                    <v-tab
                      v-for="tab in tabs"
                      :key="tab.icon"
                    >
                      <v-icon
                        size="20"
                        class="me-3"
                      >
                        {{ tab.icon }}
                      </v-icon>
                      <span>{{ tab.title }}</span>
                    </v-tab>
                  </v-tabs>

                  <!-- tabs item -->
                  <v-tabs-items v-model="tab">
                    <v-tab-item>
                      <account-settings-account :account-data="accountSettingData.account"></account-settings-account>
                    </v-tab-item>

                    <v-tab-item>
                      <account-settings-bank :bank-data="accountSettingData.bank" :banks="accountSettingData.banks"></account-settings-bank>
                    </v-tab-item>

                    <v-tab-item>
                      <account-settings-security></account-settings-security>
                    </v-tab-item>

                    <v-tab-item>
                      <account-settings-info :information-data="accountSettingData.information"></account-settings-info>
                    </v-tab-item>
                  </v-tabs-items>
                </v-card>
              </v-col>
          </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import AppBar from './components/AppBar.vue';
import NavBar from './components/NavBar.vue';
import AccountSettingsAccount from './components/AccountSettingsAccount.vue'
import AccountSettingsBank from './components/AccountSettingsBank.vue'
import AccountSettingsSecurity from './components/AccountSettingsSecurity.vue'
import AccountSettingsInfo from './components/AccountSettingsInfo.vue'

export default {
  components: {
    AppBar,
    NavBar,
    AccountSettingsAccount,
    AccountSettingsBank,
    AccountSettingsSecurity,
    AccountSettingsInfo,
  },
  data: ()=> {
    return {
      drawer: false,
      title: 'Account Settings - Trofit Partner',
      mainMenu: -1,
      dataLoading: true,
	  bankLoading: true,
      tab: '',
      tabs: [
        { title: 'Account', icon: 'mdi-account-outline' },
        { title: 'Bank Details', icon: 'mdi-bank-outline' },
        { title: 'Security', icon: 'mdi-lock-outline' },
        { title: 'Info', icon: 'mdi-information-outline' },
      ],
      accountSettingData: {
        account: {
          avatarName: 'BW',
          avatarImg: null,
          username: null,
          fullName: 'Full Name',
          code: null,
          email: null,
          role: null,
        },
        bank: {
			bankAccountName: null,
			bankAccountNo: null,
			banks: {
				bankName: null,
				bankId: null
			}
        },
		banks: [],
        information: {
          address: null,
          firstName: null,
          lastName: null,
          icNo: null,
          birthday: null,
          phone: null,
          gender: null,
          maritalStatus: null,
          race: null
        },
      }
    }
  },

  methods: {
    openDrawer(value) {
      this.drawer = value;
    },

    closeDrawer(value) {
      this.drawer = value;
    },

	getBanks() {
		this.bankLoading = true;
		axios.get('/banks')
		.then(response =>{
			if(response.status == 200) {
				this.accountSettingData.banks = response.data;

				this.bankLoading = false;
			}
		})
		.catch(error =>{
			if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
			this.$logout();
			}
		})
	},

    getPartnerDetails() {
		this.dataLoading = true;
		axios.get('/user/' + this.$key)
		.then(response =>{
			if(response.status == 200) {
				if(response.data.avatar_path == null) {
				if(response.data.last_name == null && response.data.first_name == null) {
					this.accountSettingData.account.avatarName = 'BW';
				}
				else {
					this.accountSettingData.account.avatarName = '';
					if(response.data.first_name != null) {
					this.accountSettingData.account.avatarName += (response.data.first_name).charAt(0).toUpperCase();
					}

					if(response.data.last_name != null) {
					this.accountSettingData.account.avatarName += (response.data.last_name).charAt(0).toUpperCase();
					}
				}
				}
				else {
				this.accountSettingData.account.avatarName = response.data.avatar_name;
				this.accountSettingData.account.avatarImg = response.data.avatar_path;
				}

				if(response.data.last_name == null && response.data.first_name == null) {
				this.accountSettingData.account.fullName = 'User';
				}
				else {
				this.accountSettingData.account.fullName = response.data.first_name + ' ' + response.data.last_name;
				}

				this.accountSettingData.account.username = response.data.username;
				this.accountSettingData.account.code = response.data.referral_code;
				this.accountSettingData.account.email = response.data.email;
				this.accountSettingData.account.role = response.data.role.role;

				this.accountSettingData.bank.bankAccountName = response.data.bank_name;
				this.accountSettingData.bank.bankAccountNo = response.data.bank_account;
				this.accountSettingData.bank.banks.bank_name = response.data.bank;
				this.accountSettingData.bank.banks.bank_id = response.data.bank_id;

				this.accountSettingData.information.address = response.data.address;
				this.accountSettingData.information.firstName = response.data.first_name;
				this.accountSettingData.information.lastName = response.data.last_name;
				this.accountSettingData.information.icNo = response.data.ic;
				this.accountSettingData.information.birthday = response.data.dob;
				this.accountSettingData.information.phone = response.data.phone;

				if(response.data.gender == null) {
				this.accountSettingData.information.gender = null;
				}
				else {
				this.accountSettingData.information.gender = response.data.gender + '';
				}

				if(response.data.marital_status == null) {
				this.accountSettingData.information.maritalStatus = null;
				}
				else {
				this.accountSettingData.information.maritalStatus = parseInt(response.data.marital_status);
				}

				if(response.data.race == null) {
				this.accountSettingData.information.race = null;
				}
				else {
				this.accountSettingData.information.race = parseInt(response.data.race);
				}

				this.dataLoading = false;
			}
		})
		.catch(error =>{
			if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
				this.$logout();
			}
		})
    },
	},

	created() {
		document.title = this.title;
		this.getPartnerDetails();
		this.getBanks();
	}
}
</script>
