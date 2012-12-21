<?php

/**
 * Samba initialization controller.
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

class Initialization extends ClearOS_Controller
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
        $this->load->library('samba_common/Samba');
        $this->load->library('samba/OpenLDAP_Driver');

        // Load view data
        //---------------

        try {
            $is_samba_initialized = $this->samba->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($is_samba_initialized) 
            redirect('/samba');
        else
            $this->edit();
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
        $this->load->library('samba_common/Samba');
        $this->load->factory('mode/Mode_Factory');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('netbios', 'samba_common/Samba', 'validate_netbios_name', TRUE);
        $this->form_validation->set_policy('domain', 'samba_common/Samba', 'validate_workgroup', TRUE);
        $this->form_validation->set_policy('password', 'samba_common/Samba', 'validate_password', TRUE);

        if ($this->mode->get_mode() !== Mode_Engine::MODE_SLAVE)
            $this->form_validation->set_policy('verify', 'samba_common/Samba', 'validate_password', TRUE);

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
            else
                $data['mode'] = 'notslave';
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/initialize', $data, lang('samba_app_name'));
    }

    /**
     * Returns initialization status.
     *
     * @return JSON
     */

    function get_status()
    {
        // Load libraries
        //---------------

        $this->load->library('samba/OpenLDAP_Driver');

        // Handle form submit
        //-------------------

        try {
            $data['status'] = $this->openldap_driver->get_status();
            $data['code'] = 0;
        } catch (Exception $e) {
            $data['code'] = clearos_exception_code($e);
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * Initializes Samba directory.
     *
     * @return JSON
     */

    function run_openldap_initialization()
    {
        // Load libraries
        //---------------

        $this->load->library('samba/OpenLDAP_Driver');

        // Run initialization
        //-------------------

        $this->openldap_driver->run_initialize();
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
        $this->load->library('samba_common/Samba');
        $this->load->library('samba/OpenLDAP_Driver');
        $this->load->factory('mode/Mode_Factory');

        // Handle form submit
        //-------------------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');

        try {
            $mode = $this->mode->get_mode();

            if ($mode === Mode_Engine::MODE_SLAVE) {
                $this->openldap_driver->initialize_samba_as_slave(
                    $this->input->post('netbios'),
                    $this->input->post('password')
                );
            } else {
                $this->openldap_driver->run_initialize_samba_as_master_or_standalone(
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
}
