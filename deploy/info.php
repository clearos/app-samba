<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'samba';
$app['version'] = '1.6.8';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('samba_app_description');
$app['tooltip'] = lang('samba_file_share_tooltip');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('samba_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['samba']['title'] = $app['name'];
$app['controllers']['mode']['title'] = lang('samba_mode');
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['computers']['title'] = lang('samba_computers');
$app['controllers']['administrator']['title'] = lang('samba_administrator_account');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_provides'] = array(
    'system-windows-driver',
);

$app['requires'] = array(
    'app-accounts',
    'app-groups',
    'app-users',
    'app-network',
    'samba >= 3.6.1',
    'samba < 4',
);

// Note: explicit libtalloc and samba-common dependencies make yum happy
// when it distinguishes between Samba 3 and Samba 4.
$app['core_requires'] = array(
    'app-accounts-core >= 1:1.5.40',
    'app-groups-core',
    'app-users-core >= 1:1.1.1',
    'app-network-core', 
    'app-openldap-core >= 1:1.5.40', 
    'app-openldap-directory-core', 
    'app-samba-extension-core >= 1:1.4.11',
    'app-samba-common-core >= 1:1.4.70',
    'libtalloc',
    'samba-common >= 3.6.1',
    'samba-common < 4',
    'samba-client >= 3.6.1',
    'samba-client < 4',
    'samba-winbind >= 3.6.1',
    'samba-winbind < 4',
    'samba-winbind-clients >= 3.6.1',
    'samba-winbind-clients < 4',
    'system-mode-driver',
    'tdb-tools >= 1.2.9'
);

$app['core_file_manifest'] = array( 
    'smb.ldap.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.ldap.conf' ),
    'smb.winbind.conf' => array( 'target' => '/var/clearos/ldap/synchronize/smb.winbind.conf' ),
    'add-samba-directories' => array(
        'target' => '/usr/sbin/add-samba-directories',
        'mode' => '0755',
    ),
    'add-windows-group-info' => array(
        'target' => '/usr/sbin/add-windows-group-info',
        'mode' => '0755',
    ),
    'samba-add-machine' => array(
        'target' => '/usr/sbin/samba-add-machine',
        'mode' => '0755',
    ),
    'app-samba-openldap-initialize' => array(
        'target' => '/usr/sbin/app-samba-openldap-initialize',
        'mode' => '0755',
    ),
    'app-samba-initialize' => array(
        'target' => '/usr/sbin/app-samba-initialize',
        'mode' => '0755',
    ),
    'openldap-online-event'=> array(
        'target' => '/var/clearos/events/openldap_online/samba',
        'mode' => '0755'
    ),
    'openldap-configuration-event'=> array(
        'target' => '/var/clearos/events/openldap_configuration/samba',
        'mode' => '0755'
    ),
    'accounts-ready-event'=> array(
        'target' => '/var/clearos/events/accounts_ready/samba',
        'mode' => '0755'
    ),
    'nmb.php'=> array('target' => '/var/clearos/base/daemon/nmb.php'),
    'smb.php'=> array('target' => '/var/clearos/base/daemon/smb.php'),
    'winbind.php'=> array('target' => '/var/clearos/base/daemon/winbind.php'),
);

$app['core_directory_manifest'] = array(
    '/var/clearos/samba' => array(),
    '/var/clearos/samba/backup' => array(),
    '/var/clearos/samba/lock' => array(
        'mode' => '0775',
        'owner' => 'root',
        'group' => 'webconfig',
    ),
);
