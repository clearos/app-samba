<?php

/**
 * Samba global settings controller.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\samba_common\Samba as Samba;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba global settings controller.
 *
 * @category   apps
 * @package    samba
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Settings extends ClearOS_Controller
{
    /**
     * Samba global settings controller.
     *
     * @return view
     */

    function index()
    {
        $this->_common('view');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_common('edit');
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
        $this->load->library('samba_common/Samba');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('netbios', 'samba_common/Samba', 'validate_netbios_name', TRUE);
        $this->form_validation->set_policy('comment', 'samba_common/Samba', 'validate_server_string', TRUE);
        $this->form_validation->set_policy('homes', 'samba_common/Samba', 'validate_homes_state', TRUE);
        $this->form_validation->set_policy('wins_support', 'samba_common/Samba', 'validate_wins_support');
        $this->form_validation->set_policy('wins_server', 'samba_common/Samba', 'validate_wins_server');

        if ($this->input->post('printing'))
            $this->form_validation->set_policy('printing', 'samba_common/Samba', 'validate_server_string', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                // FIXME: review setting netbios -- is add/delete computer required?  a rejoin?
                $this->samba->set_netbios_name($this->input->post('netbios'));
                $this->samba->set_server_string($this->input->post('comment'));
                $this->samba->set_printing_mode($this->input->post('printing'));
                $this->samba->set_homes_state($this->input->post('homes'));
                $this->samba->set_wins_server_and_support(
                    $this->input->post('wins_server'),
                    $this->input->post('wins_support')
                );

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
            $data['ad_mode'] = ($this->samba->get_mode() === Samba::MODE_AD_CONNECTOR) ? TRUE : FALSE;
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
