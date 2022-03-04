// This file is part of Exabis Student Review
//
// (c) 2020 GTN - Global Training Network GmbH <office@gtn-solutions.com>
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
(function($) {

    $(function() {
        // button "calculate"
        $(document).on('click', '#id_calculate', function(e) {
            // all factor inputs
            var factorInputs = $('input[name^="factors\["]');
            var factorsSum = 0;
            var subjectResSum = 0;
            factorInputs.each(function (i, el) {
                var factorVal = parseInt($(el).val());
                if (isNaN(factorVal) || factorVal < 0) {
                    factorVal = 0;
                    $(el).val(0);
                }
                if (factorVal > 9) {
                    factorVal = 9;
                    $(el).val(9);
                }
                factorsSum = factorsSum + parseFloat(factorVal);
                var subjectUid = $(el).closest('td').attr('data-subjectId');
                var subjectGrade = parseFloat($('#val_for_calculate_' + subjectUid).attr('data-subject-gradeval'));
                var subjectRes = subjectGrade * factorVal;
                subjectResSum = subjectResSum + subjectRes;
            })
            if (factorsSum > 0) {
                // var averageVal = Math.round(subjectResSum / factorsSum * 10) / 10;
                var averageVal = Math.floor(subjectResSum / factorsSum * 10) / 10;
            } else {
                var averageVal = 0;
            }
            $('#factor_summ').text(factorsSum);
            $('#subject_summ').text(subjectResSum);
            var roundedAvgVal = Math.round(averageVal);
            averageVal = averageVal.toString().replace(".", ",");
            if (typeof gradeNames !== 'undefined'){
                if (Object.values(gradeNames).length > 7) { // (6 + empty) like 1, 1 minus, 1-2, ....
                    var usedGradeNames = {};
                    Object.values(gradeNames).forEach(function (item, index) {
                        itemVal = parseInt(item);
                        if (!isNaN(itemVal)) {
                            usedGradeNames[itemVal] = itemVal;
                        } else {
                            usedGradeNames[0] = '';
                        }
                    });

                } else {
                    var usedGradeNames = gradeNames;
                }
                if (roundedAvgVal in usedGradeNames && usedGradeNames[roundedAvgVal] != '') {
                    $('#average_value').text(usedGradeNames[roundedAvgVal] + ' (' + averageVal + ')');
                }
            } else {
                $('#average_value').text(averageVal);
            }
        });

    });



})(block_exastud.jquery);
