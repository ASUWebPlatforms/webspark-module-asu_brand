(function ($, Drupal, drupalSettings) {
  // Not using behaviors for most of this.
  Drupal.behaviors.AsuBrandHeaderBehavior = {
    attach: function (context, settings) {

      // If toolbar detected, add compatibilty classes for spacing.
      if (document.body.classList.contains('toolbar-fixed')) {
        var headerElement = document.getElementById("headerContainer");
        if (headerElement) {
          headerElement.classList.add("asu-brand-toolbar-header-tray-open-compat");
        }
      }
      // For related functionality, see mutationObserver below.

    }
  };

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

  // Setup a mutation observer and watch body class attributes so we can base
  // header toolbar spacing compatibility classes on that.
  // Ref: https://www.seanmcp.com/articles/event-listener-for-class-change/
  function mutationCallback(mutationsList) {
    mutationsList.forEach(mutation => {
      if (mutation.attributeName === 'class') {
        //console.log(mutation, 'mutation');

        // A class change happened on the body tag. If the header is on the
        // page, check if the class change was made by a toolbar change and
        // apply our CSS class names to adapt dynamically if so.

        var headerElement = document.getElementById("headerContainer");
        if (headerElement) {
          var classSuffix = "";
          var vertSuffix = "-vertical";
          // Check if we need to modify classSuffix for vertical tray rules.
          if (document.body.classList.contains('toolbar-vertical')) {
            classSuffix = vertSuffix; // "-vertical"
          }
          else {
            classSuffix = ""; // reset
          }
          // Set open state class
          if (document.body.classList.contains('toolbar-tray-open')) {
              // Always clean up everything first.
              headerElement.classList.remove("asu-brand-toolbar-header-tray-closed-compat" + vertSuffix);
              headerElement.classList.remove("asu-brand-toolbar-header-tray-closed-compat");
              headerElement.classList.remove("asu-brand-toolbar-header-tray-open-compat" + vertSuffix);
              headerElement.classList.remove("asu-brand-toolbar-header-tray-open-compat");
              // Set for current state.
              headerElement.classList.add("asu-brand-toolbar-header-tray-open-compat" + classSuffix);
          }
          // Set closed state class
          else {
            // Always clean up everything.
            headerElement.classList.remove("asu-brand-toolbar-header-tray-open-compat" + vertSuffix);
            headerElement.classList.remove("asu-brand-toolbar-header-tray-open-compat");
            headerElement.classList.remove("asu-brand-toolbar-header-tray-closed-compat" + vertSuffix);
            headerElement.classList.remove("asu-brand-toolbar-header-tray-closed-compat");
            // Set for current state.
            headerElement.classList.add("asu-brand-toolbar-header-tray-closed-compat" + classSuffix);
          }
        }

      }
    })
  }
  var mutationObserver = new MutationObserver(mutationCallback);
  mutationObserver.observe( document.body, { attributes: true } );


  // TODO If needed: update the CAS URL to append return path to sign in:
  // '?destination=' + window.location.pathname

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
// Is it required by Drupal or drupalSettings? Would like it working w/o jQuery.
})(jQuery, Drupal, drupalSettings);
