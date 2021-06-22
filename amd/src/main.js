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
        setAutoCreate: function(uniqid) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_webuntis/main:setAutoCreate(uniqid)', uniqid);

            var a = $('#autocreate-' + uniqid);
            var trigger = $(a).find('i.fa');
            $(trigger).css('filter', 'blur(4px)');

            var setto = ($(trigger).hasClass('fa-toggle-on')) ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_webuntis_autocreate',
                args: { 'status': setto },
                done: function(result) {
                    $(trigger).css('filter', 'unset');
                    if (MAIN.debug) console.log('=> Result for ' + uniqid, result);
                    if (typeof result.status !== 'undefined') {
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
        tenantData: function(tenant_id, sender) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_webuntis/main:tenantData(tenant_id, sender)', tenant_id, sender);
            //var tr = $(sender).closest('tr');
            //var tenant_id = $(tr).attr('data-tenant_id');
            var field = $(sender).attr('data-field');
            var value = $(sender).val();

            $(sender).css('filter', 'blur(4px)');

            var data = { 'tenant_id': tenant_id, 'field': field, 'value': value };
            if (MAIN.debug) console.log('Sending', data);
            AJAX.call([{
                methodname: 'local_webuntis_tenantdata',
                args: data,
                done: function(result) {
                    $(sender).css('filter', 'unset');
                    if (MAIN.debug) console.log('=> Result', result);

                    if (result.status != 1) {
                        $(sender).addClass('alert-danger');
                    } else {
                        $(sender).addClass('alert-success');
                        setTimeout(function() {
                            $(sender).removeClass('alert-success');
                        }, 1000);
                        $(sender).removeClass('alert-danger');
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        }
    };
});
