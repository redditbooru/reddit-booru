import Backbone from 'backbone';

export default Backbone.Model.extend({
    defaults: function() {
        return {
            title: '',
            value: '',
            name: '',
            checked: false
        };
    }
});