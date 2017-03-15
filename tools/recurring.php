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
  'create_dt' => '2017-03-15T11:56:59.617+01:00',
  'action_name' => 'Testowa kampania',
  'external_id' => 130,
  'cons_hash' => (object)array(
    'firstname' => 'Test',
    'lastname' => 'Testowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'test.testowski@email.pl',
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
  'metadata' => (object)array(
    "amount" =>  2000,
    "amount_charged" => 50,
    "currency" => "PLN",
    "card_type" => "visa",
    "payment_processor" => "stripe",
    "transaction_id" => "tx1234567",
    "recurring_id" => "d41d8cd98f00b204e9800998ecf8427e",
    "description" => "Some client description if available",
    "status" => "success"
  ),
);

$speakcivi = new CRM_Speakcivi_Page_Speakcivi();
$speakcivi->runParam($param);
print_r($speakcivi);
print_r($param);
exit;
