<?php

/**
 * Samba computers view.
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

$this->lang->load('samba');
$this->lang->load('samba_common');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('samba_common_computer')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($computers as $name => $details) {
    // Remove trailing $
    $name = preg_replace('/\$$/', '', $name);

    $default_action = '/app/samba/computers/delete/' . $name;

    $item['title'] = $name;
    $item['action'] = $default_action;

    $item['anchors'] = button_set(
        array(anchor_delete($default_action))
    );

    $item['details'] = array(
        $name,
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('samba_common_computers'),
    $anchors,
    $headers,
    $items
);
