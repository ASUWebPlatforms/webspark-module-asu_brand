<?php

/**
 * @file
 * Conttains Drupal\asu_brand\Form\AsuBrandSettingsForm
 */

namespace Drupal\asu_brand\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AsuBrandSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'asu_brand.settings'; // Config variable name for module.

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Returns the form's unique ID.
    return 'asu_brand_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // Gets the configuration name.
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build and return the form array.

    // Loads admin settings for this form/module.
    $config = $this->config(static::SETTINGS);

    // You gotta give props to our Components Library Header! No, literally:
    // navTree - automatically built by this module, based on menu injection flag and asu_component_header_sitemenu_name settings.
    // title - implemented as asu_component_header_title setting.
    // baseUrl - implemented, derived from Drupal settings.
    // parentOrg - implemented as asu_component_header_parent_org setting.
    // parentOrgUrl - implemented as asu_component_header_parent_org_url setting.
    // logo - TODO HOLD for future, to support partner logos?
    // loggedIn - relies on default component behavior + details from us
    // userName - relies on default component behavior + details from us
    // loginLink - TODO
    // logoutLink - TODO
    // buttons - TODO HOLD for future dev.
    // breakpoint - relies on default component behavior
    // animateTitle - relies on default component behavior

    //dpm($config);

    // Global configs

    $form['asu_brand']['asu_brand_instructions'] = [
      '#type' => 'item',
      '#title' => $this->t("About the ASU Brand module"),
      '#description' => $this->t("This module supplies key features for
        maintaining and displaying the ASU brand correctly in your site. It
        bundles the ASU Header, Cookie Consent for GDPR compliance and ASU
        Google Tag Manager code into one package. The header is controlled
        and configured through this site's blocks interface. Depending on
        how you installed your site, the ASU global header may already be
        enabled. Cookie consent and Google Tag Manager are configured below.
        For most sites, the defaults are recommended."),
    ];
    // Cookie Consent [enabled]
    $form['asu_brand']['asu_brand_cookie_consent_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ASU Cookie Consent'),
      '#default_value' =>
      $config->get('asu_brand.asu_brand_cookie_consent_enabled'),
      '#description' => $this->t("Required for all public-facing sites to
        ensure GDPR compliance."),
    ];
    // GTM enabled
    $form['asu_brand']['asu_brand_gtm_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ASU Universal Google Tag Manager'),
      '#default_value' => $config->get('asu_brand.asu_brand_gtm_enabled'),
      '#description' => $this->t("Recommended for all public-facing ASU sites"),
    ];
    // GTM add'l ID - just one additional supported for now.
    $form['asu_brand']['asu_brand_extra_gtm_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Google Tag Manager ID (GTM ID)'),
      '#default_value' => $config->get('asu_brand.asu_brand_extra_gtm_id'),
      '#description' => $this->t("By default the ASU Universal GTM ID
        (GTM-KDWN8Z) is used. If you supply an additional GTM ID, another GTM
        container with that new ID will be added to your site's HTML. This
        setting is independent of whether the ASU Universal GTM is enabled."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process the form submission.

    // Note: Use asu_brand. prefix when handling config values, but not with
    // form_state versions of those values.

    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configurations on our config.
      ->set('asu_brand.asu_brand_cookie_consent_enabled', $form_state->getValue('asu_brand_cookie_consent_enabled'))
      ->set('asu_brand.asu_brand_gtm_enabled', $form_state->getValue('asu_brand_gtm_enabled'))
      ->set('asu_brand.asu_brand_extra_gtm_id', $form_state->getValue('asu_brand_extra_gtm_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
