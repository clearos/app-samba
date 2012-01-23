<?php

/**
 * Samba initialize view.
 *
 * @category   ClearOS
 * @package    Samba
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('samba');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'slave') {
    $help = lang('samba_initialize_slave_help');
    $title = lang('samba_initialize_bdc');
    $button_text = lang('samba_join_domain');
    $domain_read_only = TRUE;
} else {
    $help = lang('samba_initialize_master_help');
    $title = lang('base_initialize');
    $button_text = lang('base_initialize');
    $domain_read_only = FALSE;
}


///////////////////////////////////////////////////////////////////////////////
// Status boxes
///////////////////////////////////////////////////////////////////////////////

echo infobox_highlight(lang('base_getting_started'), $help);

echo "<div id='initialization' style='display:none;'>";

echo infobox_highlight(
    lang('base_status'),
    "<div id='initialization_result'></div>"
);

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo "<div id='configuration' style='display:none;'>";
echo "<form><input type='hidden' id='init_validated' value='$validated'></form>";

echo form_open('samba/initialize/edit');
echo form_header($title);

echo fieldset_header(lang('samba_windows_network'));
echo field_input('netbios', $netbios, lang('samba_server_name'));
echo field_input('domain', $domain, lang('samba_windows_domain'), $domain_read_only);
echo fieldset_footer();

echo fieldset_header(lang('samba_administrator_password'));
echo field_input('administrator', $administrator, lang('samba_account_username'), TRUE);
echo field_password('password', '', lang('base_password'));
if ($mode !== 'slave')
    echo field_password('verify', '', lang('base_verify'));
echo fieldset_footer();

echo field_button_set(
    array(form_submit_custom('submit', $button_text))
);

echo form_footer();
echo form_close();

echo "</div>";
