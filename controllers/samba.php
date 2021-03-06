<?php

/**
 * Samba controller.
 *
 * @category   apps
 * @package    samba
 * @subpackage controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba controller.
 *
 * @category   apps
 * @package    samba
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Samba extends ClearOS_Controller
{
    /**
     * Samba server summary view.
     *
     * @return view
     */

    function index()
    {
        $this->lang->load('samba');

        // Show warning if Samba 4 is installed
        //-------------------------------------

        if (file_exists('/usr/clearos/apps/samba_directory/deploy/info.php')) {
            $this->page->view_form('samba/samba4', $data, lang('samba_app_name'));
            return;
        }

        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget('samba');
            return;
        }

        // Load libraries
        //---------------

        $this->load->library('samba_common/Samba');

        // Load view data
        //---------------

        try {
            $is_initialized = $this->samba->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($is_initialized) {
            $views = array('samba/server', 'samba/settings', 'samba/mode', 'samba/administrator', 'samba/computers');

            $this->page->view_forms($views, lang('samba_app_name'));
        } else {
            redirect('/samba/initialization');
        }
    }
}
