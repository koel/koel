import $ from 'jquery';
window.Vue = require('vue');
Vue.use(require('vue-resource'));
Vue.http.options.root = '/api';
Vue.http.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
Vue.config.debug = false;

// Exit light,
// Enter night,
// Take my hand,
// We're off to never never land.
new Vue(require('./app.vue')).$mount('body');
