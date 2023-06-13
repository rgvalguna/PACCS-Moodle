<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * This file contains the customcert element studentname's core interaction API.
 *
 * @package    customcertelement_studentname
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_studentname;

/**
 * The customcert element studentname's core interaction API.
 *
 * @package    customcertelement_studentname
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        // \mod_customcert\element_helper::render_content($pdf, $this, fullname($user));
        $EP = ['Pvt','PFC','Cpl','Sgt','SSg','TSg','MSg','SMS','CMS'];
        $officer = ['2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL', 'BGEN', 'MGEN', 'LTGEN', 'GEN'];
        $rank = $this->get_papacs_military_fullname($user, $preview, 24);
        $afpos = $this->get_papacs_military_fullname($user, $preview, 27);
        $branchofsrvc = $this->get_papacs_military_fullname($user, $preview, 26);
        $middlename = $this->get_papacs_military_fullname($user, $preview, 28);
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
        \mod_customcert\element_helper::render_content($pdf, $this, $fullname);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        global $USER;

        return \mod_customcert\element_helper::render_html_content($this, fullname($USER));
    }

     /**
     * Helper function that returns the text.
     *
     * @param \stdClass $user the user we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_papacs_military_fullname(\stdClass $user, bool $preview, int $field_id) : string {
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

        $context = \mod_customcert\element_helper::get_context($this->get_id());
        return format_string($value, true, ['context' => $context]);
    }
}
