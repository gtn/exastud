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

$capabilities = array(

		'block/exastud:use' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'legacy' => array(
						'user' => CAP_ALLOW
				)
		),
		'block/exastud:admin' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'legacy' => array(
						'manager' => CAP_ALLOW
				)
		),
		/*
		'block/exastud:editperiods' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'legacy' => array(
						'manager' => CAP_ALLOW
				)
		),

		'block/exastud:uploadpicture' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'legacy' => array(
						'manager' => CAP_ALLOW
				)
		),
		*/
		'block/exastud:myaddinstance' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
				'user' => CAP_ALLOW
			),
			'clonepermissionsfrom' => 'moodle/my:manageblocks'
		),
		'block/exastud:addinstance' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_BLOCK,
			'archetypes' => array(
				'editingteacher' => CAP_ALLOW,
				'manager' => CAP_ALLOW
			),
			'clonepermissionsfrom' => 'moodle/site:manageblocks'
		),
);
