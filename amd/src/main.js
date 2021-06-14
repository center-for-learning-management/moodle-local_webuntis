define(
    ['jquery', 'core/ajax', 'core/notification'],
    function($, AJAX, NOTIFICATION) {
    return {
        debug: true,
        selectTarget: function(uniqid, courseid) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_webuntis/main:selectTarget(uniqid, courseid)', uniqid, courseid);

            var trigger = $('#trigger_' + uniqid + '_' + courseid + ' i');
            $(trigger).css('filter', 'blur(4px)');

            var setto = ($(trigger).hasClass('fa-toggle-on')) ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_webuntis_selecttarget',
                args: { 'courseid': courseid, 'status': setto },
                done: function(result) {
                    $(trigger).css('filter', 'unset');
                    if (MAIN.debug) console.log('=> Result for ' + uniqid + '-' + courseid, result);
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid && typeof result.status !== 'undefined') {
                        if (MAIN.debug) console.log('===> status', result.status);
                        if (result.status == 1) {
                            $(trigger).removeClass('fa-toggle-off').addClass('fa-toggle-on');
                        } else {
                            $(trigger).removeClass('fa-toggle-on').addClass('fa-toggle-off');
                        }
                    }
                    if (typeof result.canproceed !== 'undefined') {
                        if (MAIN.debug) console.log('===> canproceed', result.canproceed);
                        if (result.canproceed == 1) {
                            $('#proceed-' + uniqid).removeClass('disabled');
                        } else {
                            $('#proceed-' + uniqid).addClass('disabled');
                        }
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});
