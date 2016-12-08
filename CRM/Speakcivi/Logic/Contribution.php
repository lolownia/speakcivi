<?php

class CRM_Speakcivi_Logic_Contribution {

  /**
   * Create a transaction entity
   *
   * @param array $param
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   */
  public static function create($param, $contactId, $campaignId) {
    // todo get from settings?
    $financialTypeId = 1;
    $params = array(
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => $financialTypeId,
      'receive_date' => $param->create_dt,
      'total_amount' => $param->metadata->amount / 100,
      'fee_amount' => $param->metadata->amount_charged / 100,
      'net_amount' => ($param->metadata->amount - $param->metadata->amount_charged) / 100,
      'trxn_id' => $param->metadata->transaction_id,
      'contribution_status' => 'Completed',
      'currency' => $param->metadata->currency,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
    );
    return civicrm_api3('Contribution', 'create', $params);
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
}
