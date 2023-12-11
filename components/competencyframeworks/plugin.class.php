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
 * Configurable Reports a Moodle block for creating customizable reports
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * COMPETENCY FRAMEWORK FILTER
 * A filter for configurable reports
 *
 * @author François Parlant <https://www.linkedin.com/in/francois-parlant/>
 * @date   : 2020
 */

/* example of report query
***********
* Display the courses in which the competencies of a framework are used
***********
SELECT  ccc.id, ccc.courseid, ccc.competencyid, cf.shortname as 'referentiel', cf.idnumber as 'framework Id', c.fullname as 'cours', comp.shortname
FROM  prefix_competency_coursecomp ccc
INNER JOIN prefix_course AS c ON c.id = ccc.courseid
INNER JOIN prefix_competency AS comp ON comp.id = ccc.competencyid
INNER JOIN prefix_competency_framework AS cf ON cf.id = comp.competencyframeworkid
JOIN prefix_course_categories cc ON c.category = cc.id
WHERE 1=1
%%FILTER_COMPETENCYFRAMEWORKS:cf.id%%
%%FILTER_SUBCATEGORIES:cc.path%%
%%FILTER_STARTTIME:c.startdate:>=%% %%FILTER_ENDTIME:c.startdate:<=%%

*/
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');

/**
 * Class plugin_competencyframeworks
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_competencyframeworks extends plugin_base {

    public function init(): void {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercompetencyframeworks', 'block_configurable_reports');
        $this->reporttypes = ['courses', 'sql'];
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return get_string('filtercompetencyframeworks_summary', 'block_configurable_reports');
    }

    /**
     * execute
     *
     * @param $finalelements
     * @param $data
     * @return array|string|string[]
     */
    public function execute($finalelements, $data) {

        $filtercompetencyframeworks = optional_param('filter_competencyframeworks', 0, PARAM_INT);
        if (!$filtercompetencyframeworks) {
            return $finalelements;
        }

        if ($this->report->type !== 'sql') {
            return [$filtercompetencyframeworks];
        }

        if (preg_match("/%%FILTER_COMPETENCYFRAMEWORKS:([^%]+)%%/i", $finalelements, $output)) {
            $replace = ' AND ' . $output[1] . ' = ' . $filtercompetencyframeworks;

            return str_replace('%%FILTER_COMPETENCYFRAMEWORKS:' . $output[1] . '%%', $replace, $finalelements);
        }

        return $finalelements;
    }

    /**
     * Print filter
     *
     * @param MoodleQuickForm $mform
     * @param bool|object $formdata
     * @return void
     */
    public function print_filter(MoodleQuickForm $mform, $formdata = false): void {

        global $remotedb;

        $reportclassname = 'report_' . $this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type !== 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $competencyframeworkslist = $reportclass->elements_by_conditions($conditions);
        } else {
            $sql = 'SELECT  cf.id, cf.shortname
                      FROM {competency_framework} cf
                      ';
            $studentlist = $remotedb->get_records_sql($sql);
            foreach ($studentlist as $student) {
                $competencyframeworkslist[] = $student->userid;
            }

        }

        $competencyframeworksoptions = [];
        $competencyframeworksoptions[0] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($competencyframeworkslist)) {

            $competencyframeworks = $remotedb->get_records_sql($sql);

            foreach ($competencyframeworks as $c) {
                $competencyframeworksoptions[$c->id] = $c->shortname;
            }
        }

        $elestr = get_string('competencyframeworks', 'block_configurable_reports');
        $mform->addElement('select', 'filter_competencyframeworks', $elestr, $competencyframeworksoptions);
        $mform->setType('filter_competencyframeworks', PARAM_INT);
    }

}
