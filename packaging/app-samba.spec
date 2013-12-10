
Name: app-samba
Epoch: 1
Version: 1.5.18
Release: 1%{dist}
Summary: Windows Networking
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups
Requires: app-users
Requires: app-network
Requires: samba >= 3.6.1

%description
Windows Networking provides the necessary glue to interoperate with Windows systems.  The app provides authentication services, file and print, along with Windows domain management.

%package core
Summary: Windows Networking - Core
License: LGPLv3
Group: ClearOS/Libraries
Provides: system-windows-driver
Requires: app-base-core
Requires: app-accounts-core
Requires: app-groups-core
Requires: app-users-core >= 1:1.1.1
Requires: app-network-core
Requires: app-openldap-directory-core
Requires: app-samba-extension-core >= 1:1.4.11
Requires: app-samba-common-core >= 1:1.4.70
Requires: csplugin-filewatch
Requires: libtalloc
Requires: samba-common >= 3.6.1
Requires: samba-client >= 3.6.1
Requires: samba-winbind >= 3.6.1
Requires: samba-winbind-clients >= 3.6.1
Requires: system-mode-driver
Requires: tdb-tools >= 1.2.9

%description core
Windows Networking provides the necessary glue to interoperate with Windows systems.  The app provides authentication services, file and print, along with Windows domain management.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/samba
cp -r * %{buildroot}/usr/clearos/apps/samba/

install -d -m 0755 %{buildroot}/var/clearos/samba
install -d -m 0755 %{buildroot}/var/clearos/samba/backup
install -d -m 0775 %{buildroot}/var/clearos/samba/lock
install -D -m 0755 packaging/add-samba-directories %{buildroot}/usr/sbin/add-samba-directories
install -D -m 0755 packaging/add-windows-group-info %{buildroot}/usr/sbin/add-windows-group-info
install -D -m 0755 packaging/app-samba-initialize %{buildroot}/usr/sbin/app-samba-initialize
install -D -m 0755 packaging/app-samba-openldap-initialize %{buildroot}/usr/sbin/app-samba-openldap-initialize
install -D -m 0644 packaging/filewatch-samba-configuration.conf %{buildroot}/etc/clearsync.d/filewatch-samba-configuration.conf
install -D -m 0644 packaging/filewatch-samba-directory.conf %{buildroot}/etc/clearsync.d/filewatch-samba-directory.conf
install -D -m 0644 packaging/filewatch-samba-printing.conf %{buildroot}/etc/clearsync.d/filewatch-samba-printing.conf
install -D -m 0644 packaging/nmb.php %{buildroot}/var/clearos/base/daemon/nmb.php
install -D -m 0755 packaging/samba-add-machine %{buildroot}/usr/sbin/samba-add-machine
install -D -m 0644 packaging/smb.ldap.conf %{buildroot}/var/clearos/ldap/synchronize/smb.ldap.conf
install -D -m 0644 packaging/smb.php %{buildroot}/var/clearos/base/daemon/smb.php
install -D -m 0644 packaging/smb.winbind.conf %{buildroot}/var/clearos/ldap/synchronize/smb.winbind.conf
install -D -m 0644 packaging/winbind.php %{buildroot}/var/clearos/base/daemon/winbind.php

%post
logger -p local6.notice -t installer 'app-samba - installing'

%post core
logger -p local6.notice -t installer 'app-samba-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/samba/deploy/install ] && /usr/clearos/apps/samba/deploy/install
fi

[ -x /usr/clearos/apps/samba/deploy/upgrade ] && /usr/clearos/apps/samba/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-samba - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-samba-core - uninstalling'
    [ -x /usr/clearos/apps/samba/deploy/uninstall ] && /usr/clearos/apps/samba/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/samba/controllers
/usr/clearos/apps/samba/htdocs
/usr/clearos/apps/samba/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/samba/packaging
%dir /usr/clearos/apps/samba
%dir /var/clearos/samba
%dir /var/clearos/samba/backup
%dir %attr(0775,root,webconfig) /var/clearos/samba/lock
/usr/clearos/apps/samba/deploy
/usr/clearos/apps/samba/language
/usr/clearos/apps/samba/libraries
/usr/sbin/add-samba-directories
/usr/sbin/add-windows-group-info
/usr/sbin/app-samba-initialize
/usr/sbin/app-samba-openldap-initialize
/etc/clearsync.d/filewatch-samba-configuration.conf
/etc/clearsync.d/filewatch-samba-directory.conf
/etc/clearsync.d/filewatch-samba-printing.conf
/var/clearos/base/daemon/nmb.php
/usr/sbin/samba-add-machine
/var/clearos/ldap/synchronize/smb.ldap.conf
/var/clearos/base/daemon/smb.php
/var/clearos/ldap/synchronize/smb.winbind.conf
/var/clearos/base/daemon/winbind.php
