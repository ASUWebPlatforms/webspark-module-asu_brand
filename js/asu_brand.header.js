(function ($, Drupal, drupalSettings) {
  // Not using behaviors for this.
  // Drupal.behaviors.AsuBrandHeaderBehavior = {
  //   attach: function (context, settings) {
  //     console.log('AsuBrandHeaderBehavior happened');
  //   }
  // };

  // Probably don't want this inside the behavior. Fire once and be done!
  // Get config values passed in from AsuBrandHeaderBlock.php
  var props = drupalSettings.asu_brand.props;

  // Pantheon strips some cookie values before they hit PHP, so
  // Attempt to get userName prop in JS here for those instances.
  var name = 'SSONAME=';
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      // Patch SSONAME into props.
      props.userName = c.substring(name.length, c.length);
    }
  }

  // If we find the current path in top level items, set selected prop, aka
  // active. We do this client side to avoid needing to break the block cache.
  var currentPath = window.location.pathname+window.location.search;
  for (var index = 0; index < props.navTree.length; index++) {
    if (props.navTree[index]['href'] === currentPath) {
      props.navTree[index]['selected'] = true;
    }
  }

  // Initialize the asu_brand components-library header.
  componentsLibrary.initHeader(props);

  // Initialize the cookie-consent banner component.
  // window.addEventListener("DOMContentLoaded", event => {
  //   // Optional props to provide.
  //   var props = { asuCookieDomain = "asu.edu" };
  //   // Initialize cookie consent banner
  //   AsuCookieConsent.init(props);
  // })

// TODO Without jQuery, we get Uncaught ReferenceError: jQuery is not defined.
// Something must be calling it. Get this working without jQuery.
})(jQuery, Drupal, drupalSettings);
