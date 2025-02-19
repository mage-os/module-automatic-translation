define([
    'uiClass',
    'jquery'
], function (Class, $) {
    'use strict';

    return Class.extend({

        /**
         * Initialize actions and adapter.
         *
         * @param {Object} config
         * @param {Element} elem
         * @returns {Object}
         */
        initialize: function (config, elem) {
            return this._super()
                .updatePageActionsClass();
        },

        /**
         * Remove .actions-scrollable class from category form adminhtml page.
         *
         * @returns {Object}
         */
        updatePageActionsClass: function () {
            $("main.page-content > .page-main-actions").removeClass("actions-scrollable");
            return this;
        }
    });
});
