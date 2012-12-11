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
$this->lang->load('samba_common');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $buttons = array(
        form_submit_custom('submit', lang('samba_common_change_password')),
        anchor_cancel('/app/samba/administrator')
    );
} else {
    $buttons = array(
        anchor_custom('/app/samba/administrator/edit', lang('samba_common_change_password')),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('samba/administrator/edit');
echo form_header(lang('samba_common_administrator_account'));

echo field_input('administrator', $administrator, lang('samba_common_account_username'), TRUE);

if ($form_type == 'edit')  {
    echo field_password('password', '', lang('base_password'));
    echo field_password('verify', '', lang('base_verify'));
}

echo field_button_set($buttons);

echo form_footer();
echo form_close();
