<template>
<v-app class="app-v">
  <div class="d-flex justify-end pt-5 px-7">
    <!--<div class="lang-switch-wrapper">
      <language-switcher />
    </div>-->
  </div>
  <div class="auth-wrapper auth-v1">
    <div class="auth-inner mt-n10" v-bind:class="{ 'mb-9':isMobile }">
      <v-card class="auth-card">
        <!-- logo -->
        <v-img
          width="236px"
          src="./images/trofit_logo.png"
          class="mx-auto my-7"
        />

        <!-- title -->
        <v-card-text>
          <p class="text-2xl font-weight-semibold text--primary mb-2">
            {{ $t('login.welcome_title') }}
          </p>
          <p class="mb-2">
            {{ $t('login.welcome_subtitle') }}
          </p>
        </v-card-text>

        <!-- login form -->
        <v-card-text>
          <v-alert
            text
            type="error"
            v-if="errMsg != null"
            class="text-sm mb-5"
          >
            {{ errMsg }}
          </v-alert>
          <v-form>
            <v-text-field
              v-model.trim="login"
              outlined
              single-line
              :label="$t('login.email')"
              placeholder="john@trofitshop.com"
              hide-details
              class="mb-3"
            ></v-text-field>

            <v-text-field
              v-model.trim="password"
              outlined
              single-line
              :type="isPasswordVisible ? 'text' : 'password'"
              :label="$t('login.password')"
              placeholder="········"
              :append-icon="isPasswordVisible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
              hide-details
              @click:append="isPasswordVisible = !isPasswordVisible"
              @keyup.13="validateForm"
            ></v-text-field>

            <!--<div class="d-flex align-center justify-space-between flex-wrap">
              <v-checkbox
                hide-details
                v-model="rememberMe"
                class="me-3 mt-2 text-sm"
              >
                <template v-slot:label>
                  <div class="text-sm">
                    Remember Me
                  </div>
                </template>
              </v-checkbox>


              <a
                href="javascript:void(0)"
                class="mt-2 text-sm"
              >
                Forgot Password?
              </a>
            </div>-->

            <v-btn
              block
              color="primary"
              class="mt-6"
              :loading="loginLoading"
              @click="validateForm"
            >
              {{ $t('login.login') }}
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </div>

    <!-- <div class="ocean">
      <div class="wave"></div>
      <div class="wave"></div>
    </div> -->
  </div>
</v-app>
</template>

<script>
import LanguageSwitcher from './components/LanguageSwitcher.vue';
export default {
  components: { LanguageSwitcher },
  data: ()=> {
    return {
      errMsg: null,
      loginLoading: false,
      isPasswordVisible: false,
      login: '',
      password: '',
      rememberMe: true
    }
  },

  computed: {
    isMobile: function() {
        return this.$vuetify.breakpoint.smAndDown;
    }
  },

  methods: {
    validateForm() {
      this.errMsg = null;
      this.loginLoading = true;
      this.submitLogin();
    },

    submitLogin() {
      const formData = new FormData();
      formData.append('login', this.login);
      formData.append('password', this.password);

      axios.post('/account/login', formData)
      .then(response =>{
          if(response.status == 200) {
            window.location.href = '/' + this.$i18n.locale;
          }
      })
      .catch(error =>{
        this.loginLoading = false;
        this.errMsg = error.response.data.error.message;
      })
    }
  }
}
</script>
