<?php

return [
	'can_edit_bps_and_subjects' => true,

	'always_check_default_values' => false,

	'default_bps' => [
		'bp2004' => (object)[
			'title' => 'Bp 2004',
			'subjects' =>
				block_exastud_str_to_csv('
title;shorttitle;always_print
Alevitische Religionslehre;RALE;0
Altkatholische Religionslehre;RAK;0
Ethik;ETH;0
Evangelische Religionslehre;REV;0
Islamische Religionslehre sunnitischer Prägung;RISL;0
Jüdische Religionslehre;RJUED;0
Katholische Religionslehre;RRK;0
Orthodoxe Religionslehre;ROR;0
Syrisch-Orthodoxe Religionslehre;RSYR;0
Deutsch;D;1
Mathematik;M;1
Englisch;E;1
Erdkunde, Wirtschaftskunde, Gemeinschaftskunde;EWG;1
Naturwissenschaftliches Arbeiten;NWA;1
Geschichte;G;1
Bildende Kunst;BK;1
Musik;Mu;1
Sport;Sp;1
Wahlpflichtfach Französisch;WPF F;0
Wahlpflichtfach Mensch und Umwelt;WPF MuM;0
Wahlpflichtfach Technik;WPF Te;0
Profilfach Bildende Kunst;Profil BK;0
Profilfach Musik;Profil MuM;0
Profilfach Naturwissenschaft und Technik;Profil Nut;0
Profilfach Spanisch;Profil S;0
Profilfach Sport;Profil Sp;0
				', ";", true),
		],
		'bp2016' => (object)[
			'title' => 'Bp 2016',
			'subjects' =>
				block_exastud_str_to_csv('
title;shorttitle;always_print
Alevitische Religionslehre;RALE;0
Altkatholische Religionslehre;RAK;0
Ethik;ETH;0
Evangelische Religionslehre;REV;0
Islamische Religionslehre sunnitischer Prägung;RISL;0
Jüdische Religionslehre;RJUED;0
Katholische Religionslehre;RRK;0
Orthodoxe Religionslehre;ROR;0
Syrisch-Orthodoxe Religionslehre;RSYR;0
Deutsch;D;1
Englisch;E;1
Mathematik;M;1
Geschichte;G;1
Geographie;Geo;1
Gemeinschaftskunde;Gk;1
Wirtschaft / Berufs- und Studienorientierung;WBS;1
Biologie, Naturphänomene und Technik;BNT;1
Physik;Ph;1
Chemie;Ch;1
Biologie;Bio;1
Musik;Mu;1
Bildende Kunst;B;1
Sport;Sp;1
Wahlpflichtfach Alltagskultur, Ernähung, Soziales;WPF AES;0
Wahlpflichtfach Französisch;WPF F;0
Wahlpflichtfach Technik;WPF Te;0
Profilfach Bildende Kunst;Profil BK;0
Profilfach Fanzösisch;Profil F;0
Profilfach Musik;Profil Mu;0
Profilfach Naturwissenschaft und Technik;Profil NwT;0
Profilfach Sport;Profil Sp;0
				', ";", true),
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
