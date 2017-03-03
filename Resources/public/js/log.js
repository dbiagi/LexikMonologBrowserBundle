$(function () {
    $('.datepicker').datepicker({
        format:         'dd/mm/yyyy',
        autoclose:      true,
        todayHighlight: true,
        locale: window.log_browser_options.locale,
    });
});
