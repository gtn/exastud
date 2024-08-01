# exastud: Exabis Student Reviews (learning development reports)

exastud is a free Moodle plugin designed to empower educators to create detailed periodical reviews of their students including assessment of soft skills. With the help of this module teachers/trainers can team-assess their students using defined assessment scales. Exabis student review can also be used for the assessment of soft skills, where teachers from different Moodle courses can contribute to one report being generated.

The module can be used standalone or as part of the Exabis suite (together with ePortfolio and Exabis Competence Grids). 
Advantage of using all three modules together is that learner driven learning paths can be established – guided by trainers, self-paced, competency-based and personalized.


# INSTALLATION

This block is for Moodle 3.11 to 4.3 versions, it will not work for versions below 2021051700

Download the plugin from Moodle Plug-ins Repository. Please follow the instructions available in the Moodle Plug-ins Repository.

Download directly from Github:

Save the zip file somewhere onto your local computer and extract all the files
Transfer the folder “exastud” to the blocks-directory of Moodle
Log in as 'administrator' and click on the 'Home' link
You can now adjust setting documentation in the administration panel. 


Define periods:
Exabis student review assesses students period-based. This means that the administrator has to define the period of assessment. After defining a new period, all former assessments are moved to the archived period.


Assign head-teachers of a class: 
Here the head teacher is defined. This person is in charge of all assessments and can print them out in report card format for students.


At this point teachers and students can use the plugin in their course
For more information on setting up the plug-in please refer to the documentation.

If there are problems with Admin Backup of exastud, please check in your php.ini if the extension pdo_mysql is activated.

# USAGE

Verbal assessment of the students within a defined time-period
Students can be assessed by teachers or teams of teachers by pre-defined criteria (scales) as well as verbally.

Creating different roles for evaluators related to the role in the organisation. Assigning reports to different roles
In EXAstud administrator can assign three roles to teachers: class teacher (global group), subject teacher and responsible teacher.

Creating individual reports and fields with the report-assistant
The modules main focus is to assess students periodically verbalized and based upon certain criterias and generate report documenting a formative development in certain skills. 
To use the tool as intended a pre-configuration has to be done by the administrator.

Connecting to competences
Various assessment categories can be added directly via this module. 
If the module is used together with Exabis competence grids, competences from these grids can also be added as assessment categories.


Generate different reports for learning development
In EXAstud selecting the “Standard certificate form” in the selection causes the respective template to be used in the certificate export and the display of input fields for assessing teachers that are assigned to the template.

In a contrast to the default setting for the class's certificates, you can define for which students a different certificate, available as a template, should be generated. This means that the required reports can be linked individually to learners. This only makes sense in combination with the “bw_active” setting.

For more information, refer to the documentation.


# LICENCE

Exabis Student Review is a free software: you can redistribute it and/or modify it. It is published under the terms of the GNU General Public License (Free Software Foundation), either version 3 of the License, or any later version. 
This script is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

#HISTORY

Using the pedagogical method of cooperative open learning (COOL) a culture of reflectiveness plays an important part - this module emphasizes on this aspect. It was the first module produced for this method. See more at http://www.cooltrainers.at/index.php?id=150&L=1
In addition to the „verbal“ review, teachers can rate their students on a 10-point-scale regarding teamwork, responsibility and self-reliability. An average point‐rate from all teachers is aggregated and printed out on the student review report card for each individual student.

# DISCLAIMER

As with any customization, it is recommended that you have a good backup of your Moodle site before attempting to install contributed code.
While those contributing code make every effort to provide the best code that they can, using contributed code nevertheless entails a certain degree of risk as contributed code is not as carefully reviewed and/or tested as the Moodle core code.
Hence, use this block at your own risk.

# AUTHOR:
2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
