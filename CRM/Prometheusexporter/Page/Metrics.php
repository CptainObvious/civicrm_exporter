<?php declare (strict_types=1);

/**
 * Page: civicrm/prometheusmetrics
 *
 * Renders CiviCRM system status checks as Prometheus text metrics.
 */
class CRM_Prometheusexporter_Page_Metrics extends CRM_Core_Page {

  private const SEVERITY_MAP = [
    'debug' => 0,
    'info' => 1,
    'notice' => 2,
    'warning' => 3,
    'error' => 4,
    'critical' => 5,
    'alert' => 6,
    'emergency' => 7,
  ];

  private const WARNING_THRESHOLD = 3;
  private const ERROR_THRESHOLD = 4;

  public function run(): void {
    $this->checkAccess();

    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
    echo $this->buildMetrics();
    CRM_Utils_System::civiExit();
  }

  private function checkAccess(): void {
    $this->checkIpAllowlist();

    $expected = (string) Civi::settings()->get('prometheusexporter_token');
    if ($expected === '') {
      $this->deny('Prometheus exporter is not configured.');
    }

    $provided = CRM_Utils_Request::retrieve('token', 'String', $this, FALSE);
    if (!$provided && !empty($_SERVER['HTTP_X_CIVICRM_TOKEN'])) {
      $provided = trim((string) $_SERVER['HTTP_X_CIVICRM_TOKEN']);
    }

    if (!is_string($provided) || $provided === '' || !hash_equals($expected, $provided)) {
      $this->deny('Forbidden');
    }
  }

  private function checkIpAllowlist(): void {
    $raw = (string) Civi::settings()->get('prometheusexporter_ip_allowlist');
    $entries = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (!$entries) {
      return;
    }

    $trustProxy = (bool) Civi::settings()->get('prometheusexporter_trust_proxy');
    $clientIp = $this->getClientIp($trustProxy);
    if ($clientIp === NULL) {
      $this->deny('Forbidden');
    }

    foreach ($entries as $entry) {
      if ($this->ipMatches($clientIp, $entry)) {
        return;
      }
    }

    $this->deny('Forbidden');
  }

  private function getClientIp(bool $trustProxy): ?string {
    if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $candidate = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
      if (filter_var($candidate, FILTER_VALIDATE_IP)) {
        return $candidate;
      }
    }

    return CRM_Utils_System::ipAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? NULL);
  }

  private function ipMatches(string $ip, string $entry): bool {
    if (!str_contains($entry, '/')) {
      return filter_var($ip, FILTER_VALIDATE_IP)
        && filter_var($entry, FILTER_VALIDATE_IP)
        && inet_pton($ip) === inet_pton($entry);
    }

    [$subnet, $maskBits] = explode('/', $entry, 2);
    $maskBits = (int) $maskBits;
    $ipBinary = @inet_pton($ip);
    $subnetBinary = @inet_pton($subnet);

    if ($ipBinary === FALSE || $subnetBinary === FALSE
      || strlen($ipBinary) !== strlen($subnetBinary)) {
      return FALSE;
    }

    $bytes = intdiv($maskBits, 8);
    $remainderBits = $maskBits % 8;

    if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($subnetBinary, 0, $bytes)) {
      return FALSE;
    }

    if ($remainderBits > 0) {
      $mask = chr((0xFF << (8 - $remainderBits)) & 0xFF);
      if ((substr($ipBinary, $bytes, 1) & $mask) !== (substr($subnetBinary, $bytes, 1) & $mask)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  private function deny(string $message): never {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message . "\n";
    CRM_Utils_System::civiExit();
  }

  private function buildMetrics(): string {
    $messages = $this->getStatusMessages();
    $lines = [];
    $maxSeverity = 0;
    $checkCount = 0;

    $lines[] = '# HELP civicrm_status_check_severity Severity of an individual CiviCRM system status check.';
    $lines[] = '# TYPE civicrm_status_check_severity gauge';
    foreach ($messages as $message) {
      $name = (string) ($message['name'] ?? 'unknown');
      $severityLabel = strtolower((string) ($message['severity'] ?? 'info'));
      $severityNumber = self::SEVERITY_MAP[$severityLabel] ?? 1;
      $maxSeverity = max($maxSeverity, $severityNumber);
      $checkCount++;

      $lines[] = sprintf(
        'civicrm_status_check_severity{name="%s",severity="%s"} %d',
        $this->escapeLabel($name),
        $this->escapeLabel($severityLabel),
        $severityNumber
      );
    }

    $globalStatus = match(TRUE) {
      $maxSeverity >= self::ERROR_THRESHOLD => 2,
      $maxSeverity >= self::WARNING_THRESHOLD => 1,
      default => 0,
    };

    $lines[] = '';
    $lines[] = '# HELP civicrm_status_global Overall CiviCRM status derived from all system checks.';
    $lines[] = '# TYPE civicrm_status_global gauge';
    $lines[] = sprintf('civicrm_status_global %d', $globalStatus);

    $lines[] = '';
    $lines[] = '# HELP civicrm_version_info Installed CiviCRM version.';
    $lines[] = '# TYPE civicrm_version_info gauge';
    $lines[] = sprintf(
      'civicrm_version_info{version="%s"} 1',
      $this->escapeLabel($this->getCiviCrmVersion())
    );

    $lines[] = '';
    $lines[] = '# HELP civicrm_status_check_count Number of active system status check messages.';
    $lines[] = '# TYPE civicrm_status_check_count gauge';
    $lines[] = sprintf('civicrm_status_check_count %d', $checkCount);

    $lines[] = '';
    $lines[] = '# HELP civicrm_cron_ok Whether the last cron run check passes.';
    $lines[] = '# TYPE civicrm_cron_ok gauge';
    $lastCronTimestamp = $this->getLastCronTimestamp();
    $cronCheckOk = $this->checkOk($messages, 'checkLastCron') && $lastCronTimestamp !== NULL;
    $lines[] = sprintf('civicrm_cron_ok %d', $cronCheckOk ? 1 : 0);

    $lines[] = '';
    $lines[] = '# HELP civicrm_cron_last_run_timestamp Unix timestamp of the most recent scheduled job run, or 0 if none has been recorded.';
    $lines[] = '# TYPE civicrm_cron_last_run_timestamp gauge';
    $lines[] = sprintf('civicrm_cron_last_run_timestamp %d', $lastCronTimestamp);

    $coreUpdateChecks = [
      'civicrm_core_update_available' => 'checkVersion_upgrade',
      'civicrm_core_patch_available' => 'checkVersion_patch',
    ];
    foreach ($coreUpdateChecks as $metric => $checkName) {
      $lines[] = '';
      $lines[] = sprintf('# HELP %s Whether the related CiviCRM core update check is flagged.', $metric);
      $lines[] = sprintf('# TYPE %s gauge', $metric);
      $lines[] = sprintf('%s %d', $metric, $this->checkFlagged($messages, $checkName) ? 1 : 0);
    }

    return implode("\n", $lines) . "\n";
  }

  private function getStatusMessages(): array {
    try {
      $result = \Civi\Api4\System::check(FALSE)->execute();
      return $result->getArrayCopy();
    }
    catch (CRM_Core_Exception) {
      return [];
    }
  }

  private function getCiviCrmVersion(): string {
    return (string) CRM_Utils_System::version();
  }

  private function checkOk(array $messages, string $name): bool {
    foreach ($messages as $message) {
      if (($message['name'] ?? NULL) !== $name) {
        continue;
      }
      return $this->getSeverity($message) < self::WARNING_THRESHOLD;
    }

    return TRUE;
  }

  private function checkFlagged(array $messages, string $name): bool {
    foreach ($messages as $message) {
      if (($message['name'] ?? NULL) === $name
        && $this->getSeverity($message) >= self::WARNING_THRESHOLD) {
        return TRUE;
      }
    }

    return FALSE;
  }

  private function getSeverity(array $message): int {
    $label = strtolower((string) ($message['severity'] ?? 'info'));
    return self::SEVERITY_MAP[$label] ?? 1;
  }

  private function getLastCronTimestamp(): ?int {
    try {
      $jobs = \Civi\Api4\Job::get(FALSE)
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('last_run', 'DESC')
        ->setLimit(1)
        ->addSelect('last_run')
        ->execute()
        ->getArrayCopy();
      $job = reset($jobs);
      $timestamp = $job['last_run'] ?? NULL;

      return $timestamp ? strtotime((string) $timestamp) ?: NULL : NULL;
    }
    catch (CRM_Core_Exception) {
      return NULL;
    }
  }

  private function escapeLabel(string $value): string {
    return str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
  }

}
