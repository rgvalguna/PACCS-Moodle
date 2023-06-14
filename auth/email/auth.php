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
 * Authentication Plugin: Email Authentication
 *
 * @author Martin Dougiamas
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth_email
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/local/papacs_telegram/TelegramBotPHP/telegram.php');
define('TELEGRAM_TOKEN', '6163000440:AAEKIj0yxnPOkSV-dlOg_Uol977ULAmS1AQ');//PAPACS Bot Token
define('CHAT_ID', '-703429874');//PAPACS Customer Support Group ID


/**
 * Email authentication plugin.
 */
class auth_plugin_email extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'email';
        $this->config = get_config('auth_email');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_email() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    function can_signup() {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        // Standard signup, without custom confirmatinurl.
        return $this->user_signup_with_confirmation($user, $notify);
    }

    //custom user military naming
    /**
     * MIlitary and Civilian Name Conversion
     */
    public function military_name($user, $rank, $afpos, $branchofsrvc, $middlename){
        $EP = ['Pvt','PFC','Cpl','Sgt','SSg','TSg','MSg','SMS','CMS'];
        $officer = ['2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL', 'BGEN', 'MGEN', 'LTGEN', 'GEN'];
        $fullname = $rank." ".ucwords(strtolower($user->firstname))." ".ucwords(strtolower($middlename))." ".ucwords(strtolower($user->lastname));
       // $fullname = $rank." ".$user->firstname." ".$user->middlename." ".$user->lastname." ".$afpos." ".$branchofsrvc;
       if (in_array($rank, $EP)) {
        if($rank == 'Pvt'){
            if($afpos=='INF'||$afpos=='(INF)'){
                $afpos='(Inf)';
            }
        }
        $fullname = $rank." ".ucwords(strtolower($user->firstname))." ".ucwords(strtolower($middlename))." ".ucwords(strtolower($user->lastname))." ".$afpos." ".$branchofsrvc;
       }
       if (in_array($rank, $officer)) {
        $fullname =  strtoupper($rank." ".$user->firstname." ".$middlename." ".$user->lastname." ".$afpos." ".$branchofsrvc);
       }
       return $fullname;
    }

    /**
     * Helper function that returns the text.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    public function get_papacs_military_fullname(\stdClass $user, bool $preview, int $field_id) : string {
        global $CFG, $DB;

        // The user field to display.
        // $field = $this->get_data();
        $field = $field_id;
        // The value to display - we always want to show a value here so it can be repositioned.
        if ($preview) {
            $value = $field;
        } else {
            $value = '';
        }
        if (is_number($field)) { // Must be a custom user profile field.
            if ($field = $DB->get_record('user_info_field', array('id' => $field))) {
                // Found the field name, let's update the value to display.
                $value = $field->name;
                $file = $CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php';
                if (file_exists($file)) {
                    require_once($CFG->dirroot . '/user/profile/lib.php');
                    require_once($file);
                    $class = "profile_field_{$field->datatype}";
                    $field = new $class($field->id, $user->id);
                    $value = $field->display_data();
                }
            }
        } else if (!empty($user->$field)) { // Field in the user table.
            $value = $user->$field;
        }

        $context = \mod_customcert\element_helper::get_context( $field_id);
        return format_string($value, true, ['context' => $context]);
    }

    /**
     * Sign up a new user ready for confirmation.
     *
     * Password is passed in plaintext.
     * A custom confirmationurl could be used.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     * @param string $confirmationurl user confirmation URL
     * @return boolean true if everything well ok and $notify is set to true
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    public function user_signup_with_confirmation($user, $notify=true, $confirmationurl = null) {
        global $CFG, $DB, $SESSION;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }

        $user->id = user_create_user($user, false, false);

        user_add_password_history($user->id, $plainpassword);

        // Save any custom profile field information.
        profile_save_data($user);

        // Save wantsurl against user's profile, so we can return them there upon confirmation.
        if (!empty($SESSION->wantsurl)) {
            set_user_preference('auth_email_wantsurl', $SESSION->wantsurl, $user);
        }

        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        //PAPACS NAMES:
        $afpos = $this->get_papacs_military_fullname($user, true, 27);
        $branchofservc = $this->get_papacs_military_fullname($user, true, 26);
        $rank = $this->get_papacs_military_fullname($user, true, 24);
        $unit = $this->get_papacs_military_fullname($user, true, 25);
        $middlename = $this->get_papacs_military_fullname($user, true, 28);
        $fullname = $this->military_name($user, $rank, $afpos, $branchofservc, $middlename);

        //PAPACS Notification if User has Registered
        $telegram = new Telegram(TELEGRAM_TOKEN);
        $message = "
Notification Message 📩:
A new user is awaiting for Admin confirmation?
Please confirm immediately!
Details: 
Username: {$user->username}
Name: {$fullname}
Email: {$user->email}
Unit: {$unit}
Note: This is a auto generated notifications from PAPACS Administrator.Thank you.🫡
        ";
        $userMessage = "
<h3>Notification Message 📩:</h3>\n
<p>Thank you for Registering at <b>Philippine Army Preliminary Assessment in Cybersecurity System (PAPACS)</b>.
Please go to your email account to activate and confirm your registration.If you can't find the email kindly see through your Spam Emails.
There are 300 maximum users that can be activated using email activation per day and by this instance kindly notify the administrator for the confirmation if no email confirmation arrived. 
Maraming Salamat Po 🫡🫡🫡.</p>\n";
        $content = ['chat_id' => CHAT_ID, 'text' => $message];
        $telegram->sendMessage($content);

        if (! send_confirmation_email($user, $confirmationurl)) {
            // print_error('auth_emailnoemail', 'auth_email');
        }

        if ($notify) {
            global $CFG, $PAGE, $OUTPUT;
            $emailconfirm = get_string('emailconfirm');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($PAGE->course->fullname);
            echo $OUTPUT->header();
            // notice(get_string('emailconfirmsent', '', $user->email), "$CFG->wwwroot/index.php");
            notice($userMessage, "$CFG->wwwroot/index.php");
        } else {
            return true;
        }
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    function user_confirm($username, $confirmsecret) {
        global $DB, $SESSION;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->auth != $this->authtype) {
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret === $confirmsecret && $user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->secret === $confirmsecret) {   // They have provided the secret key to get in
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));

                if ($wantsurl = get_user_preferences('auth_email_wantsurl', false, $user)) {
                    // Ensure user gets returned to page they were trying to access before signing up.
                    $SESSION->wantsurl = $wantsurl;
                    unset_user_preference('auth_email_wantsurl', $user);
                }
                
                //custom autoenrol for PAPACS
                $plugin = enrol_get_plugin('manual');
                $targetcourseid = 2;//PAPACS EXAM Course ID
                $papacsPolicies = 4;
                $papacsTraining = 7;
                $roleid = 5; //student
                //enroll PAPACS EXAM
                $enrolperson = $DB -> get_record('enrol',array('courseid'=>$targetcourseid,'enrol'=>'manual'));
                $plugin->enrol_user($enrolperson,$user->id, $roleid,$targetcourseid);
                //enroll PAPACS Training Materials
                $enroltraining = $DB -> get_record('enrol',array('courseid'=>$papacsTraining,'enrol'=>'manual'));
                $plugin->enrol_user($enroltraining, $user->id, $roleid, $papacsTraining);
                //enroll PAPACS Policies
                $enrolpolicies = $DB -> get_record('enrol',array('courseid'=>$papacsPolicies,'enrol'=>'manual'));
                $plugin->enrol_user($enrolpolicies, $user->id, $roleid, $papacsPolicies);

                //PAPACS NAMES:
                $afpos = $this->get_papacs_military_fullname($user, true, 27);
                $branchofservc = $this->get_papacs_military_fullname($user, true, 26);
                $rank = $this->get_papacs_military_fullname($user, true, 24);
                $unit = $this->get_papacs_military_fullname($user, true, 25);
                $middlename = $this->get_papacs_military_fullname($user, true, 28);
                $fullname = $this->military_name($user, $rank, $afpos, $branchofservc, $middlename);
                //PAPACS Notification if User has Registered
                $telegram = new Telegram(TELEGRAM_TOKEN);
                $message = "
Notification Message 📩:
A new user has already been confirmed.
Details: 
    Username: {$user->username}
    Name: {$fullname}
    Email: {$user->email}
    Unit: {$unit}
Note: This is a auto generated notifications from PAPACS Administrator.Thank you.🫡
                ";
                $content = ['chat_id' => CHAT_ID, 'text' => $message];
                $telegram->sendMessage($content);
                return AUTH_CONFIRM_OK;
            }
        } else {
            return AUTH_CONFIRM_ERROR;
        }
    }

    function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set() {
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled.
     * @return bool
     */
    function is_captcha_enabled() {
        return get_config("auth_{$this->authtype}", 'recaptcha');
    }

}
