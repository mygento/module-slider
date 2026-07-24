define([
  'Magento_Ui/js/form/element/select',
  'uiRegistry'
], function (Select, registry) {
  'use strict';

  return Select.extend({
    defaults: {
      clearTargets: []
    },

    onUpdate: function () {
      this.clearTargets.forEach(function (target) {
        registry.get(target, function (component) {
          if (component) {
            component.clear();
          }
        });
      });

      return this._super();
    }
  });
});
