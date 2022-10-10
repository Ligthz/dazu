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

    <v-form ref="form">
      <div class="pt-3 px-3">
        <v-card-text class="pt-5">
          <v-row>
            <v-col
              cols="12"
              sm="8"
              md="6"
            >
              <!-- bank name -->
              <v-autocomplete
                v-model="selectedBank"
                :rules="[rules.bankRequired]"
                label="Bank Name"
                outlined
                dense
                single-line
                item-text="bank_name"
                item-value="bank_id"
                :items="localeBanks"
                return-object
              ></v-autocomplete>

              <!-- bank account name -->
              <v-text-field
                v-model.trim="bankDataLocale.bankAccountName"
                :rules="[rules.required]"
                label="Bank Account Name"
                outlined
                dense
                single-line
                class="mt-3"
              ></v-text-field>

              <!-- bank account no -->
              <v-text-field
                v-model.trim="bankDataLocale.bankAccountNo"
                :rules="[rules.required]"
                label="Bank Account No"
                outlined
                dense
                single-line
                class="mt-3"
              ></v-text-field>

              <!-- current password -->
              <v-text-field
                v-model.trim="currentPassword"
                :rules="[rules.required]"
                :type="isCurrentPasswordVisible ? 'text' : 'password'"
                :append-icon="isCurrentPasswordVisible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                label="Current Password"
                placeholder="········"
                outlined
                dense
                single-line
                class="mt-3"
                @click:append="isCurrentPasswordVisible = !isCurrentPasswordVisible"
              ></v-text-field>
            </v-col>

            <v-col
              v-if="!isMobile"
              cols="12"
              sm="4"
              md="6"
              class="d-sm-flex justify-center security-character-wrapper"
            >
              <v-img
                max-width="320"
                src="images/model-1.png"
                class="security-character"
              ></v-img>
            </v-col>
          </v-row>
        </v-card-text>
      </div>

      <!-- divider -->
      <v-divider></v-divider>

      <div class="pa-3">
        <v-card-title class="flex-nowrap">
          <v-icon class="text--primary me-3">
            mdi-key-outline
          </v-icon>
          <span class="text-break">Your Privacy is Our Priority</span>
        </v-card-title>

        <v-card-text class="two-factor-auth text-center mx-auto pt-4">
          <v-avatar
            color="primary"
            class="primary mb-4"
            rounded
          >
            <v-icon
              size="25"
              color="white"
            >
              mdi-lock-outline
            </v-icon>
          </v-avatar>
          <p class="text-base text--primary font-weight-semibold">
            We will not share your bank account details with other individuals or organizations without your permission.
          </p>
        </v-card-text>

        <!-- action buttons -->
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
            color="secondary"
            outlined
            class="mt-3"
            :disabled="submitLoading"
            @click.prevent="resetForm"
          >
            Cancel
          </v-btn>
        </v-card-text>
      </div>
    </v-form>
  </v-card>
</template>

<script>

import sanitizeHtml from 'sanitize-html';
export default {
  props: {
    bankData: {
      type: Object,
      default: () => {},
    },
    banks: {
      type: Array,
      default: () => []
    }
  },
  data: ()=> {
    return {
      submitLoading: false,
      bankDataLocale: {},
      isCurrentPasswordVisible: false,
      currentPassword: '',
      rules: {
          required: value => !!value || 'Required.',
          bankRequired: v => (v.bank_id) != null || 'Required.'
      },
      responseStatus: true,
      msg: null,
      selectedBank: {
        bank_id: null,
        bank_name: null
      },
      localeBanks: []
    }
  },

  computed: {
      isMobile: function() {
          return this.$vuetify.breakpoint.smAndDown;
      },
  },

  methods: {
    resetForm() {
      this.bankDataLocale = this.bankData;
      this.selectedBank = this.bankDataLocale.banks;
      this.localeBanks = this.banks;
      this.isCurrentPasswordVisible = false;
      this.currentPassword = '';
      this.msg = null;
      this.$refs.form.resetValidation();
    },

    validateForm() {
      this.submitLoading = true;
      this.msg = null;

      if(this.$refs.form.validate()) {
        this.updateBankDetails();
      }
      else {
        this.submitLoading = false;
      }
    },

    updateBankDetails() {
      let data = {
        bank_account_name: null,
        bank_name: null,
        bank_account_no: null,
        password: null
      }


      if(this.selectedBank.bank_id != null) {
        this.bankDataLocale.banks = this.selectedBank;

        data.bank_name = sanitizeHtml(this.selectedBank.bank_id, {
            allowedTags: [],
            allowedAttributes: {}
        });
      }

      if(this.bankDataLocale.bankAccountName != null) {
        data.bank_account_name = sanitizeHtml(this.bankDataLocale.bankAccountName, {
            allowedTags: [],
            allowedAttributes: {}
        });
      }

      if(this.bankDataLocale.bankAccountNo != null) {
        data.bank_account_no = sanitizeHtml(this.bankDataLocale.bankAccountNo, {
            allowedTags: [],
            allowedAttributes: {}
        });
      }

      if(this.currentPassword != null) {
        data.password = sanitizeHtml(this.currentPassword, {
          allowedTags: [],
          allowedAttributes: {}
        });
      }

      axios.put('/user-bank/' + this.$key, data)
      .then(response =>{
          if(response.status == 200) {
            this.submitLoading = false;
            this.responseStatus = true;
            this.msg = 'Bank details has been updated successful.';
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
