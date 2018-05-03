<?php

$force_bw_active = false;

$bw_active = get_config('exastud', 'bw_active');

return [
	'bw_active' => $force_bw_active || $bw_active,

	'can_edit_bps_and_subjects' => true,

	'always_check_default_values' => false,

	'default_bps' => !$bw_active ? [] : [
		'bp2016' => [
			'sourceinfo' => 'bw-bp2016',
			'title' => 'Bp 2016',
			'subjects' =>
				block_exastud_str_to_csv('
sourceinfo;title;shorttitle;always_print
bw-bp2016-rale;Alevitische Religionslehre;RALE;0
bw-bp2016-rak;Altkatholische Religionslehre;RAK;0
bw-bp2016-eth;Ethik;ETH;0
bw-bp2016-rev;Evangelische Religionslehre;REV;0
bw-bp2016-risl;Islamische Religionslehre sunnitischer Prägung;RISL;0
bw-bp2016-rjued;Jüdische Religionslehre;RJUED;0
bw-bp2016-rrk;Katholische Religionslehre;RRK;0
bw-bp2016-ror;Orthodoxe Religionslehre;ROR;0
bw-bp2016-rsyr;Syrisch-Orthodoxe Religionslehre;RSYR;0
bw-bp2016-d;Deutsch;D;1
bw-bp2016-e;Englisch;E;1
bw-bp2016-m;Mathematik;M;1
bw-bp2016-g;Geschichte;G;1
bw-bp2016-geo;Geographie;Geo;1
bw-bp2016-gk;Gemeinschaftskunde;Gk;1
bw-bp2016-wbs;Wirtschaft / Berufs- und Studienorientierung;WBS;1
bw-bp2016-bnt;Biologie, Naturphänomene und Technik;BNT;1
bw-bp2016-ph;Physik;Ph;1
bw-bp2016-ch;Chemie;Ch;1
bw-bp2016-bio;Biologie;Bio;1
bw-bp2016-mu;Musik;Mu;1
bw-bp2016-b;Bildende Kunst;BK;1
bw-bp2016-sp;Sport;Sp;1
bw-bp2016-wpf-aes;Wahlpflichtfach Alltagskultur, Ernährung, Soziales;WPF AES;0
bw-bp2016-wpf-f;Wahlpflichtfach Französisch;WPF F;0
bw-bp2016-wpf-te;Wahlpflichtfach Technik;WPF Te;0
bw-bp2016-profil-bk;Profilfach Bildende Kunst;Profil BK;0
bw-bp2016-profil-f;Profilfach Französisch;Profil F;0
bw-bp2016-profil-mu;Profilfach Musik;Profil Mu;0
bw-bp2016-profil-nwt;Profilfach Naturwissenschaft und Technik;Profil NwT;0
bw-bp2016-profil-s;Profilfach Spanisch;Profil S;0
bw-bp2016-profil-sp;Profilfach Sport;Profil Sp;0
				', ";", true),
		],
		'bp2004' => [
			'sourceinfo' => 'bw-bp2004',
			'title' => 'Bp 2004',
			'subjects' =>
				block_exastud_str_to_csv('
sourceinfo;title;shorttitle;always_print
bw-bp2004-rale;Alevitische Religionslehre;RALE;0
bw-bp2004-rak;Altkatholische Religionslehre;RAK;0
bw-bp2004-eth;Ethik;ETH;0
bw-bp2004-rev;Evangelische Religionslehre;REV;0
bw-bp2004-risl;Islamische Religionslehre sunnitischer Prägung;RISL;0
bw-bp2004-rjued;Jüdische Religionslehre;RJUED;0
bw-bp2004-rrk;Katholische Religionslehre;RRK;0
bw-bp2004-ror;Orthodoxe Religionslehre;ROR;0
bw-bp2004-rsyr;Syrisch-Orthodoxe Religionslehre;RSYR;0
bw-bp2004-d;Deutsch;D;1
bw-bp2004-m;Mathematik;M;1
bw-bp2004-e;Englisch;E;1
bw-bp2004-ewg;Erdkunde, Wirtschaftskunde, Gemeinschaftskunde;EWG;1
bw-bp2004-nwa;Naturwissenschaftliches Arbeiten;NWA;1
bw-bp2004-g;Geschichte;G;1
bw-bp2004-bk;Bildende Kunst;BK;1
bw-bp2004-mu;Musik;Mu;1
bw-bp2004-sp;Sport;Sp;1
bw-bp2004-wpf-f;Wahlpflichtfach Französisch;WPF F;0
bw-bp2004-wpf-mum;Wahlpflichtfach Mensch und Umwelt;WPF MuM;0
bw-bp2004-wpf-te;Wahlpflichtfach Technik;WPF Te;0
bw-bp2004-profil-bk;Profilfach Bildende Kunst;Profil BK;0
bw-bp2004-profil-mum;Profilfach Musik;Profil MuM;0
bw-bp2004-profil-nut;Profilfach Naturwissenschaft und Technik;Profil Nut;0
bw-bp2004-profil-s;Profilfach Spanisch;Profil S;0
bw-bp2004-profil-sp;Profilfach Sport;Profil Sp;0
				', ";", true),
		],
	],

	'default_categories' =>
		block_exastud_str_to_csv('
sourceinfo;title
bw-0001;Kommunikationsfähigkeit
bw-0002;Kritikfähigkeit
bw-0003;Teamfähigkeit
bw-0004;Planungsfähigkeit
bw-0005;Problemlösefähigkeit
bw-0006;Präsentationsfähigkeit
bw-0007;Durchhaltevermögen
bw-0008;Selbstständigkeit
bw-0009;Ordentlichkeit
bw-0010;Verantwortungsfähigkeit
		', ";", true),

	'default_evalopt' =>
		block_exastud_str_to_csv('
sourceinfo;title
bw-0001;Stufe 1 - ungenügend
bw-0002;Stufe 2
bw-0003;Stufe 3
bw-0004;Stufe 4
bw-0005;Stufe 5
bw-0006;Stufe 6 - sehr gut
		', ";", true),
];
