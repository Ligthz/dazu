<template>
  <v-tooltip bottom>
    <template v-slot:activator="{ on, attrs }">
      <div
        v-bind="attrs"
        v-on="on"
      >
        <v-select
            v-model="selectedLanguage"
            label="Language"
            :items="items"
            color="default"
            item-color="default"
            append-icon=""
            solo
            flat
            dense
            hide-details
            single-line
            background-color="transparent"
            class="lang-select"
            @change="setLocale"
        ></v-select>
      </div>
    </template>
    <span>{{ $t('main_nav.language') }}</span>
  </v-tooltip>
</template>

<script>
export default {
  data() {
    return {
        selectedLanguage: this.$i18n.locale.toUpperCase(),
        items: []
    }
  },
  methods: {
    setLocale() {
      let path = this.$router.currentRoute.fullPath;
      let locale = path.substring(1, 3);
      let remainPath = path.substring(3);

      if(locale == '') {
        window.location.href = '/' + this.selectedLanguage.toLowerCase();
      }
      else {
        if(this.$languages.includes(path.substring(1, 3))) {
          // is cn
          window.location.href = '/' + this.selectedLanguage.toLowerCase() + remainPath;
        }
        else {
          // en, with remaining path
          window.location.href = '/' + this.selectedLanguage.toLowerCase() + path;
        }
      }      
    }
  },
  
  mounted() {
    this.items = this.$languages.map(function(x){ return x.toUpperCase(); })
  }
}
</script>

<style scoped lang="scss">
@import "../../../scss/preset.scss";
.lang-select {
  width: 56px !important;
  max-width: 56px !important;

  ::v-deep .v-select__selections {
      .v-select__selection {
          width: 100% !important;
          text-align: center !important;
          color: rgba(0,0,0,.7) !important;
      }

      input {
          display: none !important;
      }
  }
}
</style>