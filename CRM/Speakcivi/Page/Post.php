<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Post extends CRM_Core_Page {

  public $contact_id = 0;

  public $activity_id = 0;

  public $campaign_id = 0;


  /**
   * Set values from request.
   *
   * @throws Exception
   */
  public function setValues() {
    $this->contact_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $this->activity_id = CRM_Utils_Request::retrieve('aid', 'Positive', $this, false);
    $this->campaign_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $this->contact_id);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
  }


  /**
   * Get country prefix based on campaign id.
   *
   * @param int $campaign_id
   *
   * @return string
   */
  public function getCountry($campaign_id) {
    $country = '';
    if ($campaign_id > 0) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($campaign_id);
      $language = $campaign->getLanguage();
      if ($language != '') {
        $tab = explode('_', $language);
        if (strlen($tab[0]) == 2) {
          $country = '/'.$tab[0];
        }
      }
    }
    return $country;
  }


  /**
   * Set new activity status for Scheduled activity.
   *
   * @param int $activity_id
   * @param string $status
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setActivityStatus($activity_id, $status = 'optout') {
    if ($activity_id > 0) {
      $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $params = array(
        'sequential' => 1,
        'id' => $activity_id,
        'status_id' => $scheduled_id,
      );
      $result = civicrm_api3('Activity', 'get', $params);
      if ($result['count'] == 1) {
        $new_status_id = CRM_Core_OptionGroup::getValue('activity_status', $status, 'name', 'String', 'value');
        $params['status_id'] = $new_status_id;
        $result = civicrm_api3('Activity', 'create', $params);
      }
    }
  }


  /**
   * Set acitivity status for each activities.
   * @param integer $activity_id
   * @param array $aids array of activities ids
   * @param string $status
   */
  public function setActivitiesStatuses($activity_id, $aids, $status = 'Completed') {
    if (is_array($aids) && count($aids) > 0) {
      foreach ($aids as $aid) {
        $this->setActivityStatus($aid, $status);
      }
    } else {
      $this->setActivityStatus($activity_id, $status);
    }
  }


  /**
   * Set Added status for group. If group is not assigned to contact, It is added.
   *
   * @param int $contact_id
   * @param int $group_id
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setGroupStatus($contact_id, $group_id) {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contact_id,
      'group_id' => $group_id,
      'status' => "Pending"
    ));

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contact_id,
        'group_id' => $group_id,
        'status' => "Added",
      );
    }
    $result = civicrm_api3('GroupContact', 'create', $params);
  }


  /**
   * Set language group for contact based on language of campaign
   * @param int $contact_id
   * @param string $language Language in format en, fr, de, pl etc.
   */
  public function setLanguageGroup($contact_id, $language) {
    if ($language) {
      $languageGroupNameSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');
      $defaultLanguageGroupId = (int)CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_language_group_id');
      if (!$this->checkLanguageGroup($contact_id, $defaultLanguageGroupId, $languageGroupNameSuffix)) {
        $languageGroupId = $this->findLanguageGroupId($language, $languageGroupNameSuffix);
        if ($languageGroupId) {
          $this->setGroupStatus($contact_id, $languageGroupId);
          $this->deleteLanguageGroup($contact_id, $defaultLanguageGroupId);
        } else {
          $this->setGroupStatus($contact_id, $defaultLanguageGroupId);
        }
      }
    }
  }


  /**
   * Get language group id based on language shortcut
   * @param string $language Example: en, es, fr...
   * @param string $languageGroupNameSuffix
   *
   * @return int
   */
  public function findLanguageGroupId($language, $languageGroupNameSuffix) {
    $result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'name' => $language.$languageGroupNameSuffix,
      'return' => 'id',
    ));
    if ($result['count'] == 1) {
      return $result['id'];
    }
    return 0;
  }


  /**
   * Check if contact has already at least one language group. Default group is skipping.
   * @param int $contact_id
   * @param int $defaultLanguageGroupId
   * @param string $languageGroupNameSuffix
   *
   * @return bool
   */
  public function checkLanguageGroup($contact_id, $defaultLanguageGroupId, $languageGroupNameSuffix) {
    $query = "SELECT count(gc.id) group_count
              FROM civicrm_group_contact gc JOIN civicrm_group g ON gc.group_id = g.id
              WHERE gc.contact_id = %1 AND g.id <> %2 AND g.name LIKE %3";
    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($defaultLanguageGroupId, 'Integer'),
      3 => array('%'.$languageGroupNameSuffix, 'String'),
    );
    $results = CRM_Core_DAO::executeQuery($query, $params);
    $results->fetch();
    return (bool)$results->group_count;
  }


  /**
   * Delete language group from contact
   * @param $contact_id
   * @param $group_id
   */
  public function deleteLanguageGroup($contact_id, $group_id) {
    $query = "DELETE FROM civicrm_group_contact
              WHERE contact_id = %1 AND group_id = %2";
    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($group_id, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }


  /**
   * Find activities if activity id is not set up in confirmation link
   *
   * @param $activity_id
   * @param $campaign_id
   * @param $contact_id
   *
   * @return array
   */
  public function findActivitiesIds($activity_id, $campaign_id, $contact_id) {
    $aids = array();
    if (!$activity_id && $campaign_id) {
      $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Petition', 'name', 'String', 'value');
      $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $query = "SELECT a.id
                FROM civicrm_activity a JOIN civicrm_activity_contact ac ON a.id = ac.activity_id
                WHERE ac.contact_id = %1 AND activity_type_id = %2 AND a.status_id = %3 AND a.campaign_id = %4";
      $params = array(
        1 => array($contact_id, 'Integer'),
        2 => array($activityTypeId, 'Integer'),
        3 => array($activityStatusId, 'Integer'),
        4 => array($campaign_id, 'Integer'),
      );
      $results = CRM_Core_DAO::executeQuery($query, $params);
      while ($results->fetch()) {
        $aids[$results->id] = $results->id;
      }
    }
    return $aids;
  }


  /**
   * Set language tag for contact based on language of campaign
   * @param int $contact_id
   * @param string $language Language in format en, fr, de, pl etc.
   */
  public function setLanguageTag($contact_id, $language) {
    if ($language) {
      $languageTagNamePrefix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_tag_name_prefix');
      $tagName = $languageTagNamePrefix.$language;
      if (!($tagId = $this->getLanguageTagId($tagName))) {
        $tagId = $this->createLanguageTag($tagName);
      }
      if ($tagId) {
        $this->addLanguageTag($contact_id, $tagId);
      }
    }
  }


  /**
   * Get language tag id
   * @param $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getLanguageTagId($tagName) {
    $params = array(
      'sequential' => 1,
      'name' => $tagName,
    );
    $result = civicrm_api3('Tag', 'get', $params);
    if ($result['count'] == 1) {
      return (int)$result['id'];
    }
    return 0;
  }


  /**
   * Create new language tag
   * @param $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function createLanguageTag($tagName) {
    $params = array(
      'sequential' => 1,
      'used_for' => 'civicrm_contact',
      'name' => $tagName,
      'description' => $tagName,
    );
    $result = civicrm_api3('Tag', 'create', $params);
    if ($result['count'] == 1) {
      return (int)$result['id'];
    }
    return 0;
  }


  /**
   * Add tag to contact
   * @param $contact_id
   * @param $tag_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function addLanguageTag($contact_id, $tag_id) {
    $params = array(
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contact_id,
      'tag_id' => $tag_id,
    );
    civicrm_api3('EntityTag', 'create', $params);
  }


  /**
   * Set parameter NO BULK EMAILS (User Opt Out)
   * @param int $contactId
   * @param int $isOptOut
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setIsOptOut($contactId, $isOptOut) {
    $params = array(
      'sequential' => 1,
      'id' => $contactId,
      'is_opt_out' => $isOptOut,
    );
    civicrm_api3('Contact', 'create', $params);
  }
}
