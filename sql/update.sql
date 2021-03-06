truncate speakeasy_petition_metrics;

insert into speakeasy_petition_metrics
(campaign_id, activity, is_opt_out, npeople)
SELECT 
        campaign_id as campaign_id,
            status AS activity,
            is_opt_out AS is_opt_out,
            COUNT(*) AS npeople
    FROM
	civicrm_contact c
    JOIN civicrm_group_contact g ON g.contact_id = c.id AND g.group_id = 42
        AND c.is_deleted = 0
    JOIN (SELECT 
        CONCAT('speakout petition ', CAST(camp.external_identifier AS CHAR (10))) COLLATE utf8_unicode_ci AS source_string,
            camp.external_identifier AS speakout_id,
            camp.name AS name,
            camp.title AS title,
            camp.id AS campaign_id
    FROM
        civicrm_campaign AS camp) AS kampagne ON kampagne.source_string = c.source
    GROUP BY campaign_id , status , is_opt_out;


/*
SELECT 
       substring(source, 19) as speakout_id,
            status AS activity,
            is_opt_out AS is_opt_out,
            COUNT(*) AS npeople
    FROM
        civicrm_contact c
    JOIN civicrm_group_contact g ON g.contact_id = c.id AND g.group_id = 42
        AND c.is_deleted = 0

    WHERE source like "speakout petition%" 
    GROUP by source, status, is_opt_out
*/

/* select by activity 
important: select distinct

country is not in there yet. 

Is really made sure it is about signatories, not signatures? 


*/


insert into speakeasy_petition_metrics
(campaign_id, activity, status, npeople)
SELECT 
    ca.civicrm_camp_id AS civicrm_camp_id,
    ca.stand AS activity,
    ca.status AS status,
    COUNT(*) AS npeople
FROM
    (SELECT DISTINCT
        civicrm_campaign.id AS civicrm_camp_id,
            civicrm_option_value.label AS stand,
            option_value_status.label AS status,
            c.id
    FROM
        civicrm_contact c
    JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = c.id
    JOIN civicrm_activity ON civicrm_activity.id = civicrm_activity_contact.activity_id
    JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_activity.campaign_id
    JOIN civicrm_option_group ON civicrm_option_group.name = 'activity_type'
    JOIN civicrm_option_value ON civicrm_option_value.option_group_id = civicrm_option_group.id
        AND civicrm_activity.activity_type_id = civicrm_option_value.value
    JOIN civicrm_option_group AS option_group_status ON option_group_status.name = 'activity_status'
    JOIN civicrm_option_value AS option_value_status ON option_value_status.option_group_id = option_group_status.id
        AND civicrm_activity.status_id = option_value_status.value
    WHERE
        civicrm_option_value.label IN ('Petition Signature' , 'share')) AS ca
GROUP BY civicrm_camp_id , stand , status;


/* now add speakout_id, speakout_name, language */

SET SQL_SAFE_UPDATES=0;

UPDATE speakeasy_petition_metrics
        JOIN
    civicrm_campaign AS camp ON camp.id = speakeasy_petition_metrics.campaign_id
        JOIN
    civicrm_value_speakout_integration_2 speakout_integration ON camp.id = speakout_integration.entity_id 
SET 
    speakout_id = camp.external_identifier,
    speakout_name = camp.name,
    speakout_title = camp.title,
    language = speakout_integration.language_4
WHERE
speakeasy_petition_metrics.campaign_id IS NOT NULL
;


