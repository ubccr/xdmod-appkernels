<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

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

        $akConf['appkernel_pass'] = $this->console->silentPrompt(
            'DB Password:'
        );

        $akConf['akrr-db_host'] = $akConf['appkernel_host'];
        $akConf['akrr-db_port'] = $akConf['appkernel_port'];
        $akConf['akrr-db_user'] = $akConf['appkernel_user'];
        $akConf['akrr-db_pass'] = $akConf['appkernel_pass'];

        // mail_log_recipient?

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
    }
}
