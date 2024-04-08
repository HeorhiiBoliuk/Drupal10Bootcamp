(function (Drupal, once, drupalSettings) {
  'use strict';

  Drupal.behaviors.customMapBehavior = {
    attach: function (context, settings) {
      once('customMapBehavior', '.leaflet-map', context).forEach(function (element){
        const displayMapId = element.getAttribute('data-display-id');

        const myCustomColour = settings.customMapView[displayMapId].color;
        const myCustomSize = settings.customMapView[displayMapId].size;
        const myCustomZoom = settings.customMapView[displayMapId].zoom;

        const locations = settings.locations_stores[displayMapId][0].locations;
        const markers = L.layerGroup();

        const markerHtmlStyles = `
        background-color: ${myCustomColour};
        width: ${myCustomSize}rem;
        height: ${myCustomSize}rem;`;

        locations.forEach(function(location) {
          const latitude = location.latitude;
          const longitude = location.longitude;

          const point = [latitude, longitude];

          const marker = L.marker(point, {
            icon: L.divIcon({
              className: "my-custom-pin",
              html: `<span style="${markerHtmlStyles}" />`
            })
          });
          marker.addTo(markers);
        });



        const map = L.map(element).setView([51.505, -0.09], myCustomZoom);

        markers.addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors'
        }).addTo(map);
      });
    }
  };

})(Drupal, once, drupalSettings);
