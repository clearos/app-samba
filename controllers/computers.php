<?php

/**
 * Samba computers controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\samba\Samba as Samba;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba computers controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Computers extends ClearOS_Controller
{
    /**
     * Samba computers controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('samba');
        $this->load->library('samba/Computer');

        // Load view data
        //---------------

        // Bail in AD mode!
        try {
            $data['mode'] = $this->samba->get_mode();
            if ($data['mode'] === Samba::MODE_AD_CONNECTOR)
                return;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        try {
            $data['computers'] = $this->computer->get_computers();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/computers', $data, lang('samba_computers'));
    }

    /**
     * Delete view.
     *
     * @param string $computer computer
     *
     * @return view
     */

    function delete($computer)
    {
        $confirm_uri = '/app/samba/computers/destroy/' . $computer;
        $cancel_uri = '/app/samba/computers';
        $items = array($computer);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

   /**
     * Destroys computer.
     *
     * @param string $computer computer
     *
     * @return view
     */

    function destroy($computer)
    {
        // Load libraries
        //---------------

        $this->load->library('samba/Computer');

        // Handle delete
        //--------------

        try {
            $this->computer->delete_computer($computer);

            $this->page->set_status_deleted();
            redirect('/samba/computers');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
