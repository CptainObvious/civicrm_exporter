<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_Prometheusexporter_ExtensionUtil {
  const SHORT_NAME = 'prometheusexporter';
  const LONG_NAME = 'org.example.prometheusexporter';
  const CLASS_PREFIX = 'CRM_Prometheusexporter';

  /**
   * Translate a string using the extension's domain.
   *
   * If the extension doesn't have a specific translation
   * for the string, fallback to the default translations.
   *
   * @param string $text
   *   Canonical message text (generally en_US).
   * @param array $params
   * @return string
   *   Translated text.
   * @see ts
   */
  public static function ts($text, $params = []) {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = [self::LONG_NAME, NULL];
    }
    return ts($text, $params);
  }

  /**
   * Get the URL of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo'.
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function url($file = NULL) {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
  }

  /**
   * Get the path of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo'.
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function path($file = NULL) {
    // return CRM_Core_Resources::singleton()->getPath(self::LONG_NAME, $file);
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the name of a class within this extension.
   *
   * @param string $suffix
   *   Ex: 'Page_HelloWorld' or 'Entity_Contact_Api4'.
   * @return string
   *   Ex: 'CRM_Foo_Page_HelloWorld' or 'Civi\Api4\Contact'.
   */
  public static function findClass($suffix) {
    return self::CLASS_PREFIX . '_' . $suffix;
  }
}

use CRM_Prometheusexporter_ExtensionUtil as E;

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function _prometheusexporter_civix_civicrm_config(&$config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $template =& CRM_Core_Smarty::singleton();
  $extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'templates';

  // Compatibilité Smarty 5
  if (method_exists($template, 'addTemplateDir')) {
    // Smarty 5+ méthode
    $template->addTemplateDir($extDir);
  } else {
    // Smarty 3/4 méthode legacy
    if (is_array($template->getTemplateDir())) {
      array_unshift($template->getTemplateDir(), $extDir);
    }
    else {
		$template->addTemplateDir($extDir);
    }
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
}

/**
 * (Delegated) Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function _prometheusexporter_civix_civicrm_install() {
  _prometheusexporter_civix_civicrm_config();
  if ($upgrader = _prometheusexporter_civix_upgrader()) {
    $upgrader->onInstall();
  }
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function _prometheusexporter_civix_civicrm_enable() {
  _prometheusexporter_civix_civicrm_config();
  if ($upgrader = _prometheusexporter_civix_upgrader()) {
    if (is_callable([$upgrader, '²²'])) {
      $upgrader->onEnable();
    }
  }
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy.
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @param array $item - the item to insert (parent/child a²ttributes will be
 *    filled for you)
 */
function _prometheusexporter_civix_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = [
      'attributes' => array_merge([
        'label'      => CRM_Utils_Array::value('name', $item),
        'active'     => 1,
      ], $item),
    ];
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!isset($entry['child'])) {
          $entry['child'] = [];
        }
        $found = _prometheusexporter_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item);
      }
    }
    return $found;
  }
}

/**
 * (Delegated) Implements hook_civicrm_navigationMenu().
 */
function _prometheusexporter_civix_navigationMenu(&$menu) {
  if ($upgrader = _prometheusexporter_civix_upgrader()) {
    $upgrader->appendCoreNavigationMenuItems($menu);
  }
}

/**
 * Checks if this extension is being installed or upgraded.
 *
 * @return CRM_Extension_Upgrader_Base|NULL
 */
function _prometheusexporter_civix_upgrader() {
  if (!file_exists(__DIR__ . '/CRM/ContributionTermination/Upgrader.php')) {
    return NULL;
  }
  else {
    return CRM_Extension_System::singleton()->getMapper()->keyToClass('prometheusexporter');
  }
}

/**
 * Search directory tree for files which match a glob pattern.
 *
 * Note: Dot-directories (like "..", ".git", or ".svn") will be ignored.
 * Note: In Civi 4.3+, delegate to CRM_Utils_File::findFiles()
 *
 * @param string $dir base dir
 * @param string $pattern , glob pattern, eg "*.txt"
 * @return array(string)
 */
function _prometheusexporter_civix_find_files($dir, $pattern) {
  if (is_callable(['CRM_Utils_File', 'findFiles'])) {
    return CRM_Utils_File::findFiles($dir, $pattern);
  }

  $todos = [$dir];
  $result = [];
  while (!empty($todos)) {
    $subdir = array_shift($todos);
    foreach (_prometheusexporter_civix_glob("$subdir/$pattern") as $match) {
      if (!is_dir($match)) {
        $result[] = $match;
      }
    }
    if ($dh = opendir($subdir)) {
      while (FALSE !== ($entry = readdir($dh))) {
        $path = $subdir . DIRECTORY_SEPARATOR . $entry;
        if ($entry[0] == '.') {
        }
        elseif (is_dir($path)) {
          $todos[] = $path;
        }
      }
      closedir($dh);
    }
  }
  return $result;
}

/**
 * (Delegated) Implements hook_civicrm_managed().
 *
 * Find any *.mgd.php files, merge their content, and return.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function _prometheusexporter_civix_civicrm_managed(&$entities) {
  $mgdFiles = _prometheusexporter_civix_find_files(__DIR__, '*.mgd.php');
  sort($mgdFiles);
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = E::LONG_NAME;
      }
      if (empty($e['params']['version'])) {
        $e['params']['version'] = '3';
      }
      $entities[] = $e;
    }
  }
}

/**
 * Glob wrapper which is guaranteed to return an array.
 *
 * The documentation for glob() says, "On some systems it is impossible to
 * distinguish between empty match and an error." Anecdotally, the return
 * result for an empty match is sometimes array() and sometimes FALSE.
 * This wrapper provides consistency.
 *
 * @link http://php.net/glob
 * @param string $pattern
 * @return array, possibly empty
 */
function _prometheusexporter_civix_glob($pattern) {
  $result = glob($pattern);
  return is_array($result) ? $result : [];
}
