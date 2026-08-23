# Prometheus Exporter for CiviCRM

Exposes the same data as the CiviCRM system-status screen
(`civicrm/a/#/status`) as a Prometheus-scrapable metrics endpoint at:

```
https://your-site.example.org/civicrm/prometheus-metrics
```

It's built on CiviCRM's built-in status-check system (`System.check` API /
`CRM_Utils_Check`), so it picks up whatever checks your CiviCRM version ships
with, not just the three called out below.

## Metrics exposed

| Metric | Meaning |
|---|---|
| `civicrm_status_global` | Overall status: `0`=ok, `1`=warning, `2`=error. This is the worst severity across **all** status checks, not just the three below. |
| `civicrm_version_info` | Always `1`, with the installed CiviCRM release in the `version` label (for example: `civicrm_version_info{version=6.12.0} 1`). |
| `civicrm_status_check_severity{name,severity}` | Severity (0-7) of every individual status check, labeled by its internal check name (e.g. `checkLastCron`, `checkExtensions`, `checkVersion`, `checkOutboundMail`, ...). |
| `civicrm_status_check_count` | Total number of status-check messages currently active. |
| `civicrm_cron_ok` | `1`/`0` - whether the "last cron run" check passes. |
| `civicrm_cron_last_run_timestamp` | Unix timestamp of the most recent active scheduled-job run (best-effort proxy for "cron last ran"). |
| `civicrm_extension_update_available` | `1`/`0` - whether CiviCRM's extension-update check is flagging an available update. |
| `civicrm_core_update_available` | `1`/`0` - whether CiviCRM's core-version check is flagging an available update. |

Because `civicrm_status_check_severity` carries every check as a label, you
can build alerts/dashboards for anything the CiviCRM status page shows (mail
bounce processing, file permissions, DB schema issues, etc.) without further
extension changes.

## Install

1. Copy this directory into your CiviCRM extensions directory (rename the
   `key` in `info.xml`, e.g. to `com.yourorg.prometheusexporter`, first).
2. `cv ext:enable org.example.prometheusexporter` (or enable it from
   Administer > System Settings > Extensions).
3. Go to **Administer > System Settings > Prometheus Exporter**
   (`civicrm/admin/setting/prometheusexporter`) and set:
   - **Prometheus Exporter Token** - required. The endpoint returns 403 for
     everything until this is non-empty.
   - **Prometheus Exporter IP Allowlist** - optional. One IP or CIDR range
     per line/comma, e.g. `203.0.113.10` or `10.0.0.0/8`. Leave empty to
     allow any IP (still gated by the token).
   - **Trust X-Forwarded-For header** - only turn this on if you're
     definitely behind a reverse proxy/load balancer you control that sets
     that header; otherwise leave it off, since it's the only way the IP
     allowlist could otherwise be bypassed.

   If you'd rather set these from the command line (e.g. for scripted
   deploys) instead of the UI:

   ```bash
   cv ev "Civi::settings()->set('prometheusexporter_token', 'CHANGE-ME-TO-SOMETHING-RANDOM');"
   cv ev "Civi::settings()->set('prometheusexporter_ip_allowlist', \"203.0.113.10\n198.51.100.0/24\");"
   ```

## Prometheus scrape config

Using a query-string token:

```yaml
scrape_configs:
  - job_name: civicrm
    metrics_path: /civicrm/prometheus-metrics
    params:
      token: ['CHANGE-ME-TO-SOMETHING-RANDOM']
    static_configs:
      - targets: ['your-site.example.org']
    scheme: https
```

Or using a bearer token instead (avoids the secret showing up in the URL /
web server access logs):

```yaml
scrape_configs:
  - job_name: civicrm
    metrics_path: /civicrm/prometheus-metrics
    authorization:
      credentials: CHANGE-ME-TO-SOMETHING-RANDOM
    static_configs:
      - targets: ['your-site.example.org']
    scheme: https
```

## Security notes

- The endpoint is registered as a public route (no CMS login), because
  Prometheus can't do an interactive CMS login. Access is gated by two
  independent checks, both enforced server-side in `Page/Metrics.php`:
  1. **IP allowlist** (if you've configured one) - checked first.
  2. **Shared-secret token**, checked with a timing-safe comparison
     (`hash_equals`).
  **The endpoint returns 403 for everything until the token setting is
  non-empty**, even if the IP allowlist is left empty.
- Prefer the `Authorization: Bearer` header over the `?token=` query param
  where your scrape setup supports it, to keep the secret out of access
  logs.
- The IP allowlist uses the direct TCP connection's address
  (`REMOTE_ADDR`) unless you explicitly enable "Trust X-Forwarded-For
  header" - and you should only do that if a reverse proxy you control is
  guaranteed to set/overwrite that header, since it's otherwise trivial
  for a client to spoof.
- Consider also restricting the path at your web server/firewall level to
  your Prometheus server's IP as defense in depth, on top of the
  in-extension allowlist.
- Rotate the token any time from the settings form, or via `cv ev`, and
  update your `scrape_configs` to match.

## Example alert rules

```yaml
groups:
  - name: civicrm
    rules:
      - alert: CiviCRMStatusError
        expr: civicrm_status_global == 2
        for: 15m
        labels:
          severity: critical
        annotations:
          summary: "CiviCRM system status check is reporting an error"

      - alert: CiviCRMCronNotRunning
        expr: civicrm_cron_ok == 0
        for: 30m
        labels:
          severity: critical
        annotations:
          summary: "CiviCRM cron has not run successfully"

      - alert: CiviCRMExtensionUpdateAvailable
        expr: civicrm_extension_update_available == 1
        for: 24h
        labels:
          severity: info
        annotations:
          summary: "CiviCRM has extension updates available"
```
