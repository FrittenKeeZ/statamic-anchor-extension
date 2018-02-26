(function($) {
    'use strict';

    const addons = window.Addons || {},
        anchor = addons.Anchor || false;
    if (anchor) {
        // Helper to create selects mirroring 'SelectFieldtype.vue' component.
        const createSelect = function (options, selected) {
            const $container = $('<div>', {'class': 'select'});
            const $select = $('<select>');
            _.each(options, function (option, index) {
                $select.append($('<option>', option));
            });
            if (selected) {
                $select.val(selected);
            }
            $select.on('change', function () {
                $container.attr('data-content', $select.children(':selected').text());
            }).trigger('change');

            return $container.append($select);
        };

        // Helper for checking page link.
        const isPageLink = function (value) {
            return value.length === 0 || value.indexOf('page:') === 0;
        };

        // Override and extend MediumEditor's Anchor extension.
        const AnchorForm = MediumEditor.extensions.anchor.extend({
            /* Anchor Form Options */

            /* customClassOption: [string]  (previously options.anchorButton + options.anchorButtonClass)
             * Custom class name the user can optionally have added to their created links (ie 'button').
             * If passed as a non-empty string, a checkbox will be displayed allowing the user to choose
             * whether to have the class added to the created link or not.
             */
            customClassOption: anchor.options.customClassOption,

            /* customClassOptionText: [string]
             * text to be shown in the checkbox when the __customClassOption__ is being used.
             */
            customClassOptionText: anchor.options.customClassOptionText,

            /* linkValidation: [boolean]  (previously options.checkLinkFormat)
             * enables/disables check for common URL protocols on anchor links.
             */
            linkValidation: anchor.options.linkValidation,

            /* placeholderText: [string]  (previously options.anchorInputPlaceholder)
             * text to be shown as placeholder of the anchor input.
             */
            placeholderText: anchor.options.placeholderText,

            /* targetCheckbox: [boolean]  (previously options.anchorTarget)
             * enables/disables displaying a "Open in new window" checkbox, which when checked
             * changes the `target` attribute of the created link.
             */
            targetCheckbox: anchor.options.targetCheckbox,

            /* targetCheckboxText: [string]  (previously options.anchorInputCheckboxLabel)
             * text to be shown in the checkbox enabled via the __targetCheckbox__ option.
             */
            targetCheckboxText: anchor.options.targetCheckboxText,

            showForm: function (opts) {
                MediumEditor.extensions.anchor.prototype.showForm.apply(this, arguments);

                const input = this.getInput()
                const $anchorTypeSelect = $('#medium-editor-toolbar-anchor-link-' + this.getEditorId() + ' select');
                const $anchorLinkSelect = $('#medium-editor-toolbar-anchor-type-' + this.getEditorId() + ' select');
                if (isPageLink(input.value)) {
                    $anchorTypeSelect.val(input.value).trigger('change');
                    $anchorLinkSelect.val('intern').trigger('change');
                } else {
                    $anchorLinkSelect.val('extern').trigger('change');
                }
            },

            createForm: function () {
                const form = MediumEditor.extensions.anchor.prototype.createForm.apply(this);
                const $anchorType = createSelect(anchor.types, anchor.types[0].value)
                    .attr('id', 'medium-editor-toolbar-anchor-type-' + this.getEditorId())
                    .addClass('medium-editor-toolbar-select medium-editor-toolbar-select-slim');
                const $anchorLink = createSelect(anchor.pages.nested)
                    .attr('id', 'medium-editor-toolbar-anchor-link-' + this.getEditorId())
                    .addClass('medium-editor-toolbar-select medium-editor-toolbar-select-wide');

                // Wait until the form is rendered before attaching events.
                _.defer(_.bind(function () {
                    const $anchorTypeSelect = $anchorType.find('select');
                    const $anchorLinkSelect = $anchorLink.find('select');
                    const $input = $(this.getInput());

                    $anchorLinkSelect.on('change.anchor', function () {
                        $input.val(this.value);
                    });

                    $anchorTypeSelect.on('change.anchor', function () {
                        if (this.value == 'intern') {
                            $anchorLink.removeClass('hidden');
                            $input.addClass('hidden');
                            if (! isPageLink($input.val())) {
                                $anchorLinkSelect.val('').trigger('change');
                            }
                        }

                        if (this.value == 'extern') {
                            $anchorLink.addClass('hidden');
                            $input.removeClass('hidden');
                            if (isPageLink($input.val())) {
                                $anchorLinkSelect.val('').trigger('change');
                                $input.val('').trigger('change');
                            }
                        }
                    }).trigger('change.anchor');
                }, this));

                // Fix custom class option checkbox.
                // Should be fixed at some point in MediumEditor.
                if (this.customClassOption) {
                    const buttonId = 'medium-editor-toolbar-anchor-button-field-' + this.getEditorId();
                    $(form)
                        .find('.medium-editor-toolbar-anchor-button')
                        .attr('id', buttonId)
                        .next('label')
                        .attr('for', buttonId);
                }

                $(form).prepend($anchorLink).prepend($anchorType);

                return form;
            },

            checkLinkFormat: function (value) {
                if (isPageLink(value)) {
                    return value;
                }

                return MediumEditor.extensions.anchor.prototype.checkLinkFormat.apply(this, arguments);
            }
        });

        // Override and extend MediumEditor's Anchor Preview extension.
        const AnchorPreview = MediumEditor.extensions.anchorPreview.extend({
            showPreview: function (anchorEl) {
                const instance = MediumEditor.extensions.anchorPreview.prototype.showPreview.apply(this, arguments);

                if (this.previewValueSelector) {
                    const text = anchor.pages.inline[anchorEl.attributes.href.value] || anchorEl.attributes.href.value;
                    this.anchorPreview.querySelector(this.previewValueSelector).textContent = text;
                }

                return instance;
            }
        });

        // Assign overrides to Statamic's extensions.
        Statamic.MediumEditorExtensions.anchor = AnchorForm;
        Statamic.MediumEditorExtensions.anchorPreview = AnchorPreview;
    }

}(jQuery));
