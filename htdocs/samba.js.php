<?php

/**
 * Samba javascript helper.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Javascript
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    // Translations
    //-------------

    lang_initializing = '<?php echo lang("base_initializing..."); ?>';

    // Mode field handling
    //--------------------

    $('#profiles_field').hide();
    $('#logon_drive_field').hide();
    $('#logon_script_field').hide();

    changeMode();

    $('#mode').change(function() {
        changeMode();
    });

    changeWins();

    $('#wins_support').change(function() {
        changeWins();
    });

    // Initialization
    //---------------

    if ($("#init_validated").val() == 1) {
        $("#initialization_result").html('<div class="theme-loading-normal">' + lang_initializing + '</div>');

        $("#configuration").hide();
        $("#initialization").show();

        $.ajax({
            type: 'POST',
            dataType: 'json',
            data:
                'ci_csrf_token=' + $.cookie('ci_csrf_token') +
                '&netbios=' + $("#netbios").val() +
                '&domain=' + $("#domain").val() +
                '&password=' + $("#password").val()
            ,
            url: '/app/samba/initialize/run',
            success: function(payload) {
                if (payload.code == 0) {
                    window.location.href = '/app/samba';
                } else {
                    $("#initialization_result").html(payload.error_message);
                    $("#configuration").show();
                }
            },
            error: function() {
            }
        });
    } else {
        $("#configuration").show();
    }
});

function changeMode() {
    current_mode = $('#mode').val();

    if (current_mode == 'pdc') {
        $('#profiles_field').show();
        $('#logon_drive_field').show();
        $('#logon_script_field').show();
    } else {
        $('#profiles_field').hide();
        $('#logon_drive_field').hide();
        $('#logon_script_field').hide();
    }
}

function changeWins() {
    current_wins = $('#wins_support').val();

    if (current_wins == 1) {
        $('#wins_server').attr('disabled', true);
    } else {
        $('#wins_server').attr('disabled', false);
    }
}

// vim: syntax=javascript
