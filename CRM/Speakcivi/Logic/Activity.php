<?php

class CRM_Speakcivi_Logic_Activity {


  /**
   * Get activity status id. If status isn't exist, create it.
   *
   * @param string $activityStatus Internal name of status
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getStatusId($activityStatus) {
    $params = array(
      'sequential' => 1,
      'option_group_id' => 'activity_status',
      'name' => $activityStatus,
    );
    $result = civicrm_api3('OptionValue', 'get', $params);
    if ($result['count'] == 0) {
      $params['is_active'] = 1;
      $result = civicrm_api3('OptionValue', 'create', $params);
    }
    return (int)$result['values'][0]['value'];
  }


  /**
   * Set unique activity. Method gets activity by given params and creates only if needed.
   * Need more performance but provide database consistency.
   *
   * @param array $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function setActivity($params) {
    $contactId = $params['source_contact_id'];
    $getParams = $params;
    unset($getParams['status_id']);
    unset($getParams['source_contact_id']);
    $getParams['api.ActivityContact.get'] = array(
      'activity_id' => '$value.id',
      'contact_id' => $contactId,
      'record_type_id' => 2, // means source, added by
    );
    $result = civicrm_api3('Activity', 'get', $getParams);
    if ($result['count'] == 0) {
      return civicrm_api3('Activity', 'create', $params);
    } elseif ($result['count'] >= 1) {
      $activity = self::findActivity($result, $contactId);
      if ($activity) {
        return $activity;
      } else {
        return civicrm_api3('Activity', 'create', $params);
      }
    }
  }


  /**
   * Activity for contacts means source_contact_id (civicrm_activity_contact.record_type_id = 2)
   *
   * @param array $getResult
   * @param int $contactId
   *
   * @return array
   */
  private static function findActivity($getResult, $contactId) {
    $tab = array();
    $recordTypeId = 2; // source, added by
    foreach ($getResult['values'] as $a => $activity) {
      if ($activity['api.ActivityContact.get']['count'] >= 1) {
        foreach ($activity['api.ActivityContact.get']['values'] as $c => $contact) {
          if ($contact['contact_id'] == $contactId && $contact['record_type_id'] == $recordTypeId) {
            $tab[] = $activity;
          }
        }
      }
    }
    if (is_array($tab) && count($tab) == 1) {
      return array(
        'count' => 1,
        'id' => $tab[0]['id'],
        'values' => array(0 => $tab[0]),
      );
    }
    return array();
  }


  /**
   * Set source fields in custom fields
   *
   * @param $activityId
   * @param $fields
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function setUtm($activityId, $fields) {
    $params = array(
      'sequential' => 1,
      'id' => $activityId,
    );
    $fields = (array)$fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_campaign')] = $fields['campaign'];
    }
    if (array_key_exists('content', $fields) && $fields['content']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_content')] = $fields['content'];
    }
    if (count($params) > 2) {
      civicrm_api3('Activity', 'create', $params);
    }
  }
}
