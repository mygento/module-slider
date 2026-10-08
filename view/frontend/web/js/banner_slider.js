define(['Mygento_Slider/js/vendor/splide.min'], function (Splide) {
  'use strict';

  return function (config, element) {
    const banner = new Splide(element, config);

    if (!config.thumbnails) {
      banner.mount();
      return;
    }

    const thumbnails = new Splide(config.thumbnailsElement, config.thumbnails);

    banner.sync( thumbnails );
    banner.mount();
    thumbnails.mount();
  };
});