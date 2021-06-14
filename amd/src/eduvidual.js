define(
    ['jquery', 'core/ajax', 'core/notification'],
    function($, AJAX, NOTIFICATION) {
    return {
        debug: true,
        setFeature: function(uniqid, orgid, field) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_webuntis/eduvidual:setFeature(uniqid, orgid, field)', uniqid, orgid, field);

            var a = $('#feature-' + uniqid + '-' + orgid + '-' + field);
            var trigger = $(a).find('i.fa');
            $(trigger).css('filter', 'blur(4px)');

            var setto = ($(trigger).hasClass('fa-toggle-on')) ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_webuntis_orgmap',
                args: { 'orgid': orgid, 'field': field, 'status': setto },
                done: function(result) {
                    $(trigger).css('filter', 'unset');
                    if (MAIN.debug) console.log('=> Result for ' + uniqid + '-' + orgid, result);
                    if (typeof result.orgid !== 'undefined' && result.orgid == orgid && typeof result.status !== 'undefined') {
                        if (MAIN.debug) console.log('===> status', result.status);
                        if (result.status == 1) {
                            $(trigger).removeClass('fa-toggle-off').addClass('fa-toggle-on');
                        } else {
                            $(trigger).removeClass('fa-toggle-on').addClass('fa-toggle-off');
                        }
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});
