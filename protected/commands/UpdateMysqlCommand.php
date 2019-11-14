<?php
/**
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2018 MagnusSolution. All rights reserved.
 * ###################################
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 * Please submit bug reports, patches, etc to https://github.com/magnusbilling/mbilling/issues
 * =======================================
 * Magnusbilling.com <info@magnusbilling.com>
 *
 */
class UpdateMysqlCommand extends ConsoleCommand
{

    public function run($args)
    {

        $version  = $this->config['global']['version'];
        $language = $this->config['global']['base_language'];

        echo $version;

        if (preg_match('/^6/', $version)) {

            $sql = "
            CREATE TABLE IF NOT EXISTS `pkg_rate_provider` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_provider` int(11) NOT NULL,
            `id_prefix` int(11) NOT NULL,
            `buyrate` decimal(15,6) DEFAULT '0.000000',
            `buyrateinitblock` int(11) NOT NULL DEFAULT '1',
            `buyrateincrement` int(11) NOT NULL DEFAULT '1',
            `minimal_time_buy` int(2) NOT NULL DEFAULT '0',
            `dialprefix` bigint(20) DEFAULT NULL,
            `destination` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `fk_pkg_prefix_pkg_rate` (`id_prefix`),
            KEY `dialprefix` (`dialprefix`),
            CONSTRAINT `fk_pkg_provider_pkg_rate_provider` FOREIGN KEY (`id_provider`) REFERENCES `pkg_provider` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ";
            $this->executeDB($sql);

            $sql    = "SELECT * FROM pkg_provider";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            foreach ($result as $key => $provider) {
                $sql = "INSERT INTO `pkg_rate_provider` (`id_provider`, `id_prefix`, `buyrate`, `buyrateinitblock`, `buyrateincrement`, `minimal_time_buy`)    SELECT " . $provider['id'] . ", t.id, 0, 1, 1, 0  FROM `pkg_prefix` t ";
                $this->executeDB($sql);

            }

            $sql = "UPDATE pkg_rate LEFT JOIN  pkg_trunk ON pkg_rate.id_trunk = pkg_trunk.id  SET pkg_rate.starttime = pkg_trunk.id_provider;";
            $this->executeDB($sql);

            $sql = "UPDATE pkg_rate_provider  JOIN  pkg_rate ON pkg_rate.starttime = pkg_rate_provider.id_provider AND pkg_rate.id_prefix = pkg_rate_provider.id_prefix SET
            pkg_rate_provider.buyrate = pkg_rate.buyrate, pkg_rate_provider.buyrateinitblock = pkg_rate.buyrateinitblock,
            pkg_rate_provider.buyrateincrement = pkg_rate.buyrateincrement,
            pkg_rate_provider.minimal_time_buy = pkg_rate.minimal_time_buy";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Provider Rates'')', 'rateprovider', 'prefixs', 10,3)";
            $this->executeDB($sql);
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "UPDATE pkg_module SET priority = '1' WHERE module = 'provider';
            UPDATE pkg_module SET priority = '2' WHERE module = 'trunk';
            UPDATE pkg_module SET priority = '4' WHERE module = 'servers';
            ";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_rate`
              DROP `buyrate`,
              DROP `buyrateinitblock`,
              DROP `buyrateincrement`,
              DROP `minimal_time_buy`,
              DROP `startdate`,
              DROP `stopdate`,
              DROP `starttime`,
              DROP `endtime`,
              DROP `musiconhold`;";
            $this->executeDB($sql);

            $sql = "
            ALTER TABLE `pkg_rate` ADD `dialprefix` bigint(20) NULL DEFAULT NULL , ADD INDEX (`dialprefix`) ;
            ALTER TABLE `pkg_rate` ADD `destination` varchar(50) NULL DEFAULT NULL;
            ";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_rate`
            CHANGE `initblock` `initblock` INT(11) NOT NULL DEFAULT '1',
            CHANGE `billingblock` `billingblock` INT(11) NOT NULL DEFAULT '1'
            ;";
            $this->executeDB($sql);

            $sql = "

            CREATE TABLE IF NOT EXISTS `pkg_status_system` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
              `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `cpuMediaUso` float NOT NULL DEFAULT '0',
              `cpuPercent` float NOT NULL DEFAULT '0',
              `memTotal` int(11) DEFAULT NULL,
              `memUsed` float NOT NULL DEFAULT '0',
              `networkin` float NOT NULL DEFAULT '0',
              `networkout` float NOT NULL DEFAULT '0',
              `cpuModel` varchar(200) DEFAULT NULL,
              `uptime` varchar(200) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `date` (`date`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0";

            $this->executeDB($sql);

            $sql = "UPDATE pkg_module SET `icon_cls` = 'x-fa fa-arrow-right' WHERE id_module IS NULL;
            UPDATE pkg_module SET `icon_cls` = 'x-fa fa-desktop' WHERE id_module IS NOT NULL;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE pkg_rate DROP FOREIGN KEY fk_pkg_prefix_pkg_rate;";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_configuration VALUES
                (NULL, 'Enable Signup Form', 'enable_signup', '0', 'Enable Signup form', 'global', '1');

                ";
            $this->executeDB($sql);

            $sql = "
                UPDATE `pkg_group_module` SET `show_menu` = '1', action = 'ru' WHERE `pkg_group_module`.`id_group` = 1 AND `pkg_group_module`.`id_module` = 4;
                UPDATE `pkg_module` SET `text` = 't(''Menus'')' WHERE module = 'module';
                DELETE FROM `pkg_group_module` WHERE `id_module` = 2;
                DELETE FROM `pkg_module` WHERE `id` = 2;
                DELETE FROM `pkg_group_module` WHERE `id_module` = (SELECT id FROM `pkg_module` WHERE `module` = 'groupusergroup');
                DELETE FROM `pkg_module` WHERE `module` = 'groupusergroup';
                UPDATE `pkg_module` SET priority = 1 WHERE id = 1;
                UPDATE `pkg_module` SET priority = 2 WHERE id = 7;
                UPDATE `pkg_module` SET priority = 3 WHERE id = 5;
                UPDATE `pkg_module` SET priority = 4 WHERE id = 8;
                UPDATE `pkg_module` SET priority = 5 WHERE id = 9;
                UPDATE `pkg_module` SET priority = 6 WHERE id = 10;
                UPDATE `pkg_module` SET priority = 7 WHERE id = 12;
                UPDATE `pkg_module` SET priority = 8 WHERE id = 13;
                UPDATE `pkg_module` SET priority = 9 WHERE id = 14;
                UPDATE `pkg_module` SET priority = 10 WHERE `text` = 't(''Services'')' AND id_module IS NULL;

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 1 AND module = 'user';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 1 AND module = 'sip';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 1 AND module = 'callonline';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 1 AND module = 'callerid';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 1 AND module = 'sipuras';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 1 AND module = 'restrictedphonenumber';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 1 AND module = 'callback';
                UPDATE `pkg_module` SET priority = 8 WHERE id_module = 1 AND module = 'buycredit';
                UPDATE `pkg_module` SET priority = 9 WHERE id_module = 1 AND module = 'iax';
                UPDATE `pkg_module` SET priority = 10 WHERE id_module = 1 AND module = 'gauthenticator';
                UPDATE `pkg_module` SET priority = 11 WHERE id_module = 1 AND module = 'transfertomobile';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 7 AND module = 'refill';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 7 AND module = 'methodpay';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 7 AND module = 'voucher';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 7 AND module = 'refillprovider';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 5 AND module = 'did';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 5 AND module = 'diddestination';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 5 AND module = 'diduse';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 5 AND module = 'ivr';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 5 AND module = 'queue';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 5 AND module = 'queuemember';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 5 AND module = 'didbuy';
                UPDATE `pkg_module` SET priority = 8 WHERE id_module = 5 AND module = 'dashboardqueue';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 8 AND module = 'plan';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 8 AND module = 'rate';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 8 AND module = 'prefix';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 8 AND module = 'userrate';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 8 AND module = 'offer';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 8 AND module = 'offercdr';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 8 AND module = 'offeruse';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 9 AND module = 'call';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 9 AND module = 'callfailed';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 9 AND module = 'callsummaryperday';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 9 AND module = 'callsummarydayuser';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 9 AND module = 'callsummarydaytrunk';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 9 AND module = 'callsummarydayagent';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 9 AND module = 'callsummarypermonth';
                UPDATE `pkg_module` SET priority = 8 WHERE id_module = 9 AND module = 'callsummarymonthuser';
                UPDATE `pkg_module` SET priority = 9 WHERE id_module = 9 AND module = 'callsummarymonthtrunk';
                UPDATE `pkg_module` SET priority = 10 WHERE id_module = 9 AND module = 'callsummaryperuser';
                UPDATE `pkg_module` SET priority = 11 WHERE id_module = 9 AND module = 'callsummarypertrunk';
                UPDATE `pkg_module` SET priority = 12 WHERE id_module = 9 AND module = 'callarchive';
                UPDATE `pkg_module` SET priority = 13 WHERE id_module = 9 AND module = 'sendcreditsummary';


                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 10 AND module = 'rateprovider';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 10 AND module = 'provider';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 10 AND module = 'trunk';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 10 AND module = 'servers';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 12 AND module = 'module';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 12 AND module = 'groupuser';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 12 AND module = 'configuration';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 12 AND module = 'templatemail';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 12 AND module = 'logusers';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 12 AND module = 'smtps';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 12 AND module = 'firewall';
                UPDATE `pkg_module` SET priority = 8 WHERE id_module = 12 AND module = 'api';
                UPDATE `pkg_module` SET priority = 9 WHERE id_module = 12 AND module = 'dashboard';
                UPDATE `pkg_module` SET priority = 10 WHERE id_module = 12 AND module = 'campaignlog';


                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 13 AND module = 'campaign';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 13 AND module = 'phonebook';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 13 AND module = 'phonenumber';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 13 AND module = 'campaignpoll';
                UPDATE `pkg_module` SET priority = 5 WHERE id_module = 13 AND module = 'campaignpollinfo';
                UPDATE `pkg_module` SET priority = 6 WHERE id_module = 13 AND module = 'campaignrestrictphone';
                UPDATE `pkg_module` SET priority = 7 WHERE id_module = 13 AND module = 'sms';
                UPDATE `pkg_module` SET priority = 8 WHERE id_module = 13 AND module = 'campaignsend';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 14 AND module = 'callshop';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 14 AND module = 'callshopcdr';
                UPDATE `pkg_module` SET priority = 3 WHERE id_module = 14 AND module = 'ratecallshop';
                UPDATE `pkg_module` SET priority = 4 WHERE id_module = 14 AND module = 'callsummarycallshop';

                UPDATE `pkg_module` SET priority = 1 WHERE id_module = 85 AND module = 'services';
                UPDATE `pkg_module` SET priority = 2 WHERE id_module = 85 AND module = 'servicesuse';
            ";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_configuration VALUES (NULL, 'Background Color', 'backgroundColor', '#1b1e23', 'Background Color', 'global', '1')";
            $this->executeDB($sql);

            exec("echo '\n* * * * * php /var/www/html/mbilling/cron.php statussystem' >> /var/spool/cron/root");
            exec("touch /etc/asterisk/queues_magnus.conf");
            exec("echo '#include queues_magnus.conf' >> /etc/asterisk/queues.conf");

            exec("echo '

[trunk_answer_handler]
exten => s,1,Set(MASTER_CHANNEL(TRUNKANSWERTIME)=${EPOCH})
    same => n,Return()' >> /etc/asterisk/extensions_magnus.conf");

            $version = '7.0.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "'WHERE config_key = 'version'";
            $this->executeDB($sql);
        }

        if ($version == '7.0.0') {

            $sql = "
            ALTER TABLE `pkg_ivr` CHANGE `monFriStart` `monFriStart` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '09:00-12:00|14:00-18:00';
            ALTER TABLE `pkg_ivr` CHANGE `satStart` `satStart` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '09:00-12:00';
            ALTER TABLE `pkg_ivr` CHANGE `sunStart` `sunStart` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '00:00';
            UPDATE pkg_ivr SET monFriStart = CONCAT(monFriStart,'-',monFriStop);
            UPDATE pkg_ivr SET satStart = CONCAT(satStart,'-',satStop);
            UPDATE pkg_ivr SET sunStart = CONCAT(sunStart,'-',sunStop);
            ALTER TABLE `pkg_ivr` DROP `monFriStop`;
            ALTER TABLE `pkg_ivr` DROP `satStop`;
            ALTER TABLE `pkg_ivr` DROP `sunStop`;
            ";
            $this->executeDB($sql);

            $version = '7.0.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "'WHERE config_key = 'version'";
            $this->executeDB($sql);
        }

        //2019-11-14
        if ($version == '7.0.1') {
            $sql = "ALTER TABLE `pkg_campaign` ADD `auto_reprocess` INT(11) NULL DEFAULT 0 ;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE  `pkg_cdr_summary_day_agent` ADD  `agent_bill` FLOAT NOT NULL DEFAULT  '0';
                    ALTER TABLE  `pkg_cdr_summary_day_agent` ADD  `agent_lucro` FLOAT NOT NULL DEFAULT  '0';";
            $this->executeDB($sql);

            $version = '7.0.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

    }

    public function executeDB($sql)
    {
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {

        }
    }

}
