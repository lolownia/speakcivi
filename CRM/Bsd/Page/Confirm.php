<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_Confirm extends CRM_Core_Page {
  function run() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this, true);
    // todo issue #33 temporary solution
    //$campaign_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this, true);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $id);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }

    /* Section: Group */
    $group_id = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'group_id');
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $id,
      'group_id' => $group_id,
      'status' => "Pending"
    ));
    CRM_Core_Error::debug_var('CONFIRM $resultGroupContact-get', $result, false, true);

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $id,
        'group_id' => $group_id,
        'status' => "Added",
      );
    }
    $result = civicrm_api3('GroupContact', 'create', $params);
    CRM_Core_Error::debug_var('CONFIRM $resultGroupContact-create', $result, false, true);

    /* Section: Activity */
    if ($aid) {
      $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $params = array(
        'sequential' => 1,
        'id' => $aid,
        'status_id' => $scheduled_id,
      );
      $result = civicrm_api3('Activity', 'get', $params);
      CRM_Core_Error::debug_var('CONFIRM $resultActivityGet', $result, false, true);
      if ($result['count'] == 1) {
        $completed_id = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name', 'String', 'value');
        $params['status_id'] = $completed_id;
        $result = civicrm_api3('Activity', 'create', $params);
        CRM_Core_Error::debug_var('CONFIRM $resultActivity-create', $result, false, true);
      }
    }

    /* Section: Preferred language */
    $country = '';
    $params = array(
      'sequential' => 1,
      'id' => $id,
      'return' => 'preferred_language',
    );
    $result = civicrm_api3('Contact', 'get', $params);
    if ($result['count'] == 1) {
      $preferred_language = $result['values'][0]['preferred_language'];
      $tab = explode('_', $preferred_language);
      $country = '/'.$tab[0];
    }

    $url = "{$country}/post_confirm";
    if ($campaign_id > 0) {
      $url .= "?cid={$campaign_id}";
    }
    CRM_Utils_System::redirect($url);
  }
}
