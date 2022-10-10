window.Vue = require('vue');
window.axios = require('axios');
const VueScrollTo = require('vue-scrollto');

import Vue from 'vue';
import VueRouter from 'vue-router';
import routes from './auth-router';
import VueApexCharts from 'vue-apexcharts'
/* Vuetify */
import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css'
import i18n from './i18n';
import VueGtag from "vue-gtag";
Vue.use(Vuetify);
Vue.use(VueApexCharts);
Vue.component('apexchart', VueApexCharts);

Vue.use(VueRouter);
const router = new VueRouter(routes);

Vue.use(VueGtag, {
    config: { id: "G-189W7455EE" },
    appName: 'Trofit Partner',
}, router);

// default options
Vue.use(VueScrollTo, {
     duration: 100,
     easing: "ease",
     offset: -48,
     force: true,
     cancelable: true,
     onStart: false,
     onDone: false,
     onCancel: false,
     x: false,
     y: true
})

Vue.prototype.$logout = function() {
    axios.post('./account/logout')
    .then(response =>{
        window.location.href = '/' + this.$i18n.locale;
    })
    .catch(error =>{
        window.location.href = '/' + this.$i18n.locale;
    })
}


var primaryColor = '#ee334a';
var primaryLightColor = '#fde9eb';
var secondaryColor = '#808080';
var secondaryLightColor = '#fafafa';

axios.post('/account/authenticate')
.then(response =>{
    if(response.status == 200) {

        Vue.prototype.$role = response.data[0].roles;
        Vue.prototype.$key = response.data[0].key;
        primaryColor = response.data[0].primary;
        primaryLightColor = response.data[0].secondary;

        Vue.prototype.$status = response.data[1].status;

        Vue.prototype.$primaryColor = primaryColor;
        Vue.prototype.$primaryLightColor = primaryLightColor;
        Vue.prototype.$secondaryColor = secondaryColor;
        Vue.prototype.$secondaryLightColor = secondaryLightColor;

        Vue.prototype.$url = "https://trofitshop.com";

        initVue();
    }
    else {
        window.location.href = '/' + this.$i18n.locale;
    }
})
.catch(error =>{
    window.location.href = '/' + this.$i18n.locale;
})

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


function initVue() {
    const app = new Vue({
        el: '#app',
        router,
        i18n,
        vuetify: new Vuetify({
            icons: {
                iconfont: 'mdi', // 'mdi' || 'mdiSvg' || 'md' || 'fa' || 'fa4' || 'faSvg'
            },
            theme: {
                themes: {
                    light: {
                        primary: primaryColor,
                        secondary: secondaryColor,
                        default: '#FF0013'
                    },
                },
            },
        })
    });
}

