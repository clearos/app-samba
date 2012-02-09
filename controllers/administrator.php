<?php

/**
 * Samba administrator controller.
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
 * Samba administrator controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Administrator extends ClearOS_Controller
{
    /**
     * Samba administrator view.
     *
     * @return view
     */

    function index()
    {
        $this->view();
    }

    /**
     * Initialization view.
     *
     * @return view
     */

    function edit()
    {
        $this->_form('edit');
    }
     
    /**
     * Initialization view.
     *
     * @return view
     */

    function view()
    {
        $this->_form('view');
    }

    /**
     * Common form view.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _form($form_type)
    {
        // Load libraries
        //---------------

        $this->lang->load('samba');
        $this->load->library('samba/Samba');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('password', 'samba/Samba', 'validate_password', TRUE);
        $this->form_validation->set_policy('verify', 'samba/Samba', 'validate_password', TRUE);

        $form_ok = $this->form_validation->run();

        // Extra validation
        //-----------------

        if ($form_ok) {
            if ($this->input->post('password') != $this->input->post('verify')) {
                $this->form_validation->set_error('verify', lang('base_password_and_verify_do_not_match'));
                $form_ok = FALSE;
            }
        }

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->samba->set_administrator_password($this->input->post('password'));

                $this->page->set_status_updated();
                redirect('/samba/administrator');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['administrator'] = $this->samba->get_administrator_account();
            $data['mode'] = $this->samba->get_mode();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Bail in AD or BDC mode!
        if (($data['mode'] === Samba::MODE_AD_CONNECTOR) || ($data['mode'] == Samba::MODE_BDC))
            return;

        // Load views
        //-----------

        $this->page->view_form('samba/administrator', $data, lang('samba_app_name'));
    }
}
