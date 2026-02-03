/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Ui/js/modal/confirm',
    'jquery',
    'ko',
    'mage/translate',
    'mage/template',
    'underscore',
    'Magento_Ui/js/modal/alert',
    'uiRegistry',
    'text!MageOS_AutomaticTranslation/template/translation-popup/language-selection.html'
], function (Component, confirm, $, ko, $t, template, _, alert, registry, selectTpl) {

    'use strict';

    return Component.extend({
        /**
         * Initialize Component
         */
        initialize: function () {
            var self = this,
                content;
            this._super();
            content = template(
                selectTpl,
                {
                    data: {
                        languages: self.languages,
                        fields: self.fields,
                        scope: self.scope,
                        languageLabel: $t('Language'),
                        fieldLabel: $t('Fields')
                    }
                }) + self.content;

            /**
             * Confirmation popup
             *
             * @param {String} url
             * @returns {Boolean}
             */
            window.mageosTranslationPopup = function (url) {
                confirm({
                    title: self.title,
                    content: content,
                    modalClass: 'confirm mageos-translation',
                    actions: {
                        /**
                         * Confirm action.
                         */
                        confirm: function () {
                            let language = $('#mageos-translation-language').val(),
                                fieldsCheckboxes = $('#mageos-translation-fields [name="field[]"]'),
                                scope = $('#mageos-translation-scope').val(),
                                form = registry.get(scope),
                                fields = [];

                            _.each(fieldsCheckboxes, function (value, name) {
                                fieldsCheckboxes[name].checked ? fields.push($(fieldsCheckboxes[name]).val()) : fields;
                            });

                            form.source.set('data.translate', true);
                            form.source.set('data.translationLanguage', language);
                            form.source.set('data.translationFields', fields);
                            form.submit(false);
                        }
                    },
                    buttons: [{
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss',

                        /**
                         * Click handler.
                         */
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }, {
                        text: $t('Save & Translate'),
                        class: 'action-secondary action-accept',

                        /**
                         * Click handler.
                         */
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }]
                });

                return false;
            };
        }
    });
});
