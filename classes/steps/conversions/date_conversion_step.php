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
 * Date conversion step class.
 *
 * @package    tool_trigger
 * @author     Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_trigger\steps\conversions;

defined('MOODLE_INTERNAL') || die;

/**
 * Date conversion step class.
 *
 * @package    tool_trigger
 * @author     Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date_conversion_step extends base_conversion_step {
    use \tool_trigger\helper\datafield_manager;

    /**
     * The first field for comparison. May contain datafield placeholders.
     *
     * @var string
     */
    protected $field1;

    /**
     * The date format to transform to.
     *
     * @var string
     */
    protected $format;

    /**
     * Default format or custom?
     *
     * @var string
     */
    protected $usecustomformat;

    /**
     * The date format to transform to.
     *
     * @var string
     */
    protected $customformat;

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::init()
     */
    protected function init() {
        $this->field1 = $this->data['field1'];
        // Looks odd, but hideIf was in place so not all fields are present as per default.
        // Also, this tool is MISSING form validation.
        $this->usecustomformat = $this->data['usecustomformat'];
        if ($this->usecustomformat) {
            $this->customformat = $this->data['customformat'];
            $this->format = null;
        } else {
            $this->format = $this->data['format'];
            $this->customformat = null;
        }
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        // Due to a lack of validation callbacks...
        if ($this->usecustomformat) {
            if (empty($this->customformat)) {
                throw new \coding_exception('Expecting custom format for conversion but it\'s missing');
            }
            $format = $this->customformat;
        } else {
            if (empty($this->format)) {
                throw new \coding_exception('Expecting date format for conversion but it\'s missing');
            }
            $format = get_string($this->format, 'langconfig');
        }
        $callback = function ($v) use ($format) {
            return userdate($v, $format);
        };

        $stepresults[$this->field1] = $callback($stepresults[$this->field1]);
        $this->update_datafields($event, $stepresults);
        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_convert_date_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_convert_date_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::add_extra_form_fields()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'field1', get_string('datefield', 'tool_trigger'), ['placeholder' => 'course_startdate']);
        $mform->setType('field1', PARAM_RAW);
        $mform->addRule('field1', null, 'required');

        // TODO: language based NAMES instead of format because those are language based.
        // We wish to display a named format option.
        $xoptions = [
            'strftimedate',
            'strftimedatefullshort',
            'strftimedateshort',
            'strftimedatetime',
            'strftimedatetimeshort',
            'strftimedaydate',
            'strftimedaydatetime',
            'strftimedayshort',
            'strftimedaytime',
            'strftimemonthyear',
            'strftimerecent',
            'strftimerecentfull',
            'strftimetime',
            'strftimetime12',
            'strftimetime24',
        ];
        $options = [];
        foreach ($xoptions as $option) {
            $options[$option] = $option;
        }

        $mform->addElement('advcheckbox', 'usecustomformat', get_string('usecustomformat', 'tool_trigger'));

        $fields = [];
        $fields[] = $mform->createElement('select', 'format', get_string('dateformat', 'tool_trigger'), $options);

        // Note we DO NOT USE a placeholder because it's confusing in this "hideIf" flow.
        $fields[] = $mform->createElement('text', 'customformat', get_string('dateformat', 'tool_trigger'), ['placeholder' => '']);
        $mform->setType('customformat', PARAM_RAW);

        $mform->addGroup($fields, 'formatoptions', get_string('dateformat', 'tool_trigger'), ' ', false);

        $mform->hideIf('format', 'usecustomformat', 'eq', '1');
        $mform->hideIf('customformat', 'usecustomformat', 'eq', '0');
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return false;
    }

}