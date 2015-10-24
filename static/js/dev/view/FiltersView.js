import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

import App from '../App';

const EVT_UPDATE = 'update';
const MODAL_CLASS = 'modal';

export default Backbone.View.extend({

    $sources: null,
    $sizes: null,
    $saveFilters: null,
    $body: $('body'),

    isModal: false,

    events: {
        'click button': 'handleRefreshClick',
        'change input[type="checkbox"]': 'handleCheckChange'
    },

    initialize(collection) {
        this.$sources = this.$el.find('[name="sources"]');
        this.$sizes = this.$el.find('[name="sizes"]');
        this.$saveFilters = this.$el.find('#save-filters');
        this.$body.on('click', '.show-filters', _.bind(this.showFiltersModal, this));
        this.render();
        _.extend(this, Backbone.Events);
    },

    handleRefreshClick(evt) {
        var values = {};
        var changed = false;
        this.$sources.each((index, item) => {
            values[item.getAttribute('value')] = !!item.checked;
        });

        this.collection.each((item) => {
            var value = values[item.attributes.value];
            changed = changed || item.attributes.checked === value;
            item.attributes.checked = value;
        });

        if (changed) {
            this.trigger(EVT_UPDATE, this.$saveFilters.is(':checked'));
        }

        if (this.isModal) {
            this.$el.removeClass(MODAL_CLASS);
            this.$el.detach().appendTo($('#sources'));
            App.toggleModalMode(false);
        }

    },

    showFiltersModal(evt) {
        let $el = this.$el.addClass(MODAL_CLASS).detach();
        App.toggleModalMode(true);
        this.$body.append($el);
        this.isModal = true;
    },

    handleCheckChange(evt) {
        var $target = $(evt.currentTarget);
        var selector = '[name="' + $target.attr('name') + '"]';
        var $set = this.$el.find(selector);
        var checked = $target.is(':checked');

        if ($target.val() === 'all') {
            $set.prop('checked', checked);
        } else {
            // If only one checkbox isn't checked, it's the "all", so check it
            if (checked && this.$el.find(selector + ':not(:checked)').length === 1) {
                this.$el.find(selector + '[value="all"]').prop('checked', checked);
            } else {
                this.$el.find(selector + '[value="all"]').prop('checked', false);
            }
        }
    }

});