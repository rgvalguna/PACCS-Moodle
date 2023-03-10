<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * REST web service entry point. The authentication is done via tokens.
 *
 * @package    webservice_rest
 * @copyright  2009 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * NO_DEBUG_DISPLAY - disable moodle specific debug messages and any errors in output
 */
define('NO_DEBUG_DISPLAY', true);

define('WS_SERVER', true);

require('../../../config.php');
require_once("$CFG->dirroot/webservice/api/v1/papacs.php");

if (!webservice_protocol_is_enabled('rest')) {
    header("HTTP/1.0 403 Forbidden");
    debugging('The server died because the web services or the REST protocol are not enable',
        DEBUG_DEVELOPER);
    die;
}

// $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/papacs/webservice/rest/server.php';
// $request_uri = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
// $url_components = parse_url($request_uri);
// $result = [];
// $completion = [];
// $final_result = [];
// $json = [];

/**
 * Get User Completion
 */
// if ($_GET['papacs_function'] == 'core_completion_get_course_completion_status'){
//     parse_str($url_components['query'], $params);
//     $json =
//     (new MoodleRest())->setServerAddress($url)->
//     setToken($_GET['papacs_token'])->
//     setReturnFormat(MoodleRest::RETURN_JSON)->completion('core_completion_get_course_completion_status', $params);
// $result = $json;
// }

/**
 * Get User Information with completion status of PAPACS Student
 */
// if ($_GET['papacs_function'] == 'core_user_get_users_by_field'){
//     parse_str($url_components['query'], $params);
//     //Get User Info First
//     $json =
//     (new MoodleRest())->setServerAddress($url)->
//     setToken($_GET['papacs_token'])->
//     setReturnFormat(MoodleRest::RETURN_JSON)->papacs_user('core_user_get_users_by_field', $params);
//     $json = json_decode($json, false);
//     //completion status of papacs student
//     $com_params = [
//         'userid' => $json[0]->id,
//         'courseid' => 2,//PAPACS EXAM CODE
//     ];
//     $completion = 
//     (new MoodleRest())->setServerAddress($url)->
//     setToken($_GET['papacs_token'])->
//     setReturnFormat(MoodleRest::RETURN_JSON)->completion('core_completion_get_course_completion_status',  $com_params);
//     $completion = json_decode($completion, false);
//     $final_result = [
//         'userid' => $json[0]->id,
//         'username' => $json[0]->username,
//         'firstname' => $json[0]->firstname,
//         'lastname' => $json[0]->lastname,
//         'email' => $json[0]->email,
//         'completion_status' => $completion->completionstatus,
//     ];
//     $user = $DB->get_record('user', array('username' => 964155));
//     var_dump($user);
//     $result = json_encode($final_result);
// }

// echo $result;
// die;


//revision of custom API
$token = $_GET['papacs_token'];
$username = $_GET['serial'];
$user = $DB->get_record('user', array('username' => $username));
$request =
    (new MoodleRest())->getUser($user)->
    setToken($token)->
    setReturnFormat(MoodleRest::RETURN_JSON)->papacs_status();
echo json_decode($request);
die;
