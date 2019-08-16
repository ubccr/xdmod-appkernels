<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use CCR\DB\MySQLHelper;
use Exception;

/**
 * App kernels setup.
 */
class AppKernelsSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $settings = $this->loadIniConfig('portal_settings');

        $akConf = $this->loadIniConfig('portal_settings', 'appkernels');

        $this->console->displaySectionHeader('App Kernels Setup');

        $this->console->displayMessage(<<<"EOT"
Please provide the information required to connect to your MySQL
database server.  This information will be used to connect to the
databases created during the AKRR setup.

NOTE: The database password cannot include single quote characters (')
or double quote characters (").
EOT
        );
        $this->console->displayBlankLine();


        $akConf['appkernel_host'] = $this->console->prompt(
            'DB Hostname or IP:',
            $akConf['appkernel_host']
        );

        $akConf['appkernel_port'] = $this->console->prompt(
            'DB Port:',
            $akConf['appkernel_port']
        );

        $akConf['appkernel_user'] = $this->console->prompt(
            'DB Username:',
            $akConf['appkernel_user']
        );

        // reuse XDMoD user if DB host matches or ask to reuse password
        $same_db_host_same_user = false;
        if ($akConf['appkernel_user'] === $settings['datawarehouse_user']) {
            if ($akConf['appkernel_host'] === $settings['datawarehouse_host'] &&
                $akConf['appkernel_port'] === $settings['datawarehouse_port']) {
                // Same DB host, same user thus same password
                $this->console->displayMessage("Same DB host and user as for core XDMoD");
                $akConf['appkernel_pass'] = $settings['datawarehouse_pass'];
                $same_db_host_same_user = true;
            }
            else {
                // Different DB host same user thus may be different password
                $answer = $this->console->promptBool(
                    'This username matches user for core XDMoD DB, reuse password?'
                );

                if ($answer === 'yes') {
                    $akConf['appkernel_pass'] = $settings['datawarehouse_pass'];
                } else {
                    $akConf['appkernel_pass'] = $this->console->silentPrompt('DB Password:');
                }
            }
        } else {
            // different user, ask for password
            $akConf['appkernel_pass'] = $this->console->silentPrompt('DB Password:');
        }

        $akConf['akrr-db_host'] = $akConf['appkernel_host'];
        $akConf['akrr-db_port'] = $akConf['appkernel_port'];
        $akConf['akrr-db_user'] = $akConf['appkernel_user'];
        $akConf['akrr-db_pass'] = $akConf['appkernel_pass'];

        while (true) {
            $can_access_app_kernel_db = $this->checkDataBaseAccess(
                $akConf['appkernel_host'],
                $akConf['appkernel_port'],
                $akConf['appkernel_user'],
                $akConf['appkernel_pass'],
                $akConf['appkernel_database'],
                "app_kernel_def"
            );
            $can_access_akrr_db = $this->checkDataBaseAccess(
                $akConf['akrr-db_host'],
                $akConf['akrr-db_port'],
                $akConf['akrr-db_user'],
                $akConf['akrr-db_pass'],
                $akConf['akrr-db_database'],
                "SCHEDULEDTASKS"
            );
            print_r($can_access_app_kernel_db);
            print_r($can_access_akrr_db);

            if ((!$can_access_app_kernel_db) || (!$can_access_akrr_db)) {
                $help_create_user = "To create user run as MySQL administrator:\n" .
                    "    CREATE USER '{$akConf["appkernel_user"]}'@'localhost' IDENTIFIED BY 'PASSWORD';\n";
                $help_grant_priv = "To grant user privileges run as MySQL administrator:\n" .
                    "    GRANT ALL ON mod_akrr.* TO '{$akConf["appkernel_user"]}'@'localhost';\n" .
                    "    GRANT ALL ON mod_appkernel.* TO '{$akConf["appkernel_user"]}'@'localhost';\n";

                $this->console->displayMessage("Can not access mod_appkernels mod_akrr databases.\n");
                if ($same_db_host_same_user === true){
                    $this->console->displayMessage("You might need to grant user privileges.\n");
                    $this->console->displayMessage($help_grant_priv);
                } else {
                    $this->console->displayMessage("You might need to create user and grant it privileges.\n");
                    $this->console->displayMessage($help_create_user);
                    $this->console->displayMessage($help_grant_priv);
                }
                $answer = $this->console->prompt(
                    'Try again?',
                    'yes',
                    array('yes', 'skip', 'abort')
                );

                if ($answer === 'abort') {
                    exit(1);
                } else if ($answer === 'skip') {
                    break;
                }

            } else {
                break;
            }
        }

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
Please provide the information necessary to connect to the AKRR REST
API.  This should match the configuration in your AKRR installation.
EOT
        );
        $this->console->displayBlankLine();

        $akConf['akrr_username'] = $this->console->prompt(
            'AKRR REST API username:',
            $akConf['akrr_username']
        );

        $akConf['akrr_password'] = $this->console->silentPrompt(
            'AKRR REST API password:'
        );

        $akConf['akrr_host'] = $this->console->prompt(
            'AKRR REST API host:',
            $akConf['akrr_host']
        );

        $akConf['akrr_port'] = $this->console->prompt(
            'AKRR REST API port:',
            $akConf['akrr_port']
        );

        $akConf['akrr_end_point'] = $this->console->prompt(
            'AKRR REST API end point:',
            $akConf['akrr_end_point']
        );

        $this->saveIniConfig($akConf, 'portal_settings', 'appkernels');

        // Add tab on portal
        $aclConfig = new AclConfig($this->console);
        $aclConfig->handle();

    }
    /**
     * Check is can access DB by running select
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $db_name Database name.
     * @param string $table_name Database name.
     *
     * @return bool can run select
     */
    protected function checkDataBaseAccess(
        $host,
        $port,
        $username,
        $password,
        $db_name,
        $table_name
    ) {
        try {
            MySQLHelper::staticExecuteStatement(
                $host,
                $port,
                $username,
                $password,
                $db_name,
                "SELECT * FROM " . $table_name . " LIMIT 10"
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
