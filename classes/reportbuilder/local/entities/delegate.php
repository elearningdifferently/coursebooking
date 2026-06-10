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

declare(strict_types=1);

namespace local_coursebooking\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{boolean_select, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};

defined('MOODLE_INTERNAL') || die();

/**
 * Delegate entity for report builder.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delegate extends base {

    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['local_coursebooking_deleg'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_delegate', 'local_coursebooking');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }
        foreach ($this->get_all_filters() as $filter) {
            $this->add_filter($filter)->add_condition($filter);
        }
        return $this;
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $alias = $this->get_table_alias('local_coursebooking_deleg');

        $columns = [];

        $columns[] = (new column(
            'firstname',
            new lang_string('delegate_firstname', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.firstname")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'lastname',
            new lang_string('delegate_lastname', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.lastname")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'email',
            new lang_string('delegate_email', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.email")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'isleader',
            new lang_string('delegate_isleader', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$alias}.isleader")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column(
            'newaccount',
            new lang_string('delegate_newaccount', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$alias}.newaccount")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'boolean_as_text']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $alias = $this->get_table_alias('local_coursebooking_deleg');

        $filters = [];

        $filters[] = (new filter(
            text::class,
            'email',
            new lang_string('delegate_email', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.email"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'isleader',
            new lang_string('delegate_isleader', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.isleader"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'newaccount',
            new lang_string('delegate_newaccount', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.newaccount"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
