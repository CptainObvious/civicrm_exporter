<?php declare (strict_types=1);

require_once 'prometheusexporter.civix.php';

/**
 * Implements hook_civicrm_config().
 */
function prometheusexporter_civicrm_config(&$config): void {
  _prometheusexporter_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_buildForm().
 */
function prometheusexporter_civicrm_buildForm(string $formName, &$form): void {
  if ($formName !== 'CRM_Admin_Form_Generic'
    || CRM_Utils_System::currentPath() !== 'civicrm/admin/setting/prometheusexporter') {
    return;
  }

  $scrapeUrl = rtrim((string) CRM_Utils_System::baseURL(), '/')
    . '/civicrm/prometheusmetrics';
  $markup = sprintf(
    '<div class="crm-block crm-form-block"><h3>%s</h3><p>%s</p><code>%s?token=YOUR_TOKEN</code></div>',
    ts('Prometheus scrape URL'),
    ts('Configure Prometheus to scrape this URL. Append the configured token as a query parameter, for example:'),
    htmlspecialchars($scrapeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
  );

  CRM_Core_Region::instance('page-body')->add(['markup' => $markup]);
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function prometheusexporter_civicrm_navigationMenu(&$menu): void {
  _prometheusexporter_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => ts('Prometheus Exporter'),
    'name' => ts('Prometheus Exporter'),
    'url' => CRM_Utils_System::url('civicrm/admin/setting/prometheusexporter', ['reset' => TRUE]),
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'active' => 1,
  ]);
  _prometheusexporter_civix_navigationMenu($menu);
}
