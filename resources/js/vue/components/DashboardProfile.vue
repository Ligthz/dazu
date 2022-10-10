<template>
  	<v-card class="d-flex fill-height rounded-0 pa-4">
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

        <v-avatar
			rounded
			size="90"
			class="mr-3 profile-avatar"
			color="primary"
        >
			<span class="white--text display-xl" v-bind:class="{ 'display-lg':isMobile }" v-if="profileLocale.avatarImg == null">{{ profileLocale.avatarName }}</span>
			<v-img v-else aspect-ratio="1" :src="profileLocale.avatarImg" :alt="profileLocale.avatarName"></v-img>
        </v-avatar>

        <div>
			<v-card-title class="align-start pt-0 mb-1 pl-2 profile-card-title">
				<span class="mr-1">{{ profileLocale.fullName }}</span>
				<span>({{ profileLocale.code }})</span>
			</v-card-title>
			<v-card-subtitle class="px-6 pt-0 text-sm pl-2 pb-0 pb-lg-5 pb-xl-5">{{ profileLocale.role.toUpperCase() }}</v-card-subtitle>
			<v-card-actions class="px-6 pl-2" v-if="!isMobile">
				<v-btn
					color="primary"
					to="/account-settings"
				>
					Edit Profile
				</v-btn>
			</v-card-actions>
        </div>
	</v-card>
</template>

<script>
export default {
  props: {
    profileData: {
      type: Object,
      default: () => {},
    },
	loading: {
		type: Boolean,
		default: true
	}
  },
  data: () => {
    return {
      profileLocale: {}
    }
  },

  computed: {
      isMobile: function() {
          return this.$vuetify.breakpoint.smAndDown;
      }
  },

  methods: {
    resetForm() {
      this.profileLocale = this.profileData;
    }
  },
  created() {
    this.resetForm();
  }
}
</script>
