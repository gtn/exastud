
/*
(function($) {
    $(function () {
        $('.exastud-hide-all').on('change', function () {
            // http://localhost/Moodle32Dakora/blocks/exastud/review_class.php?courseid=2&classid=3&subjectid=35&action=hide_student&studentid=4
            var table = $(this).closest('table');
            var students = [];
            table.find('[data-studentid]').each(function () {
                students.push($(this).attr('data-studentid'));
            });
            // console.log(students);
        });
    });
});
*/