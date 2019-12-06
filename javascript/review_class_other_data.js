
//
(function($) {
    $(function() {

        var c = new cookie('student-fulldata-shown').read();
        console.log(c);

        if (typeof hideDetailInputsToggler !== 'undefined' && hideDetailInputsToggler) {
            $(".exa_table .toggle-all-students").hide();
        }

        $(".exa_table").on('click', '.exastud-collapse-inputs a', function (e) {
            e.preventDefault();
            var blockid = $(this).closest('.exastud-collapse-inputs').attr('data-inputsblock');
            $('.exa_table .input-collapsible[data-inputsBlock=' + blockid + ']').toggle();
        });

        $(".exa_table").on('click', '.toggle-all-students a', function (e) {
            e.preventDefault();
            var status = $(this).closest('.toggle-all-students').attr('data-shown');
            if (status == 1) {
                $('.exa_table .input-collapsible').hide();
                $('.exa_table .exastud-uncollapse').hide();
                $('.exa_table .exastud-collapse').show();
                $(this).closest('.toggle-all-students').attr('data-shown', 0);
                new cookie('student-fulldata-shown', 0, 365, "/").set();
            } else {
                $('.exa_table .input-collapsible').show();
                $('.exa_table .exastud-collapse').hide();
                $('.exa_table .exastud-uncollapse').show();
                $(this).closest('.toggle-all-students').attr('data-shown', 1);
                new cookie('student-fulldata-shown', 1, 365, "/").set();
            }
        });

    })
})(block_exastud.jquery);

