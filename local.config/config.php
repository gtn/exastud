<?php

return [
	'can_edit_bps_and_subjects' => true,

	'always_check_default_values' => false,

	'default_bps' => [
		'bp2004' => (object)[
			'title' => 'Bp 2004',
			'subjects' =>
				block_exastud_str_to_csv('
					title	shorttitle	always_print
					Alevitische Religionslehre	RALE	0
					Altkatholische Religionslehre	RAK	0
					Ethik	ETH	0
					Evangelische Religionslehre	REV	0
					Islamische Religionslehre sunnitischer Prägung	RISL	0
					Jüdische Religionslehre	RJUED	0
					Katholische Religionslehre	RRK	0
					Orthodoxe Religionslehre	ROR	0
					Syrisch-Orthodoxe Religionslehre	RSYR	0
					Deutsch		1
					Mathematik		1
					Englisch		1
					Erdkunde, Wirtschaftskunde, Gemeinschaftskunde	EWG	1
					Naturwissenschaftliches Arbeiten	NWA	1
					Geschichte		1
					Bildende Kunst		1
					Musik		1
					Sport		1
					Französisch		0
					Technik		0
					Mensch und Umwelt	Mum	0
					Bildende Kunst		0
					NwT		0
					Spanisch		0
					Wahlpflichtfach		1
					Profilfach		1
				', "\t", true),
		],
	],

	'default_categories' =>
		preg_split('!\s*\n\s*!', trim('
			Kommunikationsfähigkeit
			Kritikfähigkeit
			Teamfähigkeit
			Planungsfähigkeit
			Problemlösefähigkeit
			Präsentationsfähigkeit
			Durchhaltevermögen
			Selbstständigkeit
			Ordentlichkeit
			Verantwortungsfähigkeit
		')),

	'default_evalopt' =>
		preg_split('!\s*\n\s*!', trim('
			Stufe 1 - ungenügend
			Stufe 2
			Stufe 3
			Stufe 4
			Stufe 5
			Stufe 6 - sehr gut
		')),
];
