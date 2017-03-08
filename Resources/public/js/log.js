$(function () {
    $('.datepicker').datepicker({
        format:         window.log_browser_options.date_format,
        autoclose:      true,
        todayHighlight: true,
        locale: window.log_browser_options.locale,
    });
});
