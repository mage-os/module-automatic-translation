define([
    'Magento_Ui/js/form/components/html',
    'underscore'
], function (Html, _) {
    'use strict';

    return Html.extend({
        defaults: {
            translationSwitcherMessage: '',
            storeSwitchUrl: '',
            noStoresMessage: '',
            translationStores: [],
            stores: '',
            content:        '',
            showSpinner:    false,
            loading:        false,
            visible:        true,
            template:       'ui/content/content',
            additionalClasses: {},
            ignoreTmpls: {
                content: true
            }
        },

        /**
         * Content getter
         *
         * @returns {String}
         */
        getContentUnsanitizedHtml: function () {
            let html = '';
            let self = this;
            if (!_.isEmpty(this.translationStores)) {
                html = '<p>' + this.translationSwitcherMessage + '</p>';
                html += '<nav><ul class="store-translation-url-list">';
                _(this.translationStores).each(function (storeName, storeId) {
                    var storeParam = 'store/' + storeId + '/';
                    html += '<li><a href="' + self.storeSwitchUrl + storeParam + '">' + storeName + '</a></li>';
                });
                html += '</ul></nav>';
            } else {
                html = '<p>' + this.noStoresMessage + '</p>';
            }
            return html;
        }
    });
});
