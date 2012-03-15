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

use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \Exception as Exception;

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

class Initialize extends ClearOS_Controller
{
    /**
     * Samba server summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('samba');
        $this->load->library('samba/Samba');

        // Load view data
        //---------------

        try {
            // In some circumstances, Samba can auto-initialize.  Give it a try.
            $this->samba->initialize();

            $is_local_initialized = $this->samba->is_local_system_initialized();
            $is_initializing = $this->samba->is_initializing();
            $is_initialized = $this->samba->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($is_local_initialized) {
            redirect('/samba');
        } else if ($is_initializing) {
            $this->page->view_form('samba/initializing', $data, lang('samba_app_name'));
        } else {
            $this->edit();
        }
    }

    /**
     * Initializes Samba.
     *
     * @return JSON
     */

    function run()
    {
        // Load libraries
        //---------------

        $this->lang->load('samba');
        $this->load->library('samba/Samba');
        $this->load->factory('mode/Mode_Factory');

        // Handle form submit
        //-------------------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');

        try {
            $mode = $this->mode->get_mode();

            if ($mode === Mode_Engine::MODE_SLAVE) {
                $this->samba->initialize_local_slave(
                    $this->input->post('netbios'),
                    $this->input->post('password')
                );
            } else {
                $this->samba->initialize_local_master_or_standalone(
                    $this->input->post('netbios'),
                    $this->input->post('domain'),
                    $this->input->post('password')
                );
            }

            $status['code'] = 0;

            echo json_encode($status);
        } catch (Exception $e) {
            echo json_encode(array('code' => clearos_exception_code($e), 'error_message' => clearos_exception_message($e)));
        }
    }

    /**
     * Initialization view.
     *
     * @return view
     */
     
    function edit()
    {
        // Load libraries
        //---------------

        $this->lang->load('samba');
        $this->load->library('samba/Samba');
        $this->load->factory('mode/Mode_Factory');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('netbios', 'samba/Samba', 'validate_netbios_name', TRUE);
        $this->form_validation->set_policy('domain', 'samba/Samba', 'validate_workgroup', TRUE);
        $this->form_validation->set_policy('password', 'samba/Samba', 'validate_password', TRUE);

        if ($this->mode->get_mode() !== Mode_Engine::MODE_SLAVE)
            $this->form_validation->set_policy('verify', 'samba/Samba', 'validate_password', TRUE);

        $form_ok = $this->form_validation->run();

        // Extra validation
        //-----------------

        if ($form_ok && ($this->mode->get_mode() !== Mode_Engine::MODE_SLAVE)) {
            if ($this->input->post('password') != $this->input->post('verify')) {
                $this->form_validation->set_error('verify', lang('base_password_and_verify_do_not_match'));
                $form_ok = FALSE;
            }
        }

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok))
            $data['validated'] = TRUE;

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['domain'] = $this->samba->get_workgroup();
            $data['netbios'] = $this->samba->get_netbios_name();
            $data['administrator'] = $this->samba->get_administrator_account();

            if ($this->mode->get_mode() === Mode_Engine::MODE_SLAVE)
                $data['mode'] = 'slave';
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/initialize', $data, lang('samba_app_name'));
    }
}
