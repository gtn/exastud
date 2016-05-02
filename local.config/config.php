<?php

return [
	'always_check_default_values' => true,

	'default_subjects' =>
		preg_split('!\s*\n\s*!', trim('
			Alevitische Religionslehre (RALE)
			Altkatholische Religionslehre (RAK)
			Ehtik (ETH)
			Evangelische Religionslehre (REV)
			Islamische Religionslehre sunnitischer Prägung (RISL)
			Jüdische Religionslehre (RJUED)
			Katholische Religionslehre (RRK)
			Orthodoxe Religionslehre (ROR)
			Syrisch-Orthodoxe Religionslehre (RSYR)
			Deutsch
			Mathematik
			Englisch
			EWG (Erdkunde, Wirtschaftskunde, Gemeinschaftskunde)
			NWA (Naturwissenschaftliches Arbeiten)
			Geschichte
			Bildende Kunst
			Musik
			Sport
			Französisch
			Technik
			Mensch und Umwelt (Mum)
			Bildende Kunst
			Musik
			NwT
			Sport
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
