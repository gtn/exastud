<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

namespace block_exastud;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../inc.php';

use block_exastud\globals as g;

class global_config {
	static function get_grade_options() {
		$values = [
			'1', '1-', '1-2',
			'2+', '2', '2-', '2-3',
			'3+', '3', '3-', '3-4',
			'4+', '4', '4-', '4-5',
			'5+', '5', '5-', '5-6',
			'6+', '6',
		];

		return array_combine($values, $values);
	}

	static function get_niveau_options() {
		return ['G' => 'G', 'M' => 'M', 'E' => 'E', 'zdu' => 'zieldifferenter Unterricht'];
	}

	static function get_niveau_option_title($id) {
		return @static::get_niveau_options()[$id];
	}
}
