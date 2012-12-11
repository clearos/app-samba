<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'samba';
$app['version'] = '1.4.7';
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
);

$app['core_requires'] = array(
    'app-accounts-core',
    'app-groups-core',
    'app-users-core >= 1:1.1.1',
    'app-network-core', 
    'app-openldap-directory-core', 
    'app-samba-extension-core',
    'app-samba-common-core >= 1:1.4.8',
    'csplugin-filewatch',
    'samba-client >= 3.6.1',
    'samba-winbind >= 3.6.1',
    'system-mode-driver',
    'tdb-tools >= 1.2.9'
);

$app['core_file_manifest'] = array( 
    'filewatch-samba-configuration.conf'=> array('target' => '/etc/clearsync.d/filewatch-samba-configuration.conf'),
    'filewatch-samba-directory.conf'=> array('target' => '/etc/clearsync.d/filewatch-samba-directory.conf'),
    'filewatch-samba-network.conf'=> array('target' => '/etc/clearsync.d/filewatch-samba-network.conf'),
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
    'samba-init' => array(
        'target' => '/usr/sbin/samba-init',
        'mode' => '0755',
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
