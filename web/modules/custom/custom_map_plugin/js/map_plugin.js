(function (Drupal, once, drupalSettings) {
  'use strict';

  Drupal.behaviors.customMapBehavior = {
    attach: function (context, settings) {
      once('customMapBehavior', '#leaflet-map', context).forEach(function (){
        const myCustomColour = settings.customMapView.color;
        const myCustomSize = settings.customMapView.size;
        const myCustomZoom = settings.customMapView.zoom;

        const locations = settings.locations_stores.locations;

        const markers = L.layerGroup();

        const markerHtmlStyles = `
        background-color: ${myCustomColour};
        width: ${myCustomSize}rem;
        height: ${myCustomSize}rem;
        display: block;
        left: -1.5rem;
        top: -1.5rem;
        position: relative;
        border-radius: 3rem 3rem 0;
        transform: rotate(45deg);
        border: 1px solid #FFFFFF;`;

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

        const map = L.map('leaflet-map').setView([51.505, -0.09], myCustomZoom);

        markers.addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors'
        }).addTo(map);
      });
    }
  };

})(Drupal, once, drupalSettings);
