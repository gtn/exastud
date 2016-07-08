<?php

return [
	'always_check_default_values' => false,

	'default_subjects' =>
		preg_split('!\s*\n\s*!', trim('
			Alevitische Religionslehre
			Altkatholische Religionslehre
			Ethik
			Evangelische Religionslehre
			Islamische Religionslehre sunnitischer Prägung
			Jüdische Religionslehre
			Katholische Religionslehre
			Orthodoxe Religionslehre
			Syrisch-Orthodoxe Religionslehre
			Deutsch
			Mathematik
			Englisch
			Erdkunde, Wirtschaftskunde, Gemeinschaftskunde
			Naturwissenschaftliches Arbeiten
			Geschichte
			Bildende Kunst
			Musik
			Sport
			Französisch
			Technik
			Mensch und Umwelt
			Bildende Kunst
			NwT
			Spanisch
		')),

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
