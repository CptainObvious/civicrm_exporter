<?php declare (strict_types=1);

/**
 * Settings metadata for the Prometheus Exporter extension.
 *
 * This registers a single setting - a shared secret that Prometheus (or
 * anything else scraping the endpoint) must supply. Set it with e.g.:
 *
 *   cv ev "Civi::settings()->set('prometheusexporter_token', 'CHANGE-ME');"
 *
 * or via API4:
 *
 *   cv api4 Setting.set +v prometheusexporter_token=CHANGE-ME
 */
return [
  'prometheusexporter_token' => [
    'name' => 'prometheusexporter_token',
    'type' => 'String',
    'html_type' => 'text',
    'default' => '',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => ts('Prometheus Exporter Token'),
    'is_required' => FALSE,
    'description' => ts('Shared secret required to access the /civicrm/prometheus-metrics endpoint, either as a ?token=... query parameter or an "Authorization: Bearer <token>" header. Leave empty to disable the endpoint entirely.'),
    'help_text' => ts('Generate a long random value; treat it like a password.'),
    'settings_pages' => ['prometheusexporter' => ['weight' => 10]],
  ],
  'prometheusexporter_ip_allowlist' => [
    'name' => 'prometheusexporter_ip_allowlist',
    'type' => 'String',
    'html_type' => 'textarea',
    'html_attributes' => ['rows' => 4, 'cols' => 60],
    'default' => '',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => ts('Prometheus Exporter IP Allowlist'),
    'is_required' => FALSE,
    'description' => ts('One IP address or CIDR range per line (or comma-separated), e.g. 203.0.113.10 or 10.0.0.0/8. Supports IPv4 and IPv6. Leave empty to allow any IP (token is still required).'),
    'help_text' => ts('Leave empty to allow any IP address.'),
    'settings_pages' => ['prometheusexporter' => ['weight' => 20]],
  ],
  'prometheusexporter_trust_proxy' => [
    'name' => 'prometheusexporter_trust_proxy',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => FALSE,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => ts('Trust X-Forwarded-For header'),
    'is_required' => FALSE,
    'description' => ts('Only enable this if requests genuinely pass through a reverse proxy/load balancer that you control and that sets X-Forwarded-For. When enabled, the IP allowlist check uses the left-most address in X-Forwarded-For instead of the direct connection IP. Enabling this on a site NOT behind such a proxy lets anyone bypass the IP allowlist by forging the header.'),
    'help_text' => ts('Enable only when CiviCRM is behind a trusted reverse proxy.'),
    'settings_pages' => ['prometheusexporter' => ['weight' => 30]],
  ],
];
