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
  'action_type' => 'donate',
  'action_technical_type' => 'dzialaj.akcjademokracja.pl:donate',
  'create_dt' => '2016-12-08T11:56:59.617+01:00',
  'action_name' => 'Testowa kampania',
  'external_id' => 130,
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius3@chords.pl',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        'zip' => '02-222',
        'country' => 'pl',
      ),
    ),
  ),
  'metadata' => (object)array(
    'amount' => 2000,
    'amount_charged' => 50,
    'currency' => 'PLN',
    'card_type' => 'visa',
    'payment_processor' => 'stripe',
    'transaction_id' => 'tx1234567',
    'description' => 'Some client description if available',
    'status' => 'success',
  ),
  'utm' => (object)array(
    'source' => 'donate-ssss1',
    'medium' => 'donate-mmmm2',
    'campaign' => 'donate-ccc3',
    'content' => 'donate-coco4',
  ),
);

$speakcivi = new CRM_Speakcivi_Page_Speakcivi();
$speakcivi->runParam($param);
print_r($speakcivi);
print_r($param);
exit;
