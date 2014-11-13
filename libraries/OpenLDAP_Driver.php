<?php

/**
 * Samba OpenLDAP driver class.
 *
 * @category   apps
 * @package    samba
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\samba;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('samba');
clearos_load_language('samba_common');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\groups\Group_Factory as Group;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager;
use \clearos\apps\users\User_Factory as User;
use \clearos\apps\users\User_Manager_Factory as User_Manager;

clearos_load_library('groups/Group_Factory');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('users/User_Factory');
clearos_load_library('users/User_Manager_Factory');

// Classes
//--------

use \clearos\apps\accounts\Accounts_Configuration as Accounts_Configuration;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\groups\Group_Engine as Group_Engine;
use \clearos\apps\ldap\LDAP_Utilities as LDAP_Utilities;
use \clearos\apps\ldap\Nslcd as Nslcd;
use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \clearos\apps\mode\Mode_Factory as Mode_Factory;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\Accounts_Driver as Accounts_Driver;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\Group_Manager_Driver as Group_Manager_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\User_Manager_Driver as User_Manager_Driver;
use \clearos\apps\samba\OpenLDAP_Driver_Not_Initialized as OpenLDAP_Driver_Not_Initialized;
use \clearos\apps\samba_common\Nmbd as Nmbd;
use \clearos\apps\samba_common\Samba as Samba;
use \clearos\apps\samba_common\Smbd as Smbd;
use \clearos\apps\samba_common\Winbind as Winbind;
use \clearos\apps\users\User_Engine as User_Engine;
use \clearos\apps\users\User_Factory as User_Factory;

clearos_load_library('accounts/Accounts_Configuration');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('groups/Group_Engine');
clearos_load_library('ldap/LDAP_Utilities');
clearos_load_library('ldap/Nslcd');
clearos_load_library('mode/Mode_Engine');
clearos_load_library('mode/Mode_Factory');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/Accounts_Driver');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/Group_Manager_Driver');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/User_Manager_Driver');
clearos_load_library('samba/OpenLDAP_Driver_Not_Initialized');
clearos_load_library('samba_common/Nmbd');
clearos_load_library('samba_common/Samba');
clearos_load_library('samba_common/Smbd');
clearos_load_library('samba_common/Winbind');
clearos_load_library('users/User_Engine');
clearos_load_library('users/User_Factory');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\samba_common\Samba_Not_Initialized_Exception as Samba_Not_Initialized_Exception;

clearos_load_library('accounts/Accounts_Driver_Not_Set_Exception');
clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('samba_common/Samba_Not_Initialized_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba OpenLDAP driver class.
 *
 * @category   apps
 * @package    samba
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class OpenLDAP_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_INITIALIZED = '/var/clearos/samba/initialized_openldap';
    const FILE_INITIALIZING = '/var/clearos/samba/lock/initializing';

    const COMMAND_NET = '/usr/bin/net';
    const COMMAND_SMBPASSWD = '/usr/bin/smbpasswd';
    const COMMAND_SAMBA_OPENLDAP_INITIALIZE = '/usr/sbin/app-samba-openldap-initialize';
    const COMMAND_SAMBA_INITIALIZE = '/usr/sbin/app-samba-initialize';

    const CACHE_FLAG_TIME = 60; // in seconds

    const STATUS_SAMBA_INITIALIZED = 'samba_initialized';
    const STATUS_SAMBA_INITIALIZING = 'samba_initializing';
    const STATUS_OPENLDAP_UNINITIALIZED = 'uninitialized';
    const STATUS_OPENLDAP_INITIALIZED = 'initialized';
    const STATUS_OPENLDAP_INITIALIZING = 'initializing';
    const STATUS_OPENLDAP_BLOCKED_SLAVE = 'blocked_slave';

    const CONSTANT_WINADMIN_USERNAME = 'winadmin';
    const CONSTANT_GUEST_CN = 'Guest Account';
    const CONSTANT_GUEST_USERNAME = 'guest';
    const CONSTANT_WINADMIN_CN = 'Windows Administrator';
    const CONSTANT_GID_DOMAIN_COMPUTERS = '1000515';

    const DEFAULT_ADMIN_PRIVS = 'SeMachineAccountPrivilege SePrintOperatorPrivilege SeAddUsersPrivilege SeDiskOperatorPrivilege SeMachineAccountPrivilege SeTakeOwnershipPrivilege';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP accounts constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds a computer.
     *
     * The required $ sign will be appended if not present.
     *
     * Note: this method does not add the Samba attributes since
     * this is done by Samba when adding a machine.
     *
     * @param string $name computer name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_computer($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Append dollar sign and uppercase
        if (!preg_match('/\$$/', $name))
            $name = $name . '$';

        // $name = strtolower($name);
        $name = strtoupper($name);

        Validation_Exception::is_valid($this->validate_computer($name));

        // If computer exists, delete it
        try {
            $this->delete_computer($name);
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        $samba = new Samba();
        $domain_sid = $samba->get_domain_sid();

        $accounts = new Accounts_Driver();

        $ldap_object['objectClass'] = array(
            'top',
            'account',
            'posixAccount',
            'sambaSamAccount'
        );

        $ldap_object['cn'] = $name;
        $ldap_object['uid'] = $name;
        $ldap_object['description'] = lang('samba_common_computer') . ' ' . preg_replace('/\$$/', '', $name);
        $ldap_object['uidNumber'] = $accounts->get_next_uid_number();
        $ldap_object['gidNumber'] = self::CONSTANT_GID_DOMAIN_COMPUTERS;
        $ldap_object['homeDirectory'] = '/dev/null';
        $ldap_object['loginShell'] = '/sbin/nologin';
        $ldap_object['sambaSID'] = $domain_sid . '-' . $ldap_object['uidNumber'] ;
        $ldap_object['sambaAcctFlags'] = '[W          ]';

        $dn = 'cn=' . $this->ldaph->dn_escape($name) . ',' . OpenLDAP::get_computers_container();

        if (! $this->ldaph->exists($dn))
            $this->ldaph->add($dn, $ldap_object);
    }

    /**
     * Deletes a computer from the domain.
     *
     * @param string $name computer name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_computer($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Append dollar sign and uppercase
        if (!preg_match('/\$$/', $name))
            $name = $name . '$';

        $name = strtolower($name);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_initialized())
            throw new Samba_Not_Initialized_Exception();

        $dn = 'cn=' . $this->ldaph->dn_escape($name) . ',' . OpenLDAP::get_computers_container();

        if ($this->ldaph->exists($dn))
            $this->ldaph->delete($dn);
    }

    /**
     * Returns bind password. 
     *
     * @return string bind password
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();

        return $ldap->get_bind_password();
    }

    /**
     * Gets a detailed list of computers for the domain.
     *
     * @return  array  detailed list of computers
     * @throws Engine_Exception
     */

    public function get_computers()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_initialized())
            throw new Samba_Not_Initialized_Exception();

        $computers = array();

        // TODO: the "AddMachine" method does not add the Samba attributes since
        // this is done automagically by Samba.  If this automagic is missed for
        // some reason, then a Computer object may not have the sambaSamAccount object.
        // To be safe, use the posixAccount object so that we can cleanup.

        $result = $this->ldaph->search(
            '(objectclass=posixAccount)',
            OpenLDAP::get_computers_container(),
            array('uid', 'sambaSID', 'uidNumber')
        );

        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);

            $computer = $attributes['uid']['0'];
            $computers[$computer]['SID'] = isset($attributes['sambaSID'][0]) ? $attributes['sambaSID'][0] : "";
            $computers[$computer]['uidNumber'] = $attributes['uidNumber'][0];

            $entry = $this->ldaph->next_entry($entry);
        }
        
        return $computers;
    }   

    /**
     * Returns domain SID.
     *
     * @return string domain SID
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_domain_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (! $this->is_initialized())
            throw new Samba_Not_Initialized_Exception();

        $result = $this->ldaph->search(
            "(objectclass=sambaDomain)",
            OpenLDAP::get_base_dn(),
            array("sambaDomainName", "sambaSID")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $sid = $attributes['sambaSID'][0];
        } else {
            throw new Engine_Exception(lang('samba_common_domain_name_missing_in_ldap'));
        }

        return $sid;
    }

    /**
     * Returns domain.
     *
     * @return string domain
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if (($mode !== Mode_Engine::MODE_SLAVE) && !$this->is_initialized())
            throw new Samba_Not_Initialized_Exception();

        $result = $this->ldaph->search(
            "(objectclass=sambaDomain)",
            OpenLDAP::get_base_dn(),
            array("sambaDomainName", "sambaSID")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $domain = $attributes['sambaDomainName'][0];
        } else {
            throw new Engine_Exception(lang('samba_common_domain_name_missing_in_ldap'));
        }

        return $domain;
    }

    /**
     * Returns status of Samba initialization.
     *
     * @return string status
     * @throws Engine_Exception
     */

    public function get_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Samba initialized
        //------------------

        $file = new File(Samba::FILE_INITIALIZED);

        if ($file->exists())
            return self::STATUS_SAMBA_INITIALIZED;

        // Samba initializing
        //-------------------

        $file = new File(Samba::FILE_INITIALIZING);
        $initializing_lock = fopen(Samba::FILE_INITIALIZING, 'r');

        if ($file->exists() && !flock($initializing_lock, LOCK_SH | LOCK_NB))
            return self::STATUS_SAMBA_INITIALIZING;

        // Samba OpenLDAP initialized
        //---------------------------

        $file = new File(self::FILE_INITIALIZED);

        if ($file->exists())
            return self::STATUS_OPENLDAP_INITIALIZED;

        // Samba OpenLDAP initializing
        //----------------------------

        $file = new File(self::FILE_INITIALIZING);
        $initializing_lock = fopen(self::FILE_INITIALIZING, 'r');

        if ($file->exists() && !flock($initializing_lock, LOCK_SH | LOCK_NB))
            return self::STATUS_OPENLDAP_INITIALIZING;

        // Slave waiting on master initialization
        //---------------------------------------

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if (($mode === Mode_Engine::MODE_SLAVE) && !$this->is_ready())
            return self::STATUS_OPENLDAP_BLOCKED_SLAVE;

        // Samba OpenLDAP uninitialized
        //-----------------------------

        return self::STATUS_OPENLDAP_UNINITIALIZED;
    }

    /**
     * Initializes the local Samba system environment.
     *
     * @param string  $netbios_  name netbios_name
     * @param string  $workgroup workgroup/Windows domain
     * @param string  $password  password for winadmin
     * @param boolean $force     force initialization
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function initialize_samba_as_master_or_standalone($netbios, $workgroup, $password, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Note: an empty password is passed by the ClearOS 5.x to 6 import tool.
        // There's no need to run the actions that require a password on an import.

        // Directory needs to be initialized
        //----------------------------------

        if (! $this->is_initialized())
            throw new OpenLDAP_Driver_Not_Initialized();

        // Handle force flag
        //------------------

        $samba = new Samba();

        if ($samba->is_initialized()) {
            if ($force) {
                clearos_log('samba', 'forcing Samba initialization');
                $samba->set_initialized(FALSE);
            } else {
                return;
            }
        }

        clearos_log('samba', 'initializing Samba system');

        // Lock state file
        //----------------

        $lock_file = new File(Samba::FILE_INITIALIZING);
        $initializing_lock = fopen(Samba::FILE_INITIALIZING, 'w');

        if (!flock($initializing_lock, LOCK_EX | LOCK_NB)) {
            clearos_log('samba', 'Samba initialization is already running');
            return;
        }

        // Set the winadmin password
        //--------------------------

        if (! empty($password)) {
            clearos_log('samba', 'setting winadmin password');

            try {
                $user = User_Factory::create(self::CONSTANT_WINADMIN_USERNAME);
                $user->reset_password($password, $password, self::CONSTANT_WINADMIN_USERNAME);
            } catch (Exception $e) {
                $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
                throw new Exception($e);
            }
        }

        // Set default mode
        //-----------------

        clearos_log('samba', "setting netbios name and workgroup: $netbios - $workgroup");

        try {
            $sysmode = Mode_Factory::create();
            $mode = $sysmode->get_mode();

            $samba = new Samba();
            $samba->set_netbios_name($netbios);
            $samba->set_workgroup($workgroup);

            if (($mode === Mode_Engine::MODE_MASTER) || ($mode === Mode_Engine::MODE_STANDALONE)) {
                clearos_log('samba', 'setting mode to PDC');
                $samba->set_mode(Samba::MODE_PDC);
            } else if ($mode === Mode_Engine::MODE_SLAVE) {
                clearos_log('samba', 'setting mode to BDC');
                $samba->set_mode(Samba::MODE_BDC);
            }
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
            throw new Exception($e);
        }

        // Save the LDAP an Idmap passwords
        //---------------------------------

        clearos_log('samba', 'storing LDAP credentials');

        try {
            $this->_save_bind_password();
            $this->_save_idmap_password();
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
            throw new Exception($e);
        }

        // Net calls for privs an joining system to domain
        // Note: Samba needs to be running for the next steps
        //---------------------------------------------------

        clearos_log('samba', 'starting Samba services for net calls');

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();
        $nslcd = new Nslcd();

        try {
            // Do a hard reset on Nslcd (brutal)
            if ($nslcd->is_installed()) {
                if ($nslcd->get_running_state())
                    $nslcd->restart();
                else
                    $nslcd->set_running_state(TRUE);
            }

            // Do a hard reset on Winbind
            if ($winbind->get_running_state())
                $winbind->restart();
            else
                $winbind->set_running_state(TRUE);

            $nmbd->set_running_state(TRUE);
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
            throw new Exception($e);
        }

        try {
            $smbd->set_running_state(TRUE);
        } catch (Exception $e) {
            // Not the end of the world, can fail if LDAP is not quite ready
        }

        $nmbd->set_boot_state(TRUE);
        $smbd->set_boot_state(TRUE);
        $winbind->set_boot_state(TRUE);

        // Grant default privileges to winadmin et al
        //-------------------------------------------

        if (! empty($password)) {
            clearos_log('samba', 'granting privileges for domain_users');

            try {
                $this->_net_grant_default_privileges($password);
            } catch (Exception $e) {
                $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
                throw new Exception($e);
            }
        }

        // Join the local system to itself
        //--------------------------------

        if (! empty($password)) {
            clearos_log('samba', 'joining system to the domain');

            try {
                $this->_net_rpc_join($password);
            } catch (Exception $e) {
                $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
                throw new Exception($e);
            }
        }

        // Update local file permissions
        //------------------------------

        try {
            $samba->update_local_file_permissions();
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
            throw new Exception($e);
        }

        // Set the local system initialized flag
        //--------------------------------------

        clearos_log('samba', 'finished local initialization');

        try {
            $samba->set_initialized(TRUE);
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
            throw new Exception($e);
        }

        // Cleanup LDAP
        //-------------

        try {
            $samba_mode = $samba->get_mode();
            if (($samba_mode === Samba::MODE_PDC) || ($samba_mode === Samba::MODE_SIMPLE_SERVER)) {
                $this->cleanup_entries();
                $this->cleanup_sids();
            }
        } catch (Exception $e) {
            // Not fatal
        }

        // Do a hard reset on Winbind and smb... again
        //---------------------------------------------

        try {
            if ($winbind->get_running_state())
                $winbind->restart();
            else
                $winbind->set_running_state(TRUE);
        } catch (Exception $e) {
            // Not fatal
        }

        try {
            if ($smbd->get_running_state())
                $smbd->restart();
            else
                $smbd->set_running_state(TRUE);
        } catch (Exception $e) {
            // Not fatal
        }

        // Cleanup file / file lock
        //-------------------------

        $this->_delete_lock($initializing_lock, Samba::FILE_INITIALIZING);
    }

    /**
     * Initializes the local Samba system environment on a slave.
     *
     * @param string  $netbios_ name netbios_name
     * @param string  $password password for winadmin
     * @param boolean $force    force initialization
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function initialize_samba_as_slave($netbios, $password, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Run initialization again, but with Netbios name and password to join domain
        //----------------------------------------------------------------------------

        $this->_initialize_slave_system($netbios, $password);

        // Update local file permissions
        //------------------------------

        $samba = new Samba();
        $samba->update_local_file_permissions();

        // Set the local system initialized flag
        //--------------------------------------

        $samba->set_initialized(TRUE);
    }

    /**
     * Initializes system using sane defaults.
     *
     * @param boolean $force    force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    public function initialize($force = FALSE, $domain = 'CLEARSYSTEM')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if initialized
        //--------------------

        if ($this->is_initialized()) {
            if ($force) {
                clearos_log('samba', 'forcing initialization');
                $this->_set_initialized(FALSE);

                $samba = new Samba();
                $samba->set_initialized(FALSE);
            } else {
                return;
            }
        }

        // Bail if driver not set
        //-----------------------

        try {
            $accounts = new Accounts_Configuration();
            $driver = $accounts->get_driver();
        } catch (Accounts_Driver_Not_Set_Exception $e) {
            return;
        }

        // Bail if OpenLDAP is not ready
        // Bail if slave, but master is not Samba-ready
        //---------------------------------------------

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if ($mode === Mode_Engine::MODE_SLAVE) {
            if (!$this->is_ready())
                return;
        } else {
            $accounts = new Accounts_Driver();
            $accounts_ready = $accounts->is_ready();

            if (!$accounts_ready)
                return;
        }

        // Lock state file
        //----------------

        $lock_file = new File(self::FILE_INITIALIZING);
        $initializing_lock = fopen(self::FILE_INITIALIZING, 'w');

        if (!flock($initializing_lock, LOCK_EX | LOCK_NB)) {
            clearos_log('samba', 'initialization is already running');
            return;
        }

        // Initialize Samba system
        //------------------------

        try {
            if (($mode === Mode_Engine::MODE_MASTER) || ($mode === Mode_Engine::MODE_STANDALONE))
                $this->_initialize_master_system($domain, NULL, $force);
            else if ($mode === Mode_Engine::MODE_SLAVE)
                $this->_initialize_slave_system('BDC');
            else
                return;
        } catch (Exception $e) {
            $this->_delete_lock($initializing_lock, self::FILE_INITIALIZING);
            throw new Exception($e);
        }

        // Delete SID cache
        //-----------------

        $file = new File(Samba::FILE_DOMAIN_SID_CACHE);

        if ($file->exists())
            $file->delete();

        // Start winbind, man
        //-------------------

        try {
            clearos_log('samba', 'starting winbind');

            $winbind = new Winbind();
            $winbind->set_boot_state(TRUE);
            $winbind->set_running_state(TRUE);
        } catch (Exception $e) {
            // Not fatal
        }

        // And scene
        //----------

        clearos_log('samba', 'finished directory initialization... whew');

        $this->_set_initialized(TRUE);
        $this->_delete_lock($initializing_lock, self::FILE_INITIALIZING);
    }

    /**
     * Checks to see if Samba directory has been initialized.
     *
     * @return boolean TRUE if Samba has been initialized in LDAP
     * @throws Engine_Exception, LDAP_Offline_Exception
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        // TODO: remove file_exists check on old path in version 7
        if ($file->exists() || file_exists('/var/clearos/samba/initialized_directory'))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Checks to see if directory is Samba-ready.
     *
     * Slave nodes need to know if Samba components exist in the directory.
     *
     * @return boolean TRUE if directory is Samba-ready
     * @throws Engine_Exception, LDAP_Offline_Exception
     */

    public function is_ready()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $result = $this->ldaph->search(
            "(objectclass=sambaDomain)",
            OpenLDAP::get_base_dn(),
            array("sambaDomainName", "sambaSID")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Runs directory initialization.
     *
     * @param boolean $force force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    public function run_initialize($force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['background'] = TRUE;

        $force = ($force) ? '-f' : '';

        $shell = new Shell();
        $shell->execute(self::COMMAND_SAMBA_OPENLDAP_INITIALIZE, $force, TRUE, $options);
    }

    /**
     * Initializes the Samba system environment.
     *
     * @param string  $netbios_  name netbios_name
     * @param string  $workgroup workgroup/Windows domain
     * @param string  $password  password for winadmin
     * @param boolean $force     force initialization
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function run_initialize_samba_as_master_or_standalone($netbios, $workgroup, $password, $force)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_netbios_name($netbios));
        Validation_Exception::is_valid($this->validate_workgroup($workgroup));
        Validation_Exception::is_valid($this->validate_password($password));

        $options['background'] = TRUE;

        $force = ($force) ? '-f' : '';

        $shell = new Shell();
        $shell->execute(self::COMMAND_SAMBA_INITIALIZE, "-d '$workgroup' -n '$netbios' -p '$password' $force", TRUE, $options);
    }

    /**
     * Initializes the Samba system environment.
     *
     * @param string  $netbios_  name netbios_name
     * @param string  $password  password for winadmin
     * @param boolean $force     force initialization
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function run_initialize_samba_as_slave($netbios, $password, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_netbios_name($netbios));
        Validation_Exception::is_valid($this->validate_password($password));

        $options['background'] = TRUE;

        $force = ($force) ? '-f' : '';

        $shell = new Shell();
        $shell->execute(self::COMMAND_SAMBA_INITIALIZE, "-n '$netbios' -p '$password' $force", TRUE, $options);
    }

    /**
     * Updates existing groups with Windows Networking group information (mapping).
     *
     * The ClearOS directory is designed to work without the Windows Networking
     * overlay.  When Windows Networking is enabled, we need to go through all the
     * existing groups and add the required Windows fields.
     *
     * @param string $domain_sid domain SID
     *
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    public function update_group_mappings($domain_sid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_initialized())
            return;

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (empty($domain_sid)) {
            $samba = new Samba();
            $domain_sid = $samba->get_domain_sid();
        }

        $group_manager = new Group_Manager_Driver();
        $group_details = $group_manager->get_details(Group_Driver::FILTER_ALL);

        // Add/Update the groups
        //----------------------

        foreach ($group_details as $group_name => $group_info) {

            // Skip system (non-LDAP) groups
            //------------------------------

            if ($group_info['core']['type'] === Group_Driver::TYPE_SYSTEM)
                continue;

            // Skip groups with existing Samba attributes
            //-------------------------------------------

            if (isset($group_info['extensions']['samba']['sid']))
                continue;

            // Update group
            //-------------

            $group = new Group_Driver($group_name);

            clearos_log('samba', "adding samba mappings to group $group_name");
            $new_group_info['extensions']['samba']['sid'] = $domain_sid . '-' . $group_info['core']['gid_number'];
            $new_group_info['extensions']['samba']['group_type'] = 2;
            $new_group_info['extensions']['samba']['display_name'] = $group_info['core']['group_name'];

            $group->update($new_group_info);
        }
    }

    /**
     * Sets administrator passord.
     *
     * @param string $password
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_administrator_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $user = User_Factory::create(self::CONSTANT_WINADMIN_USERNAME);
        $user->reset_password($password, $password, self::CONSTANT_WINADMIN_USERNAME);

        // Rejoin for good measure (hard time getting net rpc join to run consistently)
        //-----------------------------------------------------------------------------

        $samba = new Samba();

        if ($samba->get_mode() === Samba::MODE_PDC)
            $this->add_computer($samba->get_netbios_name());

        // $this->_net_rpc_join($password);

        // Cleanup LDAP
        //-------------

        try {
            $samba_mode = $samba->get_mode();
            if (($samba_mode === Samba::MODE_PDC) || ($samba_mode === Samba::MODE_SIMPLE_SERVER)) {
                $this->cleanup_entries();
                $this->cleanup_passwords();
            }
        } catch (Exception $e) {
            // Not fatal
        }
    }

    /**
     * Sets workgroup/domain name LDAP objects.
     *
     * @param string $workgroup workgroup name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_domain($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_workgroup($workgroup));

        // Bail if directory is not initialized
        //-------------------------------------

        if (! $this->is_initialized())
            return;

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Bail if not a master/standalone node
        //-------------------------------------

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if (! (($mode === Mode_Engine::MODE_MASTER) || ($mode === Mode_Engine::MODE_STANDALONE)))
            return;

        // Expensive call, only do it on a change
        //---------------------------------------

        $workgroup = strtoupper($workgroup);
        $current_workgroup = $this->get_domain();

        if ($workgroup == $current_workgroup)
            return;

        // Update sambaDomainName object
        //------------------------------

        $sid = $this->get_domain_sid();
        $base_dn = OpenLDAP::get_base_dn();

        $result = $this->ldaph->search(
            "(&(objectclass=sambaDomain)(sambaSID=$sid))",
            $base_dn,
            array("sambaDomainName")
        );

        $entry = $this->ldaph->get_first_entry($result);

        if ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);

            if ($workgroup != $attributes['sambaDomainName'][0]) {
                $new_rdn = "sambaDomainName=" . $workgroup;
                $new_dn = $new_rdn . "," . $base_dn;
                $old_dn = "sambaDomainName=" . $attributes['sambaDomainName'][0] . "," . $base_dn;
                $newattrs['sambaDomainName'] = $workgroup;

                $this->ldaph->rename($old_dn, $new_rdn);
                $this->ldaph->modify($new_dn, $newattrs);
            }
        }

        // Update sambaDomain attribute for all users
        //-------------------------------------------

        $users_container = OpenLDAP::get_users_container();

        $result = $this->ldaph->search(
            "(sambaDomainName=*)",
            $users_container,
            array("cn")
        );

        $entry = $this->ldaph->get_first_entry($result);
        $newattrs['sambaDomainName'] = $workgroup;

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);
            $this->ldaph->modify('cn=' . $attributes['cn'][0] . "," . $users_container , $newattrs);
            $entry = $this->ldaph->next_entry($entry);
        }

        // Update Samba configuration
        //---------------------------

        $samba = new Samba();
        $samba->set_workgroup($workgroup);

        // Clean up secrets file
        //----------------------

        $this->_update_secrets();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for computer.
     *
     * @param string $computer computer
     *
     * @return string error message if computer is invalid
     */

    public function validate_computer($computer)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-zA-Z0-9_\-\.]+)\$$/', $computer))
            return lang('samba_common_computer_invalid') . " " . $computer; // FIXME
    }

    /**
     * Validation routine for netbios name.
     *
     * @param string $netbios_name system name
     *
     * @return string error message if netbios name is invalid
     */

    public function validate_netbios_name($netbios_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $samba = new Samba();

        return $samba->validate_netbios_name($netbios_name);
    }

    /**
     * Validation routine for password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        $samba = new Samba();

        return $samba->validate_password($password);
    }

    /**
     * Validation routine for workgroup
     *
     * To avoid issues on Windows networks:
     * - the netbios_name and workgroup must be different
     * - the host nickname (left-side of the hostname) must not match the workgroup
     *
     * @param  string  $workgroup  workgroup name
     *
     * @return  boolean  TRUE if workgroup is valid
     */

    public function validate_workgroup($workgroup)
    {
        clearos_profile(__METHOD__, __LINE__);

        $samba = new Samba();

        return $samba->validate_workgroup($workgroup);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // F R I E N D  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Cleans up stray LDAP entries.
     *
     * @access private
     *
     * @return void
     */

    public function cleanup_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Look for stray sambaSID dn entries
        //-----------------------------------

        $result = $this->ldaph->search('(objectclass=sambaSidEntry)');
        $entry = $this->ldaph->get_first_entry($result);

        $idmap_list = array();

        while ($entry) {
            $idmap_dn = $this->ldaph->get_dn($entry);

            if (preg_match('/ou=Idmap,/i', $idmap_dn))
                $idmap_list[] = $idmap_dn;

            $entry = $this->ldaph->next_entry($entry);
        }

        // Look for stray sambaDomain entries
        //-----------------------------------

        $samba = new Samba();
        $smbconf_domain = strtoupper($samba->get_workgroup());

        $result = $this->ldaph->search('(objectclass=sambaDomain)');
        $entry = $this->ldaph->get_first_entry($result);

        $domain_list = array();

        while ($entry) {
            $domain_dn = $this->ldaph->get_dn($entry);
            $domain = preg_replace('/^sambaDomainName=/', '', $domain_dn);
            $domain = preg_replace('/,.*/', '', $domain);
            $domain = strtoupper($domain);

            if ($domain != $smbconf_domain)
                $domain_list[] = $this->ldaph->get_dn($entry);

            $entry = $this->ldaph->next_entry($entry);
        }

        // Perform cleanup
        //----------------

        foreach ($idmap_list as $idmap) {
            clearos_log('samba', 'cleaning up idmap entry: ' . $idmap);
            $this->ldaph->delete($idmap);
        }

        foreach ($domain_list as $domain) {
            clearos_log('samba', 'cleaning up domain entry: ' . $domain);
            $this->ldaph->delete($domain);
        }
    }

    /**
     * Cleans up internal passwords.
     *
     * @access private
     *
     * @return void
     */

    public function cleanup_passwords()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_save_bind_password();
        $this->_save_idmap_password();
    }

    /**
     * Cleans up SIDs.
     *
     * @access private
     *
     * @return void
     */

    public function cleanup_sids()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Fix domain SID
        //---------------

        $file = new File(Samba::FILE_DOMAIN_SID);

        if ($file->exists()) {
            $ldap = new LDAP_Driver();
            $ldaph = $ldap->get_ldap_handle();

            $result = $ldaph->search('(objectclass=sambaDomain)');
            $entry = $ldaph->get_first_entry($result);

            while ($entry) {
                $attributes = $ldaph->get_attributes($entry);
                $domain_dn = $ldaph->get_dn($entry);
                $sid = trim($file->get_contents());

                if ($attributes['sambaSID'][0] != $sid) {
                    $ldap_object['sambaSID'] = $sid;
                    $ldaph->modify($domain_dn, $ldap_object);
                    clearos_log('samba', 'fixing domain SID for ' . $attributes['sambaDomainName'][0]);

                    $samba = new Samba();
                    $samba->set_local_sid($sid);
                }

                $entry = $ldaph->next_entry($entry);
            }
        }

        // Cleanup user and group SIDs
        //----------------------------

        if (!empty($sid)) {
            // Users
            $user_manager = User_Manager::create();
            $users = $user_manager->get_list(User_Engine::FILTER_ALL);

            foreach ($users as $username) {
                $user = User::create($username);

                $details = $user->get_info();
                $user_sid = $sid . '-' . $details['core']['uid_number'];

                if ($details['extensions']['samba']['sid'] != $user_sid) {
                    $user_info['extensions']['samba']['sid'] = $user_sid;
                    $user->update($user_info);
                    clearos_log('samba', 'fixing user SID for ' . $username);
                }
            }

            // Groups
            $group_manager = Group_Manager::create();
            $groups = $group_manager->get_list(Group_Engine::FILTER_ALL);

            foreach ($groups as $group_name) {
                $group = Group::create($group_name);

                $details = $group->get_info();
                $group_sid = $sid . '-' . $details['core']['gid_number'];

                if (isset($details['extensions']['samba']['sid'])) {
                    $parts = preg_split('/-/', $details['extensions']['samba']['sid']);

                    // Skip special groups (e.g. S-1-5-32-546)
                    if (count($parts) < 7)
                        continue;

                    $base_sid = $parts[0] . '-' . $parts[1] . '-' . $parts[2] . '-' . $parts[3] .
                        '-' . $parts[4] . '-' . $parts[5] . '-' . $parts[6];

                    if ($base_sid != $sid) {
                        $new_sid = $sid . '-' . $parts[7];
                        $group_info['extensions']['samba']['sid'] = $new_sid;
                        $group->update($group_info);
                        clearos_log('samba', 'fixing group SID for ' . $group_name);
                    }
                }
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes state files used by Samba.
     *
     * @access private
     *
     * @return void
     */

    protected function _archive_state_files()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Create backup directory
        //------------------------

        $backup_path = Samba::PATH_STATE_BACKUP . '/varbackup-' . strftime('%m-%d-%Y-%H-%M-%S-%s', time());

        $backup_folder = new Folder($backup_path);

        if (! $backup_folder->exists())
            $backup_folder->create('root', 'root', '0755');

        // Perform backup
        //---------------

        $folder = new Folder(Samba::PATH_STATE);
        $state_files = $folder->get_recursive_listing();

        foreach ($state_files as $filename) {
            if (! preg_match('/(tdb)|(dat)$/', $filename))
                continue;

            if (preg_match('/\//', $filename)) {
                $dirname = dirname($filename);
                $backup_folder = new Folder($backup_path . '/' . $dirname);

                if (! $backup_folder->exists())
                    $backup_folder->create('root', 'root', '0755');
            }

            $file = new File(Samba::PATH_STATE . '/' . $filename);
            $file->move_to($backup_path . '/' . $filename);
        }
    }

    /**
     * Delete lock file..
     *
     * @param handle &$lock_file lock file
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_lock(&$lock_handle, $lock_file)
    {
        clearos_profile(__METHOD__, __LINE__);

        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);

        $lock = new File($lock_file);

        if ($lock->exists())
            $lock->delete();
    }

    /**
     * Generates a SID.
     *
     * @param string $type SID type
     *
     * @access private
     * @return string SID
     * @throws Engine_Exception
     */

    protected function _generate_sid($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($type == Samba::TYPE_SID_LOCAL)
            $param = 'getlocalsid';
        else if ($type == Samba::TYPE_SID_DOMAIN)
            $param = 'getdomainsid';
        else
            throw new Validation_Exception('Invalid SID type');

        // Create minimalist Samba config to generate a domain or local SID
        //-----------------------------------------------------------------

        $config_lines = "[global]\n";
        $config_lines .= "netbios name = mytempnetbios\n";
        $config_lines .= "workgroup = mytempdomain\n";
        $config_lines .= "domain logons = Yes\n";
        $config_lines .= "private dir = " . CLEAROS_TEMP_DIR . "\n";

        $config = new File(CLEAROS_TEMP_DIR . '/smb.conf');
            
        if ($config->exists())
            $config->delete();

        $config->create('root', 'root', '0644');
        $config->add_lines($config_lines);

        // Run net getdomainsid / getlocalsid
        //-----------------------------------

        $secrets = new File(CLEAROS_TEMP_DIR . '/secrets.tdb');

        if ($secrets->exists())
            $secrets->delete();

        $shell = new Shell();

        $shell->execute(Samba::COMMAND_NET, '-s ' . CLEAROS_TEMP_DIR . '/smb.conf ' . $param, TRUE);

        $sid = $shell->get_last_output_line();
        $sid = preg_replace('/.*: /', '', $sid);

        $config->delete();

        return $sid;
    }

    /**
     * Creates an LDAP handle.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $this->ldaph = $ldap->get_ldap_handle();
    }

    /**
     * Initializes and then saves domain SID to file.
     *
     * @access private
     *
     * @return string domain SID
     * @throws Engine_Exception
     */

    protected function _initialize_domain_sid()
    {
        clearos_profile(__METHOD__, __LINE__);

        // If /etc/samba/domainsid exists, use it
        $file = new File(Samba::FILE_DOMAIN_SID, TRUE);

        if ($file->exists()) {
            $lines = $file->get_contents_as_array();
            $sid = $lines[0];
        } else {
            $sid = $this->_generate_sid(Samba::TYPE_SID_DOMAIN);

            $file->create('root', 'root', '400');
            $file->add_lines("$sid\n");
        }

        return $sid;
    }

    /**
     * Initializes and then saves local SID to file.
     *
     * @param string $sid local SID
     *
     * @access private
     * @return string local SID
     * @throws Engine_Exception
     */

    protected function _initialize_local_sid($sid)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(Samba::FILE_LOCAL_SID, TRUE);

        // If no SID is specified, use the local copy

        if (empty($sid) && $file->exists()) {
            $lines = $file->get_contents_as_array();
            return $lines[0];
        }

        // If local copy does not exist, create a new SID
        if (empty($sid))
            $sid = $this->_generate_sid(Samba::TYPE_SID_LOCAL);

        // Create a local copy of the SID
        if ($file->exists())
            $file->delete();

        $file->create("root", "root", "400");
        $file->add_lines("$sid\n");

        return $sid;
    }

    /**
     * Initializes master node with the necessary Samba elements.
     *
     * You do not need to have the server components of Samba installed
     * to run this initialization routine.  This simply initializes the
     * necessary bits to get LDAP up and running.
     *
     * @param string  $domain   workgroup / domain
     * @param string  $password password for winadmin
     * @param boolean $force    force initialization
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _initialize_master_system($domain, $password = NULL, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if already initialized
        //----------------------------

        if ($this->is_initialized() && !$force)
            return;

        // Bail if we are not a master/standalone system
        //----------------------------------------------

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if (($mode !== Mode_Engine::MODE_MASTER) && ($mode !== Mode_Engine::MODE_STANDALONE))
            throw new Engine_Exception(lang('samba_common_system_not_in_master_mode'));

        clearos_log('samba', 'initializing master/standalone LDAP');

        // Shutdown Samba daemons if they are installed/running
        //-----------------------------------------------------

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();

        $nmbd_was_running = FALSE;
        $smbd_was_running = FALSE;
        $winbind_was_running = FALSE;

        if ($smbd->is_installed()) {
            $smbd_was_running = $smbd->get_running_state();
            if ($smbd_was_running) {
                clearos_log('samba', 'stopping smb during initialization');
                $smbd->set_running_state(FALSE);
            }
        }

        if ($nmbd->is_installed()) {
            $nmbd_was_running = $nmbd->get_running_state();
            if ($nmbd_was_running) {
                clearos_log('samba', 'stopping nmb during initialization');
                $nmbd->set_running_state(FALSE);
            }
        }

        if ($winbind->is_installed()) {
            $winbind_was_running = $winbind->get_running_state();
            if ($winbind_was_running) {
                clearos_log('samba', 'stopping winbind during initialization');
                $winbind->set_running_state(FALSE);
            }
        }

        // Archive the files (usually in /var/lib/samba)
        //----------------------------------------------

        clearos_log('samba', 'archiving old state files');

        $this->_archive_state_files();

        // Set Samba
        //----------

        clearos_log('samba', 'configuring smb.conf');

        $samba = new Samba(); 

        $samba->set_mode(Samba::MODE_PDC);
        $samba->set_workgroup($domain);
        $samba->set_password_servers(array());
        $samba->set_realm('');
        $samba->set_wins_server_and_support('', TRUE);
        $samba->set_default_idmap_backend('ldap');

        // Bootstrap the domain SID
        //-------------------------

        clearos_log('samba', 'initializing SIDs');
        $domainsid = $this->_initialize_domain_sid();

        // Set local SID to be the same as domain SID
        //-------------------------------------------

        $this->_initialize_local_sid($domainsid);

        // Implant all the Samba elements into LDAP
        //-----------------------------------------

        $this->_initialize_ldap($domainsid, $password);

        // Managed groups
        //---------------

        $this->_initialize_windows_groups($domainsid);
        $this->_initialize_group_memberships($domainsid);
        $this->update_group_mappings($domainsid);

        // Save the LDAP password into secrets
        //------------------------------------

        clearos_log('samba', 'storing LDAP credentials');
        $this->_save_bind_password();

        // Restart Samba if it was running 
        //--------------------------------

        if ($nmbd_was_running)
            $nmbd->set_running_state(TRUE);

        if ($smbd_was_running)
            $smbd->set_running_state(TRUE);

        if ($winbind_was_running)
            $winbind->set_running_state(TRUE);

        // For good measure, update local file permissions
        //------------------------------------------------

        try {
            $samba->update_local_file_permissions();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        // Handle Samba configuration and post cleanup
        //--------------------------------------------

        clearos_log('samba', 'updating secrets');

        $this->_update_secrets();
    }

    /**
     * Initializes slave node with the necessary Samba elements.
     *
     * @param string $netbios  NetBIOS name
     * @param string $password password for winadmin
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _initialize_slave_system($netbios, $password = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        clearos_log('samba', 'initializing slave mode');

        // Bail if we are not a slave system
        //----------------------------------

        $sysmode = Mode_Factory::create();
        $mode = $sysmode->get_mode();

        if ($mode !== Mode_Engine::MODE_SLAVE)
            throw new Engine_Exception('samba_system_not_in_slave_mode');

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_netbios_name($netbios));

        if (! is_null($password))
            Validation_Exception::is_valid($this->validate_password($password));

        // Set BDC defaults
        //-----------------

        clearos_log('samba', 'configuring smb.conf');

        $workgroup = $this->get_domain();

        $master = $sysmode->get_master_hostname();

        $samba = new Samba();

        $samba->set_mode(Samba::MODE_BDC);
        $samba->set_netbios_name($netbios);
        $samba->set_workgroup($workgroup);
        $samba->set_wins_server_and_support($master, FALSE);

        $this->_update_secrets();

        clearos_log('samba', 'starting up Samba services');

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();
        $nslcd = new Nslcd();

        if ($nmbd->is_installed())
            $nmbd->set_boot_state(TRUE);

        if ($smbd->is_installed())
            $smbd->set_boot_state(TRUE);

        $winbind->set_boot_state(TRUE);

        try {
            // Do a hard reset on Nslcd (brutal)
            if ($nslcd->is_installed()) {
                if ($nslcd->get_running_state())
                    $nslcd->restart();
                else
                    $nslcd->set_running_state(TRUE);
            }

            if ($nmbd->is_installed())
                $nmbd->set_running_state(TRUE);

            if ($smbd->is_installed())
                $smbd->set_running_state(TRUE);

            if ($winbind->get_running_state())
                $winbind->restart();
            else
                $winbind->set_running_state(TRUE);
        } catch (Exception $e) {
            // Not the end of the world 
        }

        // Join system to domain
        //----------------------

        if (! is_null($password)) {
            clearos_log('samba', 'joining system to the domain');
            $this->_net_rpc_join($password, $master);
        }
    }

    /**
     * Initialize LDAP configuration for Samba.
     *
     * @param string $domainsid domain SID
     * @param string $password windows administrator password
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_ldap($domainsid, $password = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $samba = new Samba();

        $domain = $samba->get_workgroup();
        $logon_drive = $samba->get_logon_drive();
        $base_dn = OpenLDAP::get_base_dn();

        if (empty($password))
            $password = LDAP_Utilities::generate_password();

        $sha_password = '{sha}' . LDAP_Utilities::calculate_sha_password($password);
        $nt_password = LDAP_Utilities::calculate_nt_password($password);

        // Domain
        //--------------------------------------------------------

        $domainobj['objectClass'] = array(
            'top',
            'sambaDomain'
        );

        $dn = 'sambaDomainName=' . $domain . ',' . $base_dn;
        $domainobj['sambaDomainName'] = $domain;
        $domainobj['sambaSID'] = $domainsid;
        $domainobj['sambaNextGroupRid'] = 20000000;
        $domainobj['sambaNextUserRid'] = 20000000;
        $domainobj['sambaNextRid'] = 20000000;
        $domainobj['sambaAlgorithmicRidBase'] = 1000;
        $domainobj['sambaMinPwdLength'] = 5;
        $domainobj['sambaPwdHistoryLength'] = 5;
        $domainobj['sambaLogonToChgPwd'] = 0;
        $domainobj['sambaMaxPwdAge'] = -1;
        $domainobj['sambaMinPwdAge'] = 0;
        $domainobj['sambaLockoutDuration'] = 60;
        $domainobj['sambaLockoutObservationWindow'] = 5;
        $domainobj['sambaLockoutThreshold'] = 0;
        $domainobj['sambaForceLogoff'] = 0;
        $domainobj['sambaRefuseMachinePwdChange'] = 0;

        if (! $this->ldaph->exists($dn)) {
            clearos_log('samba', 'adding sambaDomainName LDAP attribute');
            $this->ldaph->add($dn, $domainobj);
        }

        // Idmap
        //--------------------------------------------------------

        $dn = 'ou=Idmap,' . $base_dn;
        $idmap['objectClass'] = array(
            'top',
            'organizationalUnit'
        );
        $idmap['ou'] = 'Idmap';

        if (! $this->ldaph->exists($dn)) {
            clearos_log('samba', 'adding Idmap LDAP attribute');
            $this->ldaph->add($dn, $idmap);
        }

        // Users
        //--------------------------------------------------------

        $users_container = OpenLDAP::get_users_container();

        $winadmin_dn = 'cn=' . self::CONSTANT_WINADMIN_CN . ',' . $users_container;

        $userinfo[$winadmin_dn]['lastName'] = 'Administrator';
        $userinfo[$winadmin_dn]['firstName'] = 'Windows';
        $userinfo[$winadmin_dn]['uid'] = 'winadmin';

        $users[$winadmin_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'clearAccount'
        );
        $users[$winadmin_dn]['clearAccountStatus'] = TRUE;
        $users[$winadmin_dn]['clearSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['clearSHAPassword'] = $sha_password;
        $users[$winadmin_dn]['clearMicrosoftNTPassword'] = $nt_password;
        $users[$winadmin_dn]['sambaPwdLastSet'] = 0;
        $users[$winadmin_dn]['sambaLogonTime'] = 0;
        $users[$winadmin_dn]['sambaLogoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaKickoffTime'] = 2147483647;
        $users[$winadmin_dn]['sambaPwdCanChange'] = 0;
        $users[$winadmin_dn]['sambaPwdLastSet'] = time();
        $users[$winadmin_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$winadmin_dn]['sambaDomainName'] = $domain;
        $users[$winadmin_dn]['sambaHomeDrive'] = $logon_drive;
        $users[$winadmin_dn]['sambaPrimaryGroupSID'] = $domainsid . '-512';
        $users[$winadmin_dn]['sambaNTPassword'] = $nt_password;
        $users[$winadmin_dn]['sambaAcctFlags'] = '[U       ]';
        $users[$winadmin_dn]['sambaSID'] = $domainsid . '-500';

        $guest_dn = 'cn=' . self::CONSTANT_GUEST_CN . ',' . $users_container;

        $users[$guest_dn]['objectClass'] = array(
            'top',
            'posixAccount',
            'shadowAccount',
            'inetOrgPerson',
            'sambaSamAccount',
            'clearAccount'
        );
        $users[$guest_dn]['clearAccountStatus'] = TRUE;
        $users[$guest_dn]['clearSHAPassword'] = $sha_password;
        $users[$guest_dn]['clearMicrosoftNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaPwdLastSet'] = 0;
        $users[$guest_dn]['sambaLogonTime'] = 0;
        $users[$guest_dn]['sambaLogoffTime'] = 2147483647;
        $users[$guest_dn]['sambaKickoffTime'] = 2147483647;
        $users[$guest_dn]['sambaPwdCanChange'] = 0;
        $users[$guest_dn]['sambaPwdLastSet'] = time();
        $users[$guest_dn]['sambaPwdMustChange'] = 2147483647;
        $users[$guest_dn]['sambaDomainName'] = $domain;
        $users[$guest_dn]['sambaHomeDrive'] = $logon_drive;
        $users[$guest_dn]['sambaPrimaryGroupSID'] = $domainsid . '-514';
        $users[$guest_dn]['sambaLMPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaNTPassword'] = 'NO PASSWORDXXXXXXXXXXXXXXXXXXXXX';
        $users[$guest_dn]['sambaAcctFlags'] = '[NUD       ]';
        $users[$guest_dn]['sambaSID'] = $domainsid . '-501';

        foreach ($users as $dn => $object) {
            if ($this->ldaph->exists($dn)) {
                clearos_log('samba', "updating built-in user: $dn");
                $this->ldaph->modify($dn, $object);
            }
        }
    }

    /**
     * Initializes group memeberships.
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_group_memberships()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $user_manager = new User_Manager_Driver();
            $all_users = $user_manager->get_list();

            clearos_log('samba', 'populating domain_users group');
            $group = new Group_Driver('domain_users');
            $group->set_members($all_users);
        } catch (Exception $e) {
            // TODO: make this fatal
        }
    }

    /**
     * Initializes LDAP groups for Samba.
     *
     * @param string $domainsid domain SID
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Samba_Not_Initialized_Exception
     */

    protected function _initialize_windows_groups($domainsid = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        if (empty($domainsid)) {
            $samba = new Samba();
            $domainsid = $samba->get_domain_sid();
        }

        ///////////////////////////////////////////////////////////////////////////////
        // D O M A I N   G R O U P S
        ///////////////////////////////////////////////////////////////////////////////
        //
        // Samba uses the following convention for group mappings:
        // - the base part of the DN is the Posix group
        // - the displayName is the Windows group
        //
        ///////////////////////////////////////////////////////////////////////////////

        $groups = array();

        $group = 'domain_admins';
        $groups[$group]['core']['description'] = 'Domain Admins';
        $groups[$group]['core']['gid_number'] = '1000512';
        $groups[$group]['extensions']['samba']['sid'] = $domainsid . '-512';
        $groups[$group]['extensions']['samba']['group_type'] = 2;
        $groups[$group]['extensions']['samba']['display_name'] = 'Domain Admins';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;
        $groups[$group]['members'] = array(self::CONSTANT_WINADMIN_USERNAME);

        $group = 'domain_users';
        $groups[$group]['core']['description'] = 'Domain Users';
        $groups[$group]['core']['gid_number'] = '1000513';
        $groups[$group]['extensions']['samba']['sid'] = $domainsid . '-513';
        $groups[$group]['extensions']['samba']['group_type'] = 2;
        $groups[$group]['extensions']['samba']['display_name'] = 'Domain Users';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'domain_guests';
        $groups[$group]['core']['description'] = 'Domain Guests';
        $groups[$group]['core']['gid_number'] = '1000514';
        $groups[$group]['extensions']['samba']['sid'] = $domainsid . '-514';
        $groups[$group]['extensions']['samba']['group_type'] = 2;
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;
        $groups[$group]['extensions']['samba']['display_name'] = 'Domain Guests';
        $groups[$group]['members'] = array(self::CONSTANT_GUEST_USERNAME);

        $group = 'domain_computers';
        $groups[$group]['core']['description'] = 'Domain Computers';
        $groups[$group]['core']['gid_number'] = self::CONSTANT_GID_DOMAIN_COMPUTERS;
        $groups[$group]['extensions']['samba']['sid'] = $domainsid . '-515';
        $groups[$group]['extensions']['samba']['group_type'] = 2;
        $groups[$group]['extensions']['samba']['display_name'] = 'Domain Computers';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        /*
        $group = 'domain_controllers';
        $groups[$group]['core']['description'] = 'Domain Controllers';
        $groups[$group]['core']['gid_number'] = '1000516';
        $groups[$group]['extensions']['samba']['sid'] = $domainsid . '-516';
        $groups[$group]['extensions']['samba']['display_name'] = 'Domain Controllers';
        $groups[$group]['extensions']['samba']['group_type'] = 2;
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;
        */

        ///////////////////////////////////////////////////////////////////////////////
        // B U I L T - I N   G R O U P S
        ///////////////////////////////////////////////////////////////////////////////

        $group = 'administrators';
        $groups[$group]['core']['description'] = 'Administrators';
        $groups[$group]['core']['gid_number'] = '1000544';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-544';
        $groups[$group]['extensions']['samba']['sid_list'] = $domainsid . '-512';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Administrators';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'users';
        $groups[$group]['core']['description'] = 'Users';
        $groups[$group]['core']['gid_number'] = '1000545';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-545';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Users';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'guests';
        $groups[$group]['core']['description'] = 'Guests';
        $groups[$group]['core']['gid_number'] = '1000546';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-546';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Guests';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'power_users';
        $groups[$group]['core']['description'] = 'Power Users';
        $groups[$group]['core']['gid_number'] = '1000547';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-547';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Power Users';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'account_operators';
        $groups[$group]['core']['description'] = 'Account Operators';
        $groups[$group]['core']['gid_number'] = '1000548';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-548';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Account Operators';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'server_operators';
        $groups[$group]['core']['description'] = 'Server Operators';
        $groups[$group]['core']['gid_number'] = '1000549';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-549';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Server Operators';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'print_operators';
        $groups[$group]['core']['description'] = 'Print Operators';
        $groups[$group]['core']['gid_number'] = '1000550';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-550';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Print Operators';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        $group = 'backup_operators';
        $groups[$group]['core']['description'] = 'Backup Operators';
        $groups[$group]['core']['gid_number'] = '1000551';
        $groups[$group]['extensions']['samba']['sid'] = 'S-1-5-32-551';
        $groups[$group]['extensions']['samba']['group_type'] = 4;
        $groups[$group]['extensions']['samba']['display_name'] = 'Backup Operators';
        $groups[$group]['extensions']['mail']['distribution_list'] = 0;

        // Add/Update the groups
        //--------------------------------------------------------

        foreach ($groups as $group_name => $group_info) {
            $group = new Group_Driver($group_name);

            if ($group->exists()) {
                clearos_log('samba', "updating built-in group $group_name");
                $group->update($group_info);
            } else {
                clearos_log('samba', "adding built-in group $group_name");
                $group->add($group_info);
            }

            if (isset($group_info['members'])) {
                clearos_log('samba', "updating members for $group_name");
                $group->set_members($group_info['members']);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Cleans up the secrets file.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _update_secrets()
    {
        clearos_profile(__METHOD__, __LINE__);

        // In AD mode, we're done

        $samba = new Samba();

        if ($samba->get_mode() === Samba::MODE_AD_CONNECTOR)
            return;

        if (! $this->is_initialized())
            return;

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();

        $nmbd_wasrunning = FALSE;
        $smbd_wasrunning = FALSE;
        $winbind_wasrunning = FALSE;

        if ($winbind->is_installed()) {
            $winbind_wasrunning = $winbind->get_running_state();
            if ($winbind_wasrunning)
                $winbind->set_running_state(FALSE);
        }

        if ($smbd->is_installed()) {
            $smbd_wasrunning = $smbd->get_running_state();
            if ($smbd_wasrunning)
                $smbd->set_running_state(FALSE);
        }

        if ($nmbd->is_installed()) {
            $nmbd_wasrunning = $nmbd->get_running_state();
            if ($nmbd_wasrunning)
                $nmbd->set_running_state(FALSE);
        }

        $this->_save_bind_password();
        $this->_save_idmap_password();

        $samba->set_domain_sid();
        $samba->set_local_sid();

        try {
            if ($nmbd_wasrunning)
                $nmbd->set_running_state(TRUE);

            if ($smbd_wasrunning)
                $smbd->set_running_state(TRUE);

            if ($winbind_wasrunning)
                $winbind->set_running_state(TRUE);
        } catch (Exception $e) {
            sleep(5);
            if ($nmbd_wasrunning)
                $nmbd->set_running_state(TRUE);

            if ($smbd_wasrunning)
                $smbd->set_running_state(TRUE);

            if ($winbind_wasrunning)
                $winbind->set_running_state(TRUE);
        }
    }

    /**
     * Saves LDAP bind password to Samba secrets file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $bind_password = $this->get_bind_password();

        // Use pipe to avoid showing password in command line
        $options['stdin'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_SMBPASSWD, "-w " . $bind_password, TRUE, $options);
    }

    /**
     * Saves password required for Idmap.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_idmap_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $password = $this->get_bind_password();
        $options['stdin'] = TRUE;

        $shell = new Shell();
        $exitcode = $shell->Execute(self::COMMAND_NET, "idmap secret '*' $password", TRUE, $options);
    }

    /**
     * Grants default privileges for the system.
     *
     * @access private
     * @param string $password password for winadmin
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _net_grant_default_privileges($password, $target = '127.0.0.1')
    {
        clearos_profile(__METHOD__, __LINE__);

        $samba = new Samba();

        $domain = $samba->get_workgroup();
        $options['stdin'] = TRUE;
        $net_error = '';

        $shell = new Shell();
        $options['validate_exit_code'] = FALSE;

        for ($i = 0; $i < 10; $i++) {
            sleep(2); // wait or daemons to start, not atomic

            try {
                $retval = $shell->execute(self::COMMAND_NET, 'rpc rights grant "' . $domain . '\Domain Admins" ' .
                    self::DEFAULT_ADMIN_PRIVS . ' -I ' . $target . ' -U winadmin%' . $password, TRUE, $options);

                if ($retval == 0) {
                    $net_error = '';
                    break;
                } else {
                    $net_error = $shell->get_last_output_line();
                    clearos_log('samba', 'waiting for net rpc rights response');
                }
            } catch (Engine_Exception $e) {
                $net_error = clearos_exception_message($e);
                clearos_log('samba', 'stilll waiting for net rpc rights response');
            }
        }

        if (! empty($net_error))
            throw new Engine_Exception($net_error);
    }

    /**
     * Runs net rpc join command.
     *
     * @param string $password winadmin password
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _net_rpc_join($password, $target = '127.0.0.1')
    {
        clearos_profile(__METHOD__, __LINE__);

        $nmbd = new Nmbd();
        $smbd = new Smbd();
        $winbind = new Winbind();
        $samba = new Samba();

        $nmbd_wasrunning = FALSE;
        $smbd_wasrunning = FALSE;
        $winbind_wasrunning = FALSE;

        if ($winbind->is_installed()) {
            $winbind_wasrunning = $winbind->get_running_state();
            if (! $winbind_wasrunning)
                $winbind->set_running_state(TRUE);
        }

        if ($nmbd->is_installed()) {
            $nmbd_wasrunning = $nmbd->get_running_state();
            if (! $nmbd_wasrunning)
                $nmbd->set_running_state(TRUE);
        }

        if ($smbd->is_installed()) {
            $smbd_wasrunning = $smbd->get_running_state();
            if (! $smbd_wasrunning)
                $smbd->set_running_state(TRUE);
        }

        $netbios_name = $samba->get_netbios_name();

        $shell = new Shell();

        $options['stdin'] = TRUE;
        $options['validate_exit_code'] = FALSE;

        for ($inx = 1; $inx < 10; $inx++) {
            try {
                sleep(2);
                $retval = $shell->execute(self::COMMAND_NET, 'rpc join -S ' .$netbios_name .
                    ' -I ' . $target .  ' -U winadmin%' . $password, TRUE, $options);
                if ($retval == 0) {
                    $succeeded = TRUE;
                    break;
                } else {
                    clearos_log('samba', 'waiting for net rpc join response');
                }
            } catch (Exception $e) {
                // Try again
                clearos_log('samba', 'still waiting for net rpc join response');
            }
        }

        // TODO: Not the end of the world.
        // if (! $succeeded)

        if (! $smbd_wasrunning)
            $smbd->set_running_state(FALSE);

        if (!$nmbd_wasrunning)
            $nmbd->set_running_state(FALSE);

        if (!$winbind_wasrunning)
            $winbind->set_running_state(FALSE);
    }

    /**
     * Sets initialization flag.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_initialized($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        if ($state && !$file->exists())
            $file->create('root', 'root', '0644');
        else if (!$state && $file->exists())
            $file->delete();
    }
}
