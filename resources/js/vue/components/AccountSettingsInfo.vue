<template>
  <v-card
    flat
    class="mt-2"
  >
    <v-alert
      text
      type="success"
      v-if="msg != null && responseStatus"
      class="text-sm mb-0"
    >
      {{ msg }}
    </v-alert>

    <v-alert
      text
      type="error"
      v-if="msg != null && !responseStatus"
      class="text-sm mb-0"
    >
      {{ msg }}
    </v-alert>

    <v-form class="multi-col-validation py-3 px-3" ref="form">
      <v-card-text class="pt-5">
        <v-row>
          <v-col cols="12" class="mb-2">
            <v-textarea
              v-model.trim="infoDataLocale.address"
              outlined
              hide-details
              single-line
              rows="3"
              label="Address"
            ></v-textarea>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-text-field
              v-model.trim="infoDataLocale.firstName"
              outlined
              hide-details
              dense
              single-line
              label="First Name"
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-text-field
              v-model.trim="infoDataLocale.lastName"
              outlined
              hide-details
              dense
              single-line
              label="Last Name"
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-text-field
              v-model.trim="infoDataLocale.icNo"
              outlined
              hide-details
              dense
              single-line
              label="IC Passport No"
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-menu
              v-model="isCalenderOpen"
              :close-on-content-click="false"
              :nudge-right="40"
              transition="scale-transition"
              offset-y
              min-width="auto"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="infoDataLocale.birthday"
                  hide-details
                  label="Birthday"
                  prepend-icon="mdi-calendar"
                  readonly
                  single-line
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="infoDataLocale.birthday"
                @input="isCalenderOpen = false"
              ></v-date-picker>
            </v-menu>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-text-field
              v-model.trim="infoDataLocale.phone"
              outlined
              hide-details
              dense
              single-line
              label="Phone"
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-select
              v-model="selectedMarital"
              outlined
              hide-details
              dense
              single-line
              item-text="text"
              item-value="value"
              :items="maritals"
              return-object
              label="Marital Status"
            ></v-select>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <v-select
              v-model="selectedRace"
              outlined
              hide-details
              dense
              single-line
              item-text="text"
              item-value="value"
              :items="races"
              return-object
              label="Race"
            ></v-select>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="mb-2"
          >
            <p class="text--primary mt-n3 mb-2">
              Gender
            </p>
            <v-radio-group
              v-model="infoDataLocale.gender"
              row
              class="mt-0"
              hide-details
            >
              <v-radio
                value="0"
                label="Male"
              >
              </v-radio>
              <v-radio
                value="1"
                label="Female"
              >
              </v-radio>
              <v-radio
                value="2"
                label="Other"
              >
              </v-radio>
            </v-radio-group>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text>
        <v-btn
          color="primary"
          class="me-3 mt-3"
          :loading="submitLoading"
          @click="validateForm"
        >
          Save changes
        </v-btn>
        <v-btn
          outlined
          class="mt-3"
          color="secondary"
          type="reset"
          @click.prevent="resetForm"
        >
          Cancel
        </v-btn>
      </v-card-text>
    </v-form>
  </v-card>
</template>

<script>

import sanitizeHtml from 'sanitize-html';
export default {
  props: {
    informationData: {
      type: Object,
      default: () => {},
    },
  },
  data: ()=> {
    return {
      submitLoading: false,
      infoDataLocale: {},
      responseStatus: true,
      msg: null,
      isCalenderOpen: false,
      selectedRace: {
        text: null,
        value: null
      },
      selectedMarital: {
        text: null,
        value: null
      },
      races: [
        {
          text: 'Malay',
          value: 0
        },
        {
          text: 'Chinese',
          value: 1
        },
        {
          text: 'Indian',
          value: 2
        },
        {
          text: 'Other',
          value: 3
        }
      ],
      maritals: [
        {
          text: 'Single',
          value: 0
        },
        {
          text: 'Married',
          value: 1
        },
        {
          text: 'Divorced',
          value: 2
        },
        {
          text: 'Other',
          value: 3
        }
      ]
    }
  },
  methods: {
    resetForm() {
      this.infoDataLocale = this.informationData;
      if(this.infoDataLocale.race != null) {
        this.selectedRace = this.races[this.infoDataLocale.race];
      }

      if(this.infoDataLocale.maritalStatus != null) {
        this.selectedMarital = this.maritals[this.infoDataLocale.maritalStatus];
      }
      this.msg = null;
      this.$refs.form.resetValidation();
    },

    validateForm() {
      this.submitLoading = true;
      this.msg = null;

      if(this.$refs.form.validate()) {
        this.updateInfo();
      }
      else {
        this.submitLoading = false;
      }
    },

    updateInfo() {
      let data = {
        address: null,
        first_name: null,
        last_name: null,
        ic_passport_no: null,
        birthday: null,
        phone: null,
        gender: null,
        marital_status: null,
        race: null
      }

      if(this.infoDataLocale.address != null) {
        this.infoDataLocale.address = sanitizeHtml(this.infoDataLocale.address, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.address = this.infoDataLocale.address;
      }

      if(this.infoDataLocale.firstName != null) {
        this.infoDataLocale.firstName = sanitizeHtml(this.infoDataLocale.firstName, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.first_name = this.infoDataLocale.firstName;
      }

      if(this.infoDataLocale.lastName != null) {
        this.infoDataLocale.lastName = sanitizeHtml(this.infoDataLocale.lastName, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.last_name = this.infoDataLocale.lastName;
      }

      if(this.infoDataLocale.icNo != null) {
        this.infoDataLocale.icNo = sanitizeHtml(this.infoDataLocale.icNo, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.ic_passport_no = this.infoDataLocale.icNo;
      }

      if(this.infoDataLocale.birthday != null) {
        this.infoDataLocale.birthday = sanitizeHtml(this.infoDataLocale.birthday, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.birthday = this.infoDataLocale.birthday;
      }

      if(this.infoDataLocale.phone != null) {
        this.infoDataLocale.phone = sanitizeHtml(this.infoDataLocale.phone, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.phone = this.infoDataLocale.phone;
      }

      if(this.infoDataLocale.gender != null) {
        this.infoDataLocale.gender = sanitizeHtml(this.infoDataLocale.gender, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.gender = parseInt(this.infoDataLocale.gender);
      }

      if(this.selectedMarital.value != null) {
        this.selectedMarital.value = sanitizeHtml(this.selectedMarital.value, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.marital_status = parseInt(this.selectedMarital.value);
      }

      if(this.selectedRace.value != null) {
        this.selectedRace.value = sanitizeHtml(this.selectedRace.value, {
            allowedTags: [],
            allowedAttributes: {}
        });

        data.race = parseInt(this.selectedRace.value);
      }

      axios.put('/user-info/' + this.$key, {
        data: data
      })
      .then(response =>{
          if(response.status == 200) {
            this.submitLoading = false;
            this.responseStatus = true;
            this.msg = 'Personal info has been updated successful.';
            this.$scrollTo('#card-account-settings');
          }
      })
      .catch(error =>{
        if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
          this.$logout();
        }
        this.submitLoading = false;
        this.responseStatus = false;
        this.msg = error.response.data.error.message;
        this.$scrollTo('#card-account-settings');
      })
    }
  },
  mounted() {
    this.resetForm();
  }
}
</script>
