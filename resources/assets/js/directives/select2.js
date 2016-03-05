import Vue from 'vue';
import select2 from 'select2';
import $ from 'jquery';

export default {
    twoWay: true,
    priority: 1000,

    params: ['options'],

    bind: function () {
        var self = this;

        var data = [{ id: 0, text: 'enhancement' }, { id: 1, text: 'bug' }, { id: 2, text: 'duplicate' }, { id: 3, text: 'invalid' }, { id: 4, text: 'wontfix' }];


        $(this.el).select2(this.params.options)
            .on('change', function () {
                self.set(this.value);
            }
        );
    },

    update: function (value) {
        $(this.el).val(value).trigger('change');
    },

    unbind: function () {
        $(this.el).off().select2('destroy');
    }
};
