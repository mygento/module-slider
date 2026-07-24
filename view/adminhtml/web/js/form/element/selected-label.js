define([
  'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
  'use strict';

  return Abstract.extend({
    initialize: function () {
      this._super();
      this.value.subscribe(this.updateVisibility, this);
      this.updateVisibility(this.value());

      return this;
    },

    updateVisibility: function (value) {
      this.visible(!!value);

      return this;
    }
  });
});
