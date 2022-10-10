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
      <div class="px-3 pt-3">
        <v-card-text class="pt-5">
          <v-row>
            <v-col
              cols="12"
              sm="8"
              md="6"
            >
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
                @click:append="isCurrentPasswordVisible = !isCurrentPasswordVisible"
              ></v-text-field>

              <!-- new password -->
              <v-text-field
                v-model.trim="newPassword"
                :rules="[rules.required, rules.password, passwordNotMatch]"
                :type="isNewPasswordVisible ? 'text' : 'password'"
                :append-icon="isNewPasswordVisible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                label="New Password"
                placeholder="········"
                outlined
                dense
                single-line
                class="mt-3"
                @click:append="isNewPasswordVisible = !isNewPasswordVisible"
              ></v-text-field>

              <!-- confirm password -->
              <v-text-field
                v-model.trim="cPassword"
                :rules="[rules.required, passwordMatch]"
                :type="isCPasswordVisible ? 'text' : 'password'"
                :append-icon="isCPasswordVisible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                label="Confirm New Password"
                placeholder="········"
                outlined
                dense
                single-line
                class="mt-3"
                @click:append="isCPasswordVisible = !isCPasswordVisible"
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
                max-width="280"
                src="images/model-2.png"
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
          <span class="text-break">Keep Your Password Safe</span>
        </v-card-title>

        <v-card-text class="two-factor-auth text-center mx-auto">
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
            Please do not share your password to anyone.
          </p>
          <p class="text-sm text--primary">
            Passwords are like underwear: you don't let people see
            it, you should change it very often, and you shouldn't
            share it with strangers.
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
  data: ()=> {
    return {
      submitLoading: false,
      isCurrentPasswordVisible: false,
      isNewPasswordVisible: false,
      isCPasswordVisible: false,
      currentPassword: '',
      newPassword: '',
      cPassword: '',
      rules: {
          required: value => !!value || 'Required.',
          password: value => {
              const patt = /^[0-9a-zA-Z]{8,}$/;
              return patt.test(value) || "Make sure it's at least 8 characters.";
          }
      },
      responseStatus: true,
      msg: null
    }
  },
  computed: {
    passwordNotMatch() {
      return () => (this.currentPassword != this.newPassword) || 'New password cannot be same as your current password.'
    },
    passwordMatch() {
      return () => (this.newPassword === this.cPassword) || 'Password not match.'
    },
    isMobile: function() {
      return this.$vuetify.breakpoint.smAndDown;
    }
  },

  methods: {
    resetForm() {
      this.isCurrentPasswordVisible = false;
      this.isNewPasswordVisible = false;
      this.isCPasswordVisible = false;
      this.currentPassword = '';
      this.newPassword = '';
      this.cPassword = '';
      this.msg = null;
      this.$refs.form.resetValidation();
    },

    validateForm() {
      this.submitLoading = true;
      this.msg = null;

      if(this.$refs.form.validate()) {
        this.changePassword();
      }
      else {
        this.submitLoading = false;
      }
    },

    changePassword() {
      let data = {
        old_password: null,
        new_password: null,
        con_password: null,
      }

      data.old_password = sanitizeHtml(this.currentPassword, {
          allowedTags: [],
          allowedAttributes: {}
      });

      data.new_password = sanitizeHtml(this.newPassword, {
          allowedTags: [],
          allowedAttributes: {}
      });

      data.con_password = sanitizeHtml(this.cPassword, {
          allowedTags: [],
          allowedAttributes: {}
      });

      axios.put('/user-password/' + this.$key, data)
      .then(response =>{
          if(response.status == 200) {
            this.submitLoading = false;
            this.resetForm();
            this.responseStatus = true;
            this.msg = 'Password has been updated successful.';
            this.$scrollTo('#card-account-settings');

            this.$logout();
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
  }
}
</script>
