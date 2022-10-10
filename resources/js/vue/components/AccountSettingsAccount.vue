<template>
  <v-card
    flat
    class="pa-3 mt-2"
  >
    <v-alert
      text
      type="success"
      v-if="msg != null && responseStatus"
      class="text-sm mb-5"
    >
      {{ msg }}
    </v-alert>

    <v-alert
      text
      type="error"
      v-if="msg != null && !responseStatus"
      class="text-sm mb-5"
    >
      {{ msg }}
    </v-alert>

    <v-card-text class="d-flex">
      <v-avatar
        rounded
        size="136"
        class="me-6"
        color="primary"
      >
        <span class="white--text display-xl" v-if="accountDataLocale.avatarImg == null">{{ accountDataLocale.avatarName }}</span>
        <v-img v-else :src="accountDataLocale.avatarImg" :alt="accountDataLocale.avatarName"></v-img>
        <v-overlay
            absolute
            v-if="uploading"
        >
            <div>
                <v-progress-circular
                    color="grey lighten-5"
                    :height="4"
                    rotate="275"
                    :value="uploadProgress"
                ></v-progress-circular>
            </div>
        </v-overlay>
      </v-avatar>

      <!-- upload photo -->
      <div>
        <div class="display-md-bold">
          {{ accountDataLocale.fullName }}
        </div>
        <v-btn
          color="primary"
          class="me-3 mt-5"
          :loading="uploadLoading"
          @click="addProfilePicture"
        >
          <v-icon class="d-sm-none">
            mdi-cloud-upload-outline
          </v-icon>
          <span class="d-none d-sm-block">Upload new photo</span>
        </v-btn>

        <input
          ref="uploader"
          type="file"
          accept="image/*"
          :hidden="true"
          @change="onFileChanged"
        />

        <p class="text-sm mt-5" v-if="!isMobile">
          Allowed JPG, SVG or PNG. Max size of 500K
        </p>
      </div>
    </v-card-text>

    <v-card-text>
      <v-form class="multi-col-validation mt-6">
        <v-row>
          <v-col
            md="6"
            cols="12"
            class="py-2"
          >
            <v-text-field
              v-model="accountDataLocale.username"
              label="Username"
              dense
              outlined
              readonly
            ></v-text-field>
          </v-col>

          <v-col
            md="6"
            cols="12"
            class="py-2"
          >
            <v-text-field
              v-model="accountDataLocale.code"
              label="Referral Code"
              dense
              outlined
              readonly
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="py-2"
          >
            <v-text-field
              v-model="accountDataLocale.email"
              label="E-mail"
              dense
              outlined
              readonly
            ></v-text-field>
          </v-col>

          <v-col
            cols="12"
            md="6"
            class="py-2"
          >
            <v-text-field
              v-model="accountDataLocale.role"
              dense
              label="Role"
              outlined
              readonly
            ></v-text-field>
          </v-col>
        </v-row>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script>

import sanitizeHtml from 'sanitize-html';
export default {
  props: {
    accountData: {
      type: Object,
      default: () => {},
    },
  },
  data: ()=> {
    return {
      accountDataLocale: {},
      uploading: false,
      uploadLoading: false,
      uploadProgress: 0,
      responseStatus: true,
      msg: null
    }
  },

  computed: {
      isMobile: function() {
          return this.$vuetify.breakpoint.smAndDown;
      }
  },

  methods: {
    resetForm() {
      this.accountDataLocale = this.accountData;
      this.uploading = false;
      this.uploadLoading = false;
      this.uploadProgress = 0;
    },

    addProfilePicture() {
      this.uploadLoading = true;
      this.msg = null;
      window.addEventListener('focus', () => {
          this.uploadLoading = false;
      }, { once: true })

      this.$refs.uploader.click();
    },

    onFileChanged(e) {
      this.uploadLoading = true;

      if (e.target.files && e.target.files[0]) {
          const f = e.target.files[0];

          var bufferObj = {};
          bufferObj.data = null;

          if (f) {
              if(f['type'] === 'image/png' || f['type'] === 'image/jpg'
                  || f['type'] === 'image/jpeg' || f['type'] === 'image/svg+xml')
              {
                  if((f['size'] * 0.001) <= 500) {

                      let formData = new FormData();
                      formData.append('file', f);
                      formData.append('created_by', this.$key);

                      if(f['name'].length > 100) {
                          formData.append('file_name', (f['name'].substring(0, 90) + '.' + f['name'].split('.').pop()));
                      }
                      else {
                          formData.append('file_name', f['name']);
                      }

                      bufferObj.data = formData;

                      this.uploadProfilePicture(bufferObj);
                  }
                  else {
                    this.resetForm();
                    this.responseStatus = false;
                    this.msg = 'File size too large! Maximum file size is 500kb.'
                    this.$scrollTo('#card-account-settings');
                  }
              }
              else {
                this.resetForm();
                this.responseStatus = false;
                this.msg = 'Invalid file type! Acceptable file type: .png, .jpg, .jpeg, .svg ONLY.'
                this.$scrollTo('#card-account-settings');
              }
          }
          else {
            this.resetForm();
            this.responseStatus = false;
            this.msg = 'Invalid file! Please try another file.'
            this.$scrollTo('#card-account-settings');
          }
      }
    },

    async uploadProfilePicture (obj) {
      this.uploading = true;
      let resultArr = [];
      resultArr = await this.uploadMedia(obj.data);
      if(resultArr[0] == true) {
        this.updateProfilePicture(resultArr[1]);
      }
      else {
        this.resetForm();
        this.responseStatus = false;
        this.msg = resultArr[1];
        this.$scrollTo('#card-account-settings');
      }
    },

    uploadMedia(data) {
      return new Promise((resolve, reject) => {
        axios.post('/file', data,
        {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: function( progressEvent ) {
                this.uploadProgress = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ) );
            }.bind(this)
        })
        .then(response =>{
            if(response.status == 201) {
              resolve([true, response.data.avatar]);
            }
            else {
              reject([false, "Unable to upload, something went wrong!"]);
            }
        })
        .catch(error =>{
          if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
            this.$logout();
          }
          else {
            reject([false, error.response.data.error.message]);
          }
        })
      });
    },

    updateProfilePicture(key) {
      let data = {};
      data.image_id = sanitizeHtml(key, {
          allowedTags: [],
          allowedAttributes: {}
      });

      axios.put('/user-avatar/' + this.$key, data)
      .then(response =>{
        if(response.status == 200) {
            this.resetForm();
            this.responseStatus = true;
            this.msg = 'Profile picture has been updated successful.';
            this.accountDataLocale.avatarImg = response.data.avatar_path;
            this.accountDataLocale.avatarName = response.data.avatar_name;
            this.$scrollTo('#card-account-settings');
        }
      })
      .catch(error =>{
        if(error.response.status == 401 || error.response.status == 419 || error.response.status == 405) {
          this.$logout();
        }
        this.resetForm();
        this.responseStatus = false;
        this.msg = error.response.data.error.message;
        this.$scrollTo('#card-account-settings');
      });
    },
  },
  created() {
    this.resetForm();
  }
}
</script>
