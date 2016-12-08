<?php

class CRM_Speakcivi_Logic_Contact {

  /**
   * Get email
   * 
   * @param $contactId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function getEmail($contactId) {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => "email",
      'id' => $contactId,
    ));
    return $result['values'][0]['email'];
  }


  /**
   * Get contact id (or ids) by using Email API
   *
   * @param $email
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getContactByEmail($email) {
    $ids = array();
    $params = array(
      'sequential' => 1,
      'is_primary' => 1,
      'email' => $email,
      'return' => "contact_id",
    );
    $result = civicrm_api3('Email', 'get', $params);
    if ($result['count'] > 0) {
      foreach ($result['values'] as $contact) {
        $ids[$contact['contact_id']] = $contact['contact_id'];
      }
    }
    return $ids;
  }


  /**
   * Set up own created date. Column created_date is kind of timestamp and therefore It can't be set up during creating new contact.
   *
   * @param $contactId
   * @param $createdDate
   *
   * @return bool
   *
   */
  public static function setContactCreatedDate($contactId, $createdDate) {
    $query = "UPDATE civicrm_contact SET created_date = %2 WHERE id = %1";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($createdDate, 'String'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }


  /**
   * Check If contact need send email confirmation
   *
   * @param $newContact
   * @param $contactId
   * @param $groupId
   * @param $isOptOut
   *
   * @return bool
   *
   */
  public static function isContactNeedConfirmation($newContact, $contactId, $groupId, $isOptOut) {
    if ($newContact || $isOptOut) {
      return true;
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contactId,
        'group_id' => $groupId,
      );
      $result = civicrm_api3('GroupContact', 'get', $params);
      if ($result['count'] == 0) {
        return true;
      }
    }
    return false;
  }


  /**
   * Set contact params
   *
   * @param int $contactId
   * @param array $contactParams
   */
  public static function set($contactId, $contactParams) {
    $params = array(
      'sequential' => 1,
      'id' => $contactId,
    );
    $params = array_merge($params, $contactParams);
    if (count($params) > 2) {
      civicrm_api3('Contact', 'create', $params);
    }
  }
}
