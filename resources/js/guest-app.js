window.Vue = require('vue');
window.axios = require('axios');

import Vue from 'vue';
import VueRouter from 'vue-router';
import routes from './guest-router';
import Vuetify from './guest-vuetify';
import i18n from './i18n';
import VueGtag from "vue-gtag";

Vue.use(VueRouter);
const router = new VueRouter(routes);

// use beforeEach route guard to set the language
router.beforeEach((to, from, next) => {

    // use the language from the routing param or default language
    let language = to.params.lang;

    if (!language) {
      language = 'en'
    }

    // set the current language for i18n.
    i18n.locale = language
    next()
})

Vue.prototype.$isMobile = function() {
  return this.$vuetify.breakpoint.smAndDown;
}
Vue.use(VueGtag, {
    config: { id: "G-QQHNH1TFY7" },
    appName: 'Trofit Partner',
}, router);

const app = new Vue({
    el: '#app',
    router,
    i18n,
    vuetify: Vuetify
})
