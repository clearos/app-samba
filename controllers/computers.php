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

    /**
     * View view.
     *
     * @return view
     */

    function view()
    {
        $this->_common('view');
    }

    /**
     * Common settings handler.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _common($form_type)
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');
        $this->lang->load('samba');
        $this->load->library('samba/Samba');
        $this->load->library('samba/Nmbd');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('netbios', 'samba/Samba', 'validate_netbios_name', TRUE);
        $this->form_validation->set_policy('comment', 'samba/Samba', 'validate_server_string', TRUE);
        //$this->form_validation->set_policy('printing', 'samba/Samba', 'validate_server_string', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->samba->set_netbios_name($this->input->post('netbios'));
                $this->samba->set_server_string($this->input->post('comment'));

                $this->page->set_status_updated();
                redirect('/samba/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['netbios'] = $this->samba->get_netbios_name();
            $data['comment'] = $this->samba->get_server_string();
            $data['homes'] = $this->samba->get_homes_state();
            $data['wins_support'] = $this->samba->get_wins_support();
            $data['wins_server'] = $this->samba->get_wins_server();

            $data['show_printing'] = clearos_app_installed('print_server') ? TRUE : FALSE;
            $data['printing'] = $this->samba->get_printing_mode();
            $data['printing_options'] = $this->samba->get_printing_modes();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/settings', $data, lang('base_settings'));
    }
}
