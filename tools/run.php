<?php

session_start();
$settingsFile = trim(implode('', file('path.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
$error = @include_once( $settingsFile );
if ( $error == false ) {
  echo "Could not load the settings file at: {$settingsFile}\n";
  exit( );
}

// Load class loader
global $civicrm_root;
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

// message from Speakout
$param = (object)array(
  'action_type' => 'petition',
  'action_technical_type' => 'dzialaj.akcjademokracja.pl:petition',
  'create_dt' => '2016-01-08T11:56:59.617+01:00',
  'action_name' => 'Testowa kampania',
  'external_id' => 98,
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius@chords.pl',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        'zip' => '02-222',
        'country' => 'pl',
      ),
    ),
  ),
  'utm' => (object)array(
    'source' => 'ssss1',
    'medium' => 'mmmm2',
    'campaign' => 'ccc3',
    'content' => 'coco4',
  ),
);

$speakcivi = new CRM_Speakcivi_Page_Speakcivi();
$speakcivi->runParam($param);
print_r($speakcivi);
print_r($param);
exit;
