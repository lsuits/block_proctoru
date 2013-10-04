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
 * recent_activity block rendrer
 *
 * @package    block_proctoru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_proctoru_renderer extends plugin_renderer_base {
    
    public function getUnregisteredMessage(){
        $output = get_string('not_registered', 'block_proctoru');
        return $output;
    }
    
    public function getStatusReportTable($sort="", $page=1){
        global $DB;
        
        echo $this->header();
        $perPage   = 20;
        $totalRows = $DB->count_records('user');
        
        $table = new flexible_table('pr0ct0rur3po4t');
        $table->sortable(true, 'lastname', SORT_ASC);
//        $table->no_sorting('role');
        $table->pageable(true);
        $table->pagesize($perPage,$totalRows);
        $table->define_baseurl('/blocks/proctoru/report.php');
        $table->set_attribute('class', 'admintable generaltable');
        $table->initialbars(true);
        
        $table->define_headers(
                array(
                    'picture',
                    'firstname',
                    'lastname',
                    'role',
                    'idnumber',
                    'status'
                ));
        $table->define_columns(array(
                    'picture',
                    'firstname',
                    'lastname',
                    'role',
                    'idnumber',
                    'status'
                ));
        
        $table->setup();
        list($where, $params) = $table->get_sql_where();
        
        $userfields = explode(',',user_picture::fields('', array('idnumber')));
        $limit  = $table->get_page_size();
        $offset = $table->get_page_start();
        
        $exempt = ProctorU::arrFetchNonExemptUserids();
        
        $data = ProctorU::arrFetchRegisteredStatusByUserid(
                $exempt,
                null,
                $userfields,
                $table->get_sql_sort(), 
                $limit,
                $offset);
        
        foreach(array_values($data) as $d){
            
            
            $userpic = new user_picture($d);
            
            $row = array(
                    $this->render($userpic),
                    $d->firstname,
                    $d->lastname,
                    $d->role,
                    $d->idnumber,
                    $d->status,
                    );
            
            $table->add_data($row);
        }
        
        

        $table->print_html();
        echo $this->footer();
    }
}

?>