<?php

/**
 * Samba smb and nmb daemon controller.
 *
 * @category   apps
 * @package    samba
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2013 ClearFoundation
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

use \clearos\apps\base\Daemon as Daemon_Class;

require clearos_app_base('base') . '/controllers/daemon.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba smb and nmb daemon controller.
 *
 * @category   apps
 * @package    samba
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Server extends Daemon
{
    /**
     * Samba server constructor.
     *
     * @return view
     */

    function __construct()
    {
        parent::__construct('nmb', 'samba');
    }

    /**
     * Default controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');

        $data['daemon_name'] = 'samba';
        $data['app_name'] = 'samba';

        // Load views
        //-----------

        $options['javascript'] = array(clearos_app_htdocs('base') . '/daemon.js.php');

        $this->page->view_form('base/daemon', $data, lang('base_server_status'), $options);
    }

    /**
     * Status.
     *
     * @return view
     */

    function status()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        $this->load->library('samba_common/Nmbd');
        $this->load->library('samba_common/Smbd');

        $nmbd_running = $this->nmbd->get_running_state();
        $smbd_running = $this->smbd->get_running_state();

        $status['status'] = ($smbd_running && $nmbd_running) ? Daemon_Class::STATUS_RUNNING : Daemon_Class::STATUS_STOPPED;

        echo json_encode($status);
    }

    /**
     * Start.
     *
     * @return view
     */

    function start()
    {
        $this->load->library('samba_common/Nmbd');
        $this->load->library('samba_common/Smbd');

        try {
            $this->nmbd->set_running_state(TRUE);
            $this->smbd->set_running_state(TRUE);
            $this->nmbd->set_boot_state(TRUE);
            $this->smbd->set_boot_state(TRUE);
        } catch (Exception $e) {
            // Keep going
        }
    }

    /**
     * Stop.
     *
     * @return view
     */

    function stop()
    {
        $this->load->library('samba_common/Nmbd');
        $this->load->library('samba_common/Smbd');

        try {
            $this->smbd->set_running_state(FALSE);
            $this->nmbd->set_running_state(FALSE);
            $this->smbd->set_boot_state(FALSE);
            $this->nmbd->set_boot_state(FALSE);
        } catch (Exception $e) {
            // Keep going
        }
    }
}
