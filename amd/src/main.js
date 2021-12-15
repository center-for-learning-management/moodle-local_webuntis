define(
    ['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/str'],
    function($, AJAX, Notification, ModalFactory, str) {
    return {
        debug: true,
        sync_queue_create: [],
        sync_queue_purge: [],
        sync_queue_roles: [],

        selectTarget: function(uniqid, courseid) {
            var MAIN = this;
            if (MAIN.debug) {
                console.log('local_webuntis/main:selectTarget(uniqid, courseid)', uniqid, courseid);
            }

            var trigger = $('#trigger_' + uniqid + '_' + courseid + ' i');
            $(trigger).css('filter', 'blur(4px)');

            var setto = ($(trigger).hasClass('fa-toggle-on')) ? 0 : 1;
            if (MAIN.debug) {
                console.log('setto', setto);
            }
            AJAX.call([{
                methodname: 'local_webuntis_selecttarget',
                args: { 'courseid': courseid, 'status': setto },
                done: function(result) {
                    $(trigger).css('filter', 'unset');
                    if (MAIN.debug) {
                        console.log('=> Result for ' + uniqid + '-' + courseid, result);
                    }
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid && typeof result.status !== 'undefined') {
                        if (MAIN.debug) {
                            console.log('===> status', result.status);
                        }
                        if (result.status == 1) {
                            $(trigger).removeClass('fa-toggle-off').addClass('fa-toggle-on');
                        } else {
                            $(trigger).removeClass('fa-toggle-on').addClass('fa-toggle-off');
                        }
                    }
                    if (typeof result.canproceed !== 'undefined') {
                        if (MAIN.debug) {
                            console.log('===> canproceed', result.canproceed);
                        }
                        if (result.canproceed == 1) {
                            $('#proceed-' + uniqid).removeClass('disabled');
                        } else {
                            $('#proceed-' + uniqid).addClass('disabled');
                        }
                    }
                },
                fail: Notification.exception
            }]);
        },
        setAutoCreate: function(uniqid) {
            var MAIN = this;
            if (MAIN.debug) {
                console.log('local_webuntis/main:setAutoCreate(uniqid)', uniqid);
            }

            var a = $('#autocreate-' + uniqid);
            var trigger = $(a).find('i.fa');
            $(trigger).css('filter', 'blur(4px)');

            var setto = ($(trigger).hasClass('fa-toggle-on')) ? 0 : 1;
            if (MAIN.debug) {
                console.log('setto', setto);
            }
            AJAX.call([{
                methodname: 'local_webuntis_autocreate',
                args: { 'status': setto },
                done: function(result) {
                    $(trigger).css('filter', 'unset');
                    if (MAIN.debug) {
                        console.log('=> Result for ' + uniqid, result);
                    }
                    if (typeof result.status !== 'undefined') {
                        if (MAIN.debug) {
                            console.log('===> status', result.status);
                        }
                        if (result.status == 1) {
                            $(trigger).removeClass('fa-toggle-off').addClass('fa-toggle-on');
                        } else {
                            $(trigger).removeClass('fa-toggle-on').addClass('fa-toggle-off');
                        }
                    }
                },
                fail: Notification.exception
            }]);
        },
        tenantData: function(tenant_id, sender) {
            var MAIN = this;
            if (MAIN.debug) {
                console.log('local_webuntis/main:tenantData(tenant_id, sender)', tenant_id, sender);
            }
            //var tr = $(sender).closest('tr');
            //var tenant_id = $(tr).attr('data-tenant_id');
            var field = $(sender).attr('data-field');
            var value = $(sender).val();

            $(sender).css('filter', 'blur(4px)');

            var data = { 'tenant_id': tenant_id, 'field': field, 'value': value };
            if (MAIN.debug) {
                console.log('Sending', data);
            }
            AJAX.call([{
                methodname: 'local_webuntis_tenantdata',
                args: data,
                done: function(result) {
                    $(sender).css('filter', 'unset');
                    if (MAIN.debug) {
                        console.log('=> Result', result);
                    }

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
                fail: Notification.exception
            }]);
        },
        /**
         * Create user accounts.
         * @param uniqid of the controls.
         * @param item only execute particular item, if empty, execute all.
         */
        usersync_create: function(uniqid, item) {
            if (this.debug) console.log('local_webuntis/main::usersync_create(uniqid, item)', uniqid, item);
            var MAIN = this;
            if (typeof(item) === 'undefined') {
                $('.' + uniqid + ' .m_doit:checked').each(function() {
                    MAIN.sync_queue_create.push({ 'uniqid': uniqid, 'item': this});
                });
                if (MAIN.sync_queue_create.length > 0) {
                    var queueitem = MAIN.sync_queue_create.shift();
                    MAIN.usersync_create(queueitem.uniqid, queueitem.item);
                }
            } else {
                $(item).css('filter', 'blur(2px)');
                var tr = $(item).closest('tr');
                var remoteuserid = tr.attr('data-remoteuserid');
                AJAX.call([{
                    methodname: 'local_webuntis_usersync_create',
                    args: { 'remoteuserid': remoteuserid },
                    done: function(result) {
                        $(item).css('filter', 'unset');
                        if (MAIN.debug) {
                            console.log('=> Result for ' + uniqid + '-' + remoteuserid, result);
                        }
                        if (result.userid > 0) {
                            var span = $("<span style=\"color: green;\">").html(result.message);
                            $(item).parent().empty().append(span);
                        }
                        if (MAIN.sync_queue_create.length > 0) {
                            var queueitem = MAIN.sync_queue_create.shift();
                            MAIN.usersync_create(queueitem.uniqid, queueitem.item);
                        }
                    },
                    fail: Notification.exception
                }]);
            }
        },
        /**
         * Purge user accounts.
         * @param uniqid of the controls.
         * @param item only execute particular item, if empty, execute all.
         */
        usersync_purge: function(uniqid, item) {
            if (this.debug) console.log('local_webuntis/main::usersync_purge(uniqid, item)', uniqid, item);
            var MAIN = this;
            if (typeof(item) === 'undefined') {
                str.get_strings([
                    {'key' : 'admin:usersync:userpurge:confirm:title', 'component': 'local_webuntis'},
                    {'key' : 'admin:usersync:userpurge:confirm:text', 'component': 'local_webuntis'},
                    {'key' : 'proceed'},
                    {'key' : 'cancel'},
                ]).done(function(s) {
                    Notification.confirm(s[0], s[1], s[2], s[3], function() {
                        $('.' + uniqid + ' .m_doit:checked').each(function() {
                            MAIN.sync_queue_purge.push({ 'uniqid': uniqid, 'item': this});
                        });
                        if (MAIN.sync_queue_purge.length > 0) {
                            var queueitem = MAIN.sync_queue_purge.shift();
                            MAIN.usersync_purge(queueitem.uniqid, queueitem.item);
                        }
                    });
                }).fail(Notification.exception);
            } else {
                $(item).css('filter', 'blur(2px)');
                var tr = $(item).closest('tr');
                var userid = tr.attr('data-userid');
                var orgid = tr.attr('data-orgid');
                AJAX.call([{
                    methodname: 'local_webuntis_usersync_purge',
                    args: { 'userid': userid, 'orgid': orgid },
                    done: function(result) {
                        $(item).css('filter', 'unset');
                        if (MAIN.debug) {
                            console.log('=> Result for ' + uniqid + '-' + userid, result);
                        }
                        if (result.status > 0) {
                            var span = $("<span style=\"color: green;\">").html(result.message);
                            $(item).parent().empty().append(span);
                        }
                        if (MAIN.sync_queue_purge.length > 0) {
                            var queueitem = MAIN.sync_queue_purge.shift();
                            MAIN.usersync_purge(queueitem.uniqid, queueitem.item);
                        }
                    },
                    fail: Notification.exception
                }]);
            }
        },
        /**
         * Set roles of user accounts.
         * @param uniqid of the controls.
         * @param item only execute particular item, if empty, execute all.
         */
        usersync_roles: function(uniqid, item) {
            if (this.debug) console.log('local_webuntis/main::usersync_roles(uniqid, item)', uniqid, item);
            var MAIN = this;
            if (typeof(item) === 'undefined') {
                str.get_strings([
                    {'key' : 'admin:usersync:userroles:confirm:title', 'component': 'local_webuntis'},
                    {'key' : 'admin:usersync:userroles:confirm:text', 'component': 'local_webuntis'},
                    {'key' : 'proceed'},
                    {'key' : 'cancel'},
                ]).done(function(s) {
                    Notification.confirm(s[0], s[1], s[2], s[3], function() {
                        $('.' + uniqid + ' .m_doit:checked').each(function() {
                            MAIN.sync_queue_roles.push({ 'uniqid': uniqid, 'item': this});
                        });
                        if (MAIN.sync_queue_roles.length > 0) {
                            var queueitem = MAIN.sync_queue_roles.shift();
                            MAIN.usersync_roles(queueitem.uniqid, queueitem.item);
                        }
                    });
                }).fail(Notification.exception);
            } else {
                $(item).css('filter', 'blur(2px)');
                var tr = $(item).closest('tr');
                var userid = tr.attr('data-userid');
                var orgid = tr.attr('data-orgid');
                var role = tr.find('.w_role').html().replace('Administrator', 'Manager');

                AJAX.call([{
                    methodname: 'local_webuntis_usersync_roles',
                    args: { 'userid': userid, 'orgid': orgid, 'role': role },
                    done: function(result) {
                        $(item).css('filter', 'unset');
                        if (MAIN.debug) {
                            console.log('=> Result for ' + uniqid + '-' + userid, result);
                        }
                        if (typeof(result.role) !== 'undefined') {
                            $(tr).find('.sync-btn').empty();
                            $(tr).find('.m_role').css('color', 'green').html(result.role);
                        }
                        if (MAIN.sync_queue_roles.length > 0) {
                            var queueitem = MAIN.sync_queue_roles.shift();
                            MAIN.usersync_roles(queueitem.uniqid, queueitem.item);
                        }
                    },
                    fail: Notification.exception
                }]);
            }
        },
    };
});
