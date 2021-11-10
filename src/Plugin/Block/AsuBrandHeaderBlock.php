<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu;
use Drupal\Component\Utility\Html;
//use Drupal\Core\Menu\MenuLinkTree;
//use Drupal\Component\Utility\UrlHelper;
//use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides the ASU brand header block which deploys the component header.
 *
 * @Block(
 *   id = "asu_brand_header",
 *   admin_label = @Translation("ASU brand header"),
 *   category = @Translation("ASU brand"),
 * )
 */
class AsuBrandHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Pass data from php:
    // https://codimth.com/blog/web/drupal/passing-data-php-javascript-drupal-8

    // Pull in block-level configs (see blockForm()). Drupal manages deltas.
    $config = $this->getConfiguration();

    // Rally props to pass to JS as drupalSettings.
    $props = [];
    $props['baseUrl'] = base_path();
    $props['title'] = $config['asu_brand_header_block_title'];
    $props['parentOrg'] = $config['asu_brand_header_block_parent_org'];
    $props['parentOrgUrl'] = $config['asu_brand_header_block_parent_org_url'];
    $props['expandOnHover'] = $config['asu_brand_header_block_expand_on_hover'];
    $props['loginLink'] = $config['asu_brand_header_block_login_path'];
    $props['logoutLink'] = $config['asu_brand_header_block_logout_path'];
    // TODO Further refine? There are styling issues w buttons IN the component.
    //      For now, we hardcode the colors that look best.
    if ($config['asu_brand_header_block_cta1_url']) {
      $props['buttons'][] = [
        "href" => $config['asu_brand_header_block_cta1_url'],
        "text" => $config['asu_brand_header_block_cta1_label'],
        //"size" => "medium",
        "color" => $config['asu_brand_header_block_cta1_style']
      ];
    }
    if ($config['asu_brand_header_block_cta2_url']) {
      $props['buttons'][] = [
        "href" => $config['asu_brand_header_block_cta2_url'],
        "text" => $config['asu_brand_header_block_cta2_label'],
        //"size" => "medium",
        "color" => $config['asu_brand_header_block_cta2_style']
      ];
    }

    // Override component's default behavior for logged in/out detection to
    // rely on Drupal instead, via passing in props.
    if ($config['asu_brand_header_block_sync_session']) {
      $current_uid = \Drupal::currentUser()->id();
      if ($current_uid > 0) {
        $props['loggedIn'] = TRUE;
        // If we have the SSONAME cookie, use that name. Fallback to
        // "You are logged in" since using the username results in cache issues
        // given the per-role cache. Caching per user causes the header to
        // break for some reason. See also JS solve we have as a backup for
        // when Pantheon strips cookies, in asu_brand.header.js.
        $props['userName'] = isset($_COOKIE['SSONAME']) ? Html::escape($_COOKIE['SSONAME']) : t('You are logged in');
      } else { // Force header to match Drupal login state even if there's a SSO session.
        $props['loggedIn'] = FALSE;
        $props['userName'] = '';
      }
    }

    // Build navTree prop if menu insert is enabled in block config.
    $navTree = [];
    if ($config['asu_brand_header_block_menu_enabled']) {
      $navTree = $this->getNavTree($config['asu_brand_header_block_menu_name']);
    }
    $props['navTree'] = $navTree;

    $block_output = [];
    // Markup containers where components will initialize.
    $block_output['#markup'] =
      $this->t('
        <!-- Header component will be initialized in this container. -->
        <div id="headerContainer"></div>
        <!-- Cookie Consent component will be initialized in this container. -->
        <div id="cookieConsentContainer" class="cookieConsentContainer"></div>');
    $tag_menu = $config['asu_brand_header_block_menu_enabled'] ? $config['asu_brand_header_block_menu_name'] : 'main';
    $block_output['#cache'] = [
      'contexts' => $this->getCacheContexts(),
      // Break cache when block or menus change.
      'tags' => Cache::mergeTags($this->getCacheTags(), Cache::buildTags('config:system.menu', [$tag_menu], '.')),
    ];
    // Attach components and helper js registered in asu_brand.libraries.yml
    $block_output['#attached']['library'][] = 'asu_brand/components-library';
    // Pass block configs to javascript. Gets taken up in js/asu_brand.header.js
    $block_output['#attached']['drupalSettings']['asu_brand']['props'] = $props;
    // Get and pass cookie consent status, too.
    $global_config = \Drupal::config('asu_brand.settings');
    $block_output['#attached']['drupalSettings']['asu_brand']['cookie_consent'] = $global_config->get('asu_brand.asu_brand_cookie_consent_enabled');

    return $block_output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // TODO We should really only use the user context due to our current
    // username fallback above, but that breaks the component for some reason.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get system menu options.
    $menu_options = menu_ui_get_menus();

    // Currently unimplemented config items for props:
    // logo TODO for future dev
    // expandOnHover TODO for future dev
    // mobileNavTree TODO for future dev
    // breakpoint TODO for future dev

    // We localize most header settings to the block form to better support
    // microsites and subsites.

    // Config for this instance.
    $config = $this->getConfiguration();

    $form['asu_brand_header_block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#description' => $this->t('Site title to appear in the header.'),
      '#default_value' => isset($config['asu_brand_header_block_title']) ?
        $config['asu_brand_header_block_title'] : \Drupal::config('system.site')->get('name'),
      '#required' => TRUE
    ];
    $form['asu_brand_header_block_parent_org'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent unit name'),
      '#description' => $this->t('Optional. Name of the parent unit.'),
      '#default_value' => isset($config['asu_brand_header_block_parent_org']) ?
        $config['asu_brand_header_block_parent_org'] : '',
      '#required' => FALSE
    ];
    $form['asu_brand_header_block_parent_org_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Parent Department URL'),
      '#description' => $this->t('Optional. URL of the parent unit.'),
      '#default_value' => isset($config['asu_brand_header_block_parent_org_url']) ?
        $config['asu_brand_header_block_parent_org_url'] : '',
      '#required' => FALSE
    ];
    $form['asu_brand_header_block_sync_session'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync login status with Drupal session'),
      '#description' => $this->t('Recommended. Keeps the header\'s login/out
        status synced with the users\'s Drupal session. If disabled, the header
        will reflect the user\'s single-sign on status and they may become
        confused about whether they are logged in or not.'),
      '#default_value' => isset($config['asu_brand_header_block_sync_session']) ?
        $config['asu_brand_header_block_sync_session'] : 1,
    ];
    $form['asu_brand_header_block_menu_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Insert menu into header'),
      '#description' => $this->t('Insert a site menu into the ASU header and display it responsively. Important note: the first enabled menu link will always be treated as the home menu link and will be converted into a home icon. To change which menu link is used as home, reorder your menu links.'),
      '#default_value' => isset($config['asu_brand_header_block_menu_enabled']) ?
        $config['asu_brand_header_block_menu_enabled'] : 1,
    ];
    $form['asu_brand_header_block_menu_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu to insert'),
      '#description' => $this->t('Select the menu to insert.'),
      '#options' => $menu_options,
      '#default_value' => isset($config['asu_brand_header_block_menu_name']) ?
        $config['asu_brand_header_block_menu_name'] : 'main',
      '#states' => array(
        // Display this field when the menu is enabled.
        'visible' => array(
          ':input[name="settings[asu_brand_header_block_menu_enabled]"]' => array(
            'checked' => TRUE,
          ),
        ),
      ),
    ];
    $form['asu_brand_header_block_expand_on_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand on hover'),
      '#description' => $this->t('If enabled, menu dropdowns will expand on hover. Allows for top-level menu items with children to be clickable as navigation destinations.'),
      '#default_value' => isset($config['asu_brand_header_block_expand_on_hover']) ?
        $config['asu_brand_header_block_expand_on_hover'] : 0,
      '#states' => array(
        // Display this field when the menu is enabled.
        'visible' => array(
          ':input[name="settings[asu_brand_header_block_menu_enabled]"]' => array(
            'checked' => TRUE,
          ),
        ),
      ),
    ];
    $form['asu_brand_header_block_login_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login path'),
      '#description' => $this->t('Login path for the site.'),
      '#default_value' => isset($config['asu_brand_header_block_login_path']) ?
        $config['asu_brand_header_block_login_path'] : '/caslogin',
      '#required' => TRUE
    ];
    $form['asu_brand_header_block_logout_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout path'),
      '#description' => $this->t('Logout path for the site.'),
      '#default_value' => isset($config['asu_brand_header_block_logout_path']) ?
        $config['asu_brand_header_block_logout_path'] : '/caslogout',
      '#required' => TRUE
    ];
    $form['asu_brand_header_block_cta1_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call to action button 1 label'),
      '#default_value' => isset($config['asu_brand_header_block_cta1_label']) ?
        $config['asu_brand_header_block_cta1_label'] : '',
    ];
    $form['asu_brand_header_block_cta1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call to action button 1 URL or path'),
      '#default_value' => isset($config['asu_brand_header_block_cta1_url']) ?
        $config['asu_brand_header_block_cta1_url'] : '',
      '#states' => array(
        // Require this field when the label is filled.
        'required' => array(
          ':input[name="settings[asu_brand_header_block_cta1_label]"]' => array(
            'filled' => TRUE,
          ),
        ),
      ),
    ];

    $style_options = [
        'gold' => 'Gold',
        'maroon' => 'Maroon',
        'light' => 'Gray 2',
        'dark' => 'Gray 7',
      ];
    $form['asu_brand_header_block_cta1_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Call to action button 1 style'),
      '#default_value' => isset($config['asu_brand_header_block_cta1_style']) ?
        $config['asu_brand_header_block_cta1_style'] : '',
      '#options' => $style_options,
    ];
    $form['asu_brand_header_block_cta2_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call to action button 2 label'),
      '#default_value' => isset($config['asu_brand_header_block_cta2_label']) ?
        $config['asu_brand_header_block_cta2_label'] : '',
    ];
    $form['asu_brand_header_block_cta2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call to action button 2 URL or path'),
      '#default_value' => isset($config['asu_brand_header_block_cta2_url']) ?
        $config['asu_brand_header_block_cta2_url'] : '',
      '#states' => array(
        // Require this field when the label is filled.
        'required' => array(
          ':input[name="settings[asu_brand_header_block_cta2_label]"]' => array(
            'filled' => TRUE,
          ),
        ),
      ),
    ];

    $form['asu_brand_header_block_cta2_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Call to action button 2 style'),
      '#default_value' => isset($config['asu_brand_header_block_cta2_style']) ?
        $config['asu_brand_header_block_cta2_style'] : '',
      '#options' => $style_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['asu_brand_header_block_title'] =
      $values['asu_brand_header_block_title'];
    $this->configuration['asu_brand_header_block_parent_org'] =
      $values['asu_brand_header_block_parent_org'];
    $this->configuration['asu_brand_header_block_parent_org_url'] =
      $values['asu_brand_header_block_parent_org_url'];
    $this->configuration['asu_brand_header_block_sync_session'] =
      $values['asu_brand_header_block_sync_session'];
    $this->configuration['asu_brand_header_block_menu_enabled'] =
      $values['asu_brand_header_block_menu_enabled'];
    $this->configuration['asu_brand_header_block_menu_name'] =
      $values['asu_brand_header_block_menu_name'];
    $this->configuration['asu_brand_header_block_expand_on_hover'] =
      $values['asu_brand_header_block_expand_on_hover'];
    $this->configuration['asu_brand_header_block_login_path'] =
      $values['asu_brand_header_block_login_path'];
    $this->configuration['asu_brand_header_block_logout_path'] =
      $values['asu_brand_header_block_logout_path'];
    $this->configuration['asu_brand_header_block_cta1_label'] =
      $values['asu_brand_header_block_cta1_label'];
    $this->configuration['asu_brand_header_block_cta1_url'] =
      $values['asu_brand_header_block_cta1_url'];
    $this->configuration['asu_brand_header_block_cta1_style'] =
      $values['asu_brand_header_block_cta1_style'];
    $this->configuration['asu_brand_header_block_cta2_label'] =
      $values['asu_brand_header_block_cta2_label'];
    $this->configuration['asu_brand_header_block_cta2_url'] =
      $values['asu_brand_header_block_cta2_url'];
    $this->configuration['asu_brand_header_block_cta2_style'] =
      $values['asu_brand_header_block_cta2_style'];
  }

  /**
   * Build menu array for inclusion in header component navTree prop.
   *
   * @param array $menu_name A menu tree's machine name.
   */
  function getNavTree($menu_name) {

    $menu_tree = \Drupal::menuTree();
    // Load the entire tree.
    $parameters = new MenuTreeParameters();
    // Limit to enabled items.
    $parameters->onlyEnabledLinks();

    // Optionally set active trail.
    $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
    $parameters->setActiveTrail($menu_active_trail);

    // Load the tree based on this set of parameters.
    $tree = $menu_tree->load($menu_name, $parameters);
    // Transform the tree using the manipulators you want.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    // Finally, build a renderable array from the transformed tree.
    $menu_tmp = $menu_tree->build($tree);
    $navTree = [];
    foreach ($menu_tmp['#items'] as $item) {

      // BUILD LEVEL 2 first, if extant, to put under parent.
      $childItems = [];
      $childTrayButtons = [];
      if (!empty($item['below'])) {
        foreach ($item['below'] as $child) {

          // Get values from menu link custom fields we have added.
          $child_link_custom_values = $this->getMenuLinkCustomValues($child['original_link']);

          // Note on buttons: In childItems, we have two types of buttons:
          // 1. column buttons, denoted by childItem's link_type
          // 2. dropdown tray buttons, denoted by childItem's is_button flag

          if ($child_link_custom_values['is_button']) {
            // Dropdown tray buttons and
            $childTrayButtons[] = [
              'href' => $child['url']->toString(),
              'text' => $child['title'],
              'color' => $child_link_custom_values['button_color'],
            ];
          } else {
            // Set all other menu link childItems, including link_type's:
            // heading && button
            $childItems[] = [
              'href' => $child['url']->toString(),
              'text' => $child['title'],
              'type' => $child_link_custom_values['link_type'],
            ];
          }
        }

        // Sort child items into column formatting for navTree prop.
        $childItemCols = $this->sortChildLinksToCols($childItems);
      }

      // Get values from menu link custom fields we have added.
      $item_link_custom_values = $this->getMenuLinkCustomValues($item['original_link']);

      // BUILD LEVEL 1
      $navTree[] = [
        'href' => $item['url']->toString(),
        'text' => $item['title'],
        'items' => !empty($item['below']) ? $childItemCols : '',
        'buttons' => !empty($childTrayButtons) ? $childTrayButtons : '',
      ];
    }
    // First item always becomes Home icon.
    //$navTree[0]['href'] = base_path();
    //$navTree[0]['text'] = t('Home');
    $navTree[0]['type'] = 'icon';
    $navTree[0]['class'] = 'home';
    return $navTree;
  }


  /**
   * Helper function to get custom menu link field values.
   */
  private function getMenuLinkCustomValues($link) {

    // Get custom fields we've added to menu links using the following approach
    // https://drupal.stackexchange.com/questions/235754/get-menu-link-item-from-menulinktreeelement
    // Maybe not the most OO way to go, but it works.
    $link_type = null;
    $is_button = null;
    $button_color = null;
    if ($link instanceof \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent) {
      $link_uuid = $link->getDerivativeId();
      $link_entity = \Drupal::service('entity.repository')
        ->loadEntityByUuid('menu_link_content', $link_uuid);
      $link_type = $link_entity->menu_link_asu_brand_link_type->value;
      $is_button = $link_entity->menu_link_asu_brand_link_is_button->value;
      $button_color = $link_entity->menu_link_asu_brand_link_button_color->value;
    }

    return ['link_type' => $link_type, 'is_button' => $is_button, 'button_color' => $button_color];
  }

  /**
   * Helper function to sort child menu links array into columns for navTree.
   */
  private function sortChildLinksToCols($childItems) {
    $col = 0;
    $tripwire = false;
    $childItemCols = [];
    foreach ($childItems as $k => $v) {
      // Break out into columns if we have headings or column breaks.
      if ($tripwire && ($v['type'] === "heading" || $v['type'] === "column break")) {
        $col++;
      }
      $childItemCols[$col][] = $v;
      // We want first heading/column to stay in 0, so trigger here.
      // All subsequent passes will use new columns.
      $tripwire = true;
    }
    return $childItemCols;
  }
}

/* DEV NOTES

TODO Add Cookie Consent
TODO Create improvement ticket for admin UI: Capture ideas
  - menu handling limits depth to 2 for us. Matches standard. Document in UI.
  - add menu configs column to menu list adming table showing link item settings
  - js not working without including jQuery, which is unused... drupalSettings or Drupal js libs' dependency maybe?

TODO add a test (see other asu_brand for example) - confirm:
  - enabled menu items show
  - disabled menu items don't show
  - menu items at lower levels don't show
  - menu features display
  - login link matches login status

Sample Props and navTree structures
See Storybook Knobs:
https://unity.web.asu.edu/@asu-design-system/components-library/index.html?path=/story/header--base

navTree array structure, rough reference
  href
  text
  type : one of icon (home)
  class TODO not implemented. Needed?
  selected
  buttons
    text
    href
    color
  items: (each array under is a column)
    href
    text
    type : one of heading, button
    classes TODO not implmented. Needed?

*/
