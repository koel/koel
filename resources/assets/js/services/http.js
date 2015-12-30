import { extend } from 'lodash';

/**
 * Responsible for all HTTP requests.
 * 
 * IMPORTANT:
 * If the user has a good enough connection to stream music, he or she shouldn't 
 * encounter any HTTP errors. That's why Koel doesn't handle HTTP errors.
 * After all, even if there were errors, how bad can it be?
 */
export default {
    request(method, url, data, successCb = null, errorCb = null, options = {}) {
        switch (method) {
            case 'get':
                return Vue.http.get(url, data, options).then(successCb, errorCb);
            case 'post':
                return Vue.http.post(url, data, options).then(successCb, errorCb);
            case 'put':
                return Vue.http.put(url, data, options).then(successCb, errorCb);
            case 'delete':
                return Vue.http.delete(url, data, options).then(successCb, errorCb);
            default:
                break;
        }
    },

    get(url, data = {}, successCb = null, errorCb = null, options = {}) {
        return this.request('get', url, data, successCb, errorCb, options);
    },

    post(url, data, successCb = null, errorCb = null, options = {}) {
        return this.request('post', url, data, successCb, errorCb, options);
    },

    put(url, data, successCb = null, errorCb = null, options = {}) {
        return this.request('put', url, data, successCb, errorCb, options);
    },

    delete(url, data = {}, successCb = null, errorCb = null, options = {}) {
        return this.request('delete', url, data, successCb, errorCb, options);
    },

    /**
     * A shortcut method to ping and check if the user session is still valid.
     */
    ping() {
        return this.get('/');
    },
};
