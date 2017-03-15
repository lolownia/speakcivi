<?php

class CRM_Speakcivi_Logic_Contribution {

  private static $financialTypeId = 1;

  private static $paymentMethod = 6;

  private static $frequencyInterval = 1;

  private static $frequencyUnit = 'month';

  private static $mapStatus = array(
    'success' => 1, // completed
    'destroy' => 3, // cancelled
  );

  private static $mapRecurringStatus = array(
    'success' => 2, // pending
    'destroy' => 3, // cancelled
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
      return self::setOne($param, $contactId, $campaignId, $recurId);
    } else {
      return self::setOne($param, $contactId, $campaignId);
    }
  }


  /**
   * Set contribution.
   *
   * @param object $param
   * @param int $contactId
   * @param int $campaignId
   * @param int $recurId
   *
   * @return array
   */
  private static function setOne($param, $contactId, $campaignId, $recurId = 0) {
    if (!$contrib = self::find($param->metadata->transaction_id)) {
      return self::create($param, $contactId, $campaignId, $recurId);
    }
    return $contrib;
  }


  /**
   * Find contribution by unique transaction id.
   *
   * @param string $transactionId
   *
   * @return array
   */
  private static function find($transactionId) {
    $params = array(
      'sequential' => 1,
      'trxn_id' => $transactionId,
    );
    $result = civicrm_api3('Contribution', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return array();
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
      'payment_instrument_id' => self::$paymentMethod,
      'receive_date' => $param->create_dt,
      'total_amount' => $param->metadata->amount / 100,
      'fee_amount' => $param->metadata->amount_charged / 100,
      'net_amount' => ($param->metadata->amount / 100 - $param->metadata->amount_charged / 100),
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


  /**
   * Set recurring contribution.
   *
   * @param object $param
   * @param int $contactId
   * @param int $campaignId
   *
   * @return mixed
   */
  private static function setRecurring($param, $contactId, $campaignId) {
    if (!$recur = self::findRecurring($param->metadata->recurring_id)) {
      $recur = self::createRecurring($param, $contactId, $campaignId);
    } else {
      if ($recur['values'][0]['contribution_status_id'] != self::determineRecurringStatus($param->metadata->status)) {
        self::setRecurringStatus($param, $recur['id']);
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
   * Create new recurring contribution.
   *
   * @param object $param
   * @param int $contactId
   * @param int $campaignId
   *
   * @return mixed
   */
  private static function createRecurring($param, $contactId, $campaignId) {
    $params = array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'amount' => $param->metadata->amount / 100,
      'currency' => $param->metadata->currency,
      'frequency_unit' => self::$frequencyUnit,
      'frequency_interval' => self::$frequencyInterval,
      'start_date' => $param->create_dt,
      'create_date' => $param->create_dt,
      'trxn_id' => $param->metadata->recurring_id,
      'contribution_status_id' => self::determineRecurringStatus($param->metadata->status),
      'financial_type_id' => self::$financialTypeId,
      'payment_instrument_id' => self::$paymentMethod,
      'campaign_id' => $campaignId,
    );
    if ($param->metadata->status == 'destroy') {
      $params['cancel_date'] = $param->create_dt;
    }
    return civicrm_api3('ContributionRecur', 'create', $params);
  }


  /**
   * Set recurring status and cancel date if needed.
   *
   * @param object $param
   * @param int $recurId
   *
   * @return mixed
   */
  private static function setRecurringStatus($param, $recurId) {
    $params = array(
      'sequential' => 1,
      'id' => $recurId,
      'contribution_status_id' => self::determineRecurringStatus($param->metadata->status),
    );
    if ($param->metadata->status == 'destroy') {
      $params['cancel_date'] = $param->create_dt;
    }
    return civicrm_api3('ContributionRecur', 'create', $params);
  }


  /**
   * Set UTM in custom fields
   *
   * @param int $contributionId
   * @param object $fields
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


  /**
   * Determine contribution status for recurring based on status from param.
   *
   * @param string $status
   *
   * @return mixed
   */
  private static function determineRecurringStatus($status) {
    return self::$mapRecurringStatus[$status];
  }
}
