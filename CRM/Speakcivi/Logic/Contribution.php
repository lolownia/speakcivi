<?php

class CRM_Speakcivi_Logic_Contribution {

  private static $financialTypeId = 1;

  private static $mapStatus = array(
    'success' => 2,
    'destroy' => 3,
  );

  /**
   * Create a transaction entity
   *
   * @param array $param
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   */
  public static function set($param, $contactId, $campaignId) {
    if (self::isRecurring($param->metadata->recurring_id)) {
      $recurId = self::setRecurring($param, $contactId, $campaignId);
      return self::create($param, $contactId, $campaignId, $recurId);
    } else {
      return self::create($param, $contactId, $campaignId);
    }
  }


  /**
   * Create a transaction entity
   *
   * @param array $param
   * @param int $contactId
   * @param int $campaignId
   * @param int $recurId
   *
   * @return array
   */
  private static function create($param, $contactId, $campaignId, $recurId = 0) {
    $params = array(
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => self::$financialTypeId,
      'receive_date' => $param->create_dt,
      'total_amount' => $param->metadata->amount,
      'fee_amount' => $param->metadata->amount_charged,
      'net_amount' => ($param->metadata->amount - $param->metadata->amount_charged),
      'trxn_id' => $param->metadata->transaction_id,
      'contribution_status' => self::determineStatus($param->metadata->status),
      'currency' => $param->metadata->currency,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
    );
    if ($recurId) {
      $params['contribution_recur_id'] = $recurId;
    }
    return civicrm_api3('Contribution', 'create', $params);
  }


  private static function setRecurring($param, $contactId, $campaignId) {
    if (!$recur = self::findRecurring($param->metadata->recurring_id)) {
      $recur = self::createRecurring($param, $contactId, $campaignId);
    } else {
      if ($recur['values'][0]['contribution_status_id'] != self::determineStatus($param->metadata->status)) {
        self::setRecurringStatus($recur['id'], self::determineStatus($param->metadata->status));
      }
    }
    return $recur['id'];
  }


  /**
   * Find recurring contribution by unique transaction id.
   *
   * @param string $recurringId
   *
   * @return array
   */
  private static function findRecurring($recurringId) {
    $params = array(
      'sequential' => 1,
      'trxn_id' => $recurringId,
    );
    $result = civicrm_api3('ContributionRecur', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return array();
  }


  /**
   * Set UTM in custom fields
   *
   * @param $contributionId
   * @param $fields
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function setUtm($contributionId, $fields) {
    $params = array(
      'sequential' => 1,
      'id' => $contributionId,
    );
    $fields = (array)$fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contribution_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contribution_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contribution_campaign')] = $fields['campaign'];
    }
    if (array_key_exists('content', $fields) && $fields['content']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contribution_content')] = $fields['content'];
    }
    if (count($params) > 2) {
      civicrm_api3('Contribution', 'create', $params);
    }
  }


  /**
   * Check if contribution is recurring.
   *
   * @param string $recurringId
   *
   * @return bool
   */
  private static function isRecurring($recurringId) {
    return (bool)$recurringId;
  }


  /**
   * Determine contribution status based on status from param.
   *
   * @param string $status
   *
   * @return mixed
   */
  private static function determineStatus($status) {
    return self::$mapStatus[$status];
  }
}
