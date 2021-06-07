define(
    ['jquery', 'core/ajax', 'core/notification'],
    function($, AJAX, NOTIFICATION) {
    return {
        debug: false,
        selectTarget: function(uniqid, courseid) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_webuntis/main:selectTarget(uniqid, courseid)', uniqid, courseid);

            var status = $('#local_webuntis_selecttarget_' + uniqid + ' tr.course-' + courseid + ' a i').hasClass('fa-toggle-on');
            if (status) {
                $('#local_webuntis_selecttarget_' + uniqid + ' tr.course-' + courseid + ' a i').hasClass('fa-toggle-off');
            } else {
                $('#local_webuntis_selecttarget_' + uniqid + ' tr.course-' + courseid + ' a i').hasClass('fa-toggle-on');
            }

            var setto = (status) ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_webuntis_selecttarget',
                args: { 'courseid': courseid, 'status': setto },
                done: function(result) {
                    if (MAIN.debug) console.log('=> Result for ' + uniqid + '-' + courseid, result);
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid && typeof result.status !== 'undefined') {
                        if (result.status) {
                            $('#local_webuntis_selecttarget_' + uniqid + ' tr.course-' + courseid + ' a i').hasClass('fa-toggle-off');
                        } else {
                            $('#local_webuntis_selecttarget_' + uniqid + ' tr.course-' + courseid + ' a i').hasClass('fa-toggle-on');
                        }
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});
