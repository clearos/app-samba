<?php

/**
 * Samba javascript helper.
 *
 * @category   apps
 * @package    samba
 * @subpackage javascript
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
clearos_load_language('samba');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    // Translations
    //-------------

    lang_samba_initializing = '<?php echo lang("samba_initializing_warning"); ?>';
    lang_initializing = '<?php echo lang("samba_initializing_core_system"); ?>';
    lang_connecting = '<?php echo lang("base_connecting..."); ?>';
    lang_blocked_slave = '<?php echo lang("samba_master_node_needs_initialization"); ?>';

    // Mode field handling
    //--------------------

    $('#profiles_field').hide();
    $('#logon_drive_field').hide();
    $('#logon_script_field').hide();
    $("#configuration").hide();
    $("#initialization").hide();

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
            url: '/app/samba/initialization/run',
            success: function(payload) {
            },
            error: function() {
            }
        });

        getInitializationStatus();
    } else if ($("#init_validated").val() == 0) {
        getInitializationStatus();
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

function getInitializationStatus() {
    $.ajax({
        url: '/app/samba/initialization/get_status',
        type: 'GET',
        dataType: 'json',
        success : function(payload) {
            window.setTimeout(getInitializationStatus, 2000);
            showInitializationStatus(payload);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getInitializationStatus, 2000);
        }
    });
}

function runDirectoryInitialization() {
    $.ajax({
        url: '/app/samba/initialization/run_openldap_initialization',
        type: 'GET',
        dataType: 'json',
        success : function(payload) {
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
        }
    });
}

function showInitializationStatus(payload) {
    if (payload.status == 'samba_initialized') {
        $("#configuration").hide();
        $("#initialization").hide();
        window.location.href = '/app/samba';
    } else if (payload.status == 'samba_initializing') {
        $("#initialization_result").html('<div class="theme-loading-normal">' + lang_samba_initializing + '</div>');
        $("#configuration").hide();
        $("#initialization").show();
    } else if (payload.status == 'initializing') {
        $("#initialization_result").html('<div class="theme-loading-normal">' + lang_initializing + '</div>');
        $("#configuration").hide();
        $("#initialization").show();
    } else if (payload.status == 'initialized') {
        if ($("#init_validated").val() == 0) {
            $("#initialization_result").html('');
            $("#configuration").show();
            $("#initialization").hide();
        } else {
            $("#initialization_result").html('<div class="theme-loading-normal">' + lang_connecting + '</div>');
            $("#configuration").hide();
            $("#initialization").show();
        }
    } else if (payload.status == 'blocked_slave') {
        $("#initialization_result").html('<div class="theme-loading-normal">' + lang_blocked_slave + '</div>');
        $("#configuration").hide();
        $("#initialization").show();
    } else if (payload.status == 'uninitialized') {
        $("#initialization_result").html('<div class="theme-loading-normal">' + lang_connecting + '</div>');
        $("#configuration").hide();
        $("#initialization").show();
        runDirectoryInitialization(); 
    }
}

// vim: syntax=javascript
