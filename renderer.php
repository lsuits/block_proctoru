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
 * Renderer for block proctoru
 *
 * @package    block_proctoru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once $CFG->libdir.'/tablelib.php';
require_once 'lib.php';
/**
 * proctoru verification status block rendrer
 *
 * @package    block_proctoru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_proctoru_renderer extends plugin_renderer_base {
    
    public function getUnregisteredMessage(){
        $output = get_string('not_registered', 'block_proctoru');
        return $output;
    }
    
    public function render_registration_report(registration_report $report) {
        $this->page->requires->yui_module(
            'moodle-block_proctoru-regreport', 
            'M.block_proctoru.regreport.init',
            array(array_values($report->data))
            ); 
        
        $out  = $this->output->heading("ProctorU Registration Status");
        $out .= html_writer::tag('div', '', array("id"=>"report"));
        return $this->output->container($out);
    }
}

class registration_report implements renderable {

    public $data;

    public function __construct(){
        $this->data = ProctorU::partial_get_users_listing(ProctorU::UNREGISTERED);
    }
}

?>