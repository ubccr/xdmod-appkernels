/* global Ext, XDMoD, CCR */
Ext.namespace('XDMoD', 'XDMoD.Module', 'CCR', 'CCR.xdmod', 'CCR.xdmod.ui');

XDMoD.Module.AppKernels.AppKernelNotificationPanel = Ext.extend(Ext.Panel, {
    title: 'Notification',
    iconCls: 'contact_btn_send',
    loadSettings: function () {
        if (this.appkernelsListStoreLoaded && this.resourcesListStoreLoaded) {
            var form = this.notificationSettingsForm.getForm();
            var formData = Ext.encode(form.getValues());

            if (formData === '{}') {
                formData = '{"send_report_weekly_on_day":"Monday","send_report_monthly_on_day":"1","resourcesList_all":"on","appkernelsList_all":"on","controlThreshold":"-0.5"}';
            }

            Ext.Ajax.request({
                url: XDMoD.REST.baseURL + 'app_kernels/notifications?token=' + XDMoD.REST.token,
                method: 'GET',
                params: {
                    curent_tmp_settings: formData
                },
                timeout: 60000, // 1 Minute,
                scope: this,
                success: function (response) {
                    var form2 = this.notificationSettingsForm.getForm();
                    var response2 = Ext.decode(response.responseText);
                    if (response2.success) {
                        form2.setValues(response2.data);
                    } else {
                        Ext.Msg.alert('Load failed', response2.message);
                    }
                },
                failure: function (response) {
                    Ext.Msg.alert('Load failed', response.message);
                }
            });

            return true;
        }
        return false;
    },
    sendReport: function (baseParams2) {
        var baseParams = baseParams2;
        var form = this.notificationSettingsForm.getForm();
        baseParams.report_param = form.getValues();
        baseParams.report_param[baseParams.report_type + '_send_on_event'] = 'sendAlways';
        baseParams.report_param = Ext.encode(baseParams.report_param);

        Ext.MessageBox.show({
            msg: 'Sending report, please wait...',
            progressText: 'Sending report...',
            width: 300,
            wait: true,
            waitConfig: {
                interval: 200
            }
        });

        Ext.Ajax.request({
            url: XDMoD.REST.baseURL + 'app_kernels/notifications/send?token=' + XDMoD.REST.token,
            method: 'GET',
            params: baseParams,
            timeout: 120000, // 1 Minute,
            scope: this,
            success: function (response) {
                var response2 = Ext.decode(response.responseText);
                if (response2.success) {
                    Ext.MessageBox.hide();

                    if ('message' in response2) {
                        Ext.MessageBox.alert('Status', response2.message);
                    } else {
                        Ext.MessageBox.alert('Status', 'Can not send report');
                    }
                } else {
                    Ext.MessageBox.alert('Status', 'Can not send report');
                }
            },
            failure: function (/* response */) {
                Ext.MessageBox.alert('Status', 'Can not send report');
            }
        });
    },
    initComponent: function () {
        // Send report to e-mail for specified period
        this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
            id: 'duration_selector_' + this.id,
            alignRight: false,
            showRefresh: false,
            showAggregationUnit: false,
            scope: this // also scope of handle
        });

        this.durationToolbar.dateSlider.region = 'south';

        var sendReportButton = new Ext.Button({
            /*= =======================================sendReport=============================================== */
            id: 'send_report_button_' + this.id,
            text: 'Send Report',
            iconCls: 'send_report',
            tooltip: 'Send report via e-mail for specified period',
            scope: this, // also scope of handle
            handler: function () {
                var baseParams = {};
                baseParams.start_date = this.durationToolbar.getStartDate().format('Y-m-d');
                baseParams.end_date = this.durationToolbar.getEndDate().format('Y-m-d');
                baseParams.report_type = 'for_specified_period';
                baseParams.operation = 'send_report';

                this.sendReport(baseParams);
            }
        });

        this.durationToolbar.addItem('-');
        this.durationToolbar.addItem(sendReportButton);

        // resourcesList
        this.resourcesList = new Ext.form.CheckboxGroup({
            fieldLabel: 'Resources',
            columns: 5,
            vertical: true,
            items: [
                { boxLabel: 'all', name: 'resourcesList_all', checked: true }
            ]
        });
        this.resourcesListStoreLoaded = false;
        this.resourcesListStore = new Ext.data.JsonStore({
            url: XDMoD.REST.prependPathBase('/app_kernels/resources'),
            root: 'response',
            fields: ['name'],
            autoLoad: true,
            restful: true,
            listeners: {
                scope: this,
                load: function (t, records) {
                    var i = 0;
                    for (i = 0; i < records.length; i++) {
                        var cb = new Ext.form.Checkbox({
                            boxLabel: records[i].data.name,
                            name: 'resourcesList_' + records[i].data.name
                        });
                        if (this.resourcesList.items instanceof Ext.util.MixedCollection) {
                            this.resourcesList.items.add(cb);
                        } else {
                            this.resourcesList.items.push(cb);
                        }
                        this.resourcesList.doLayout();
                    }
                    this.resourcesListStoreLoaded = true;
                }
            }
        });
        // appkernelsList
        this.appkernelsList = new Ext.form.CheckboxGroup({
            fieldLabel: 'App. Kernels',
            columns: 5,
            vertical: true,
            items: [
                { boxLabel: 'all', name: 'appkernelsList_all', checked: true }
            ]
        });
        this.appkernelsListStoreLoaded = false;
        this.appkernelsListStore = new Ext.data.JsonStore({
            url: XDMoD.REST.prependPathBase('/app_kernels/app_kernels'),
            root: 'response',
            fields: ['name'],
            autoLoad: true,
            restful: true,
            listeners: {
                scope: this,
                load: function (t, records) {
                    var i = 0;
                    for (i = 0; i < records.length; i++) {
                        var cb = new Ext.form.Checkbox({
                            boxLabel: records[i].data.name,
                            name: 'appkernelsList_' + records[i].data.name
                        });
                        if (this.appkernelsList.items instanceof Ext.util.MixedCollection) {
                            this.appkernelsList.items.addAll(cb);
                        } else {
                            this.appkernelsList.items.push(cb);
                        }
                        this.appkernelsList.doLayout();
                    }
                    this.appkernelsListStoreLoaded = true;
                }
            }
        });
        var daysOfMonth = [];
        var i;
        for (i = 1; i <= 31; i++) {
            daysOfMonth.push([i, i]);
        }
        var sendOnEvents = [
            ['sendNever', 'Do Not Send'],
            ['sendAlways', 'Send Always'],
            ['sendOnAnyErrors', 'Send on Any Errors'],
            ['sendOnFailedRuns', 'Send on Major Errors'],
            ['sendOnPatternRecAnyErrors', 'Send on Pattern Errors'],
            ['sendOnPatternRecFailedRuns', 'Send on Major Pattern Errors']
        ];
        this.notificationSettingsForm = new Ext.form.FormPanel({
            autoHeight: true,
            items: [
                {
                    xtype: 'fieldset',
                    title: 'Report Periodicity Settings',
                    autoHeight: true,
                    collapsed: false,
                    collapsible: true,
                    items: [
                        {
                            xtype: 'container',
                            border: false,
                            layout: 'form',
                            items: [
                                {
                                    xtype: 'combo',
                                    fieldLabel: 'Daily report',
                                    hiddenName: 'daily_report_send_on_event',
                                    mode: 'local',
                                    triggerAction: 'all',
                                    editable: false,
                                    disabled: false,
                                    value: 'sendNever',
                                    valueField: 'id',
                                    displayField: 'text',

                                    store: new Ext.data.ArrayStore({
                                        id: 0,
                                        fields: ['id', 'text'],
                                        data: sendOnEvents
                                    })
                                }
                            ]
                        },
                        {
                            xtype: 'container',
                            border: false,
                            layout: 'column',
                            anchor: '100%',
                            items: [
                                {
                                    xtype: 'container',
                                    border: false,
                                    layout: 'form',
                                    items: [
                                        {
                                            xtype: 'combo',
                                            fieldLabel: 'Weekly report',
                                            hiddenName: 'weekly_report_send_on_event',
                                            mode: 'local',
                                            triggerAction: 'all',
                                            editable: false,
                                            disabled: false,
                                            value: 'sendNever',
                                            valueField: 'id',
                                            displayField: 'text',

                                            store: new Ext.data.ArrayStore({
                                                id: 0,
                                                fields: ['id', 'text'],
                                                data: sendOnEvents
                                            })
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    border: false,
                                    layout: 'form',
                                    items: [
                                        {
                                            xtype: 'combo',
                                            fieldLabel: '&nbsp;&nbsp;Send report on',
                                            hiddenName: 'weekly_report_send_on',
                                            mode: 'local',
                                            triggerAction: 'all',
                                            editable: false,
                                            disabled: false,
                                            value: 2,
                                            valueField: 'id',
                                            displayField: 'text',

                                            store: new Ext.data.ArrayStore({
                                                id: 0,
                                                fields: ['id', 'text'],
                                                data: [
                                                    [2, 'Monday'],
                                                    [3, 'Tuesday'],
                                                    [4, 'Wednesday'],
                                                    [5, 'Thursday'],
                                                    [6, 'Friday'],
                                                    [7, 'Saturday'],
                                                    [1, 'Sunday']
                                                ]
                                            })
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'container',
                            border: false,
                            layout: 'column',
                            anchor: '100%',
                            items: [
                                {
                                    xtype: 'container',
                                    border: false,
                                    layout: 'form',
                                    items: [
                                        {
                                            xtype: 'combo',
                                            fieldLabel: 'Monthly report',
                                            hiddenName: 'monthly_report_send_on_event',
                                            mode: 'local',
                                            triggerAction: 'all',
                                            editable: false,
                                            disabled: false,
                                            value: 'sendNever',
                                            valueField: 'id',
                                            displayField: 'text',

                                            store: new Ext.data.ArrayStore({
                                                id: 0,
                                                fields: ['id', 'text'],
                                                data: sendOnEvents
                                            })
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    border: false,
                                    layout: 'form',
                                    items: [
                                        {
                                            xtype: 'combo',
                                            fieldLabel: '&nbsp;&nbsp;Send report on',
                                            hiddenName: 'monthly_report_send_on',
                                            mode: 'local',
                                            triggerAction: 'all',
                                            editable: false,
                                            disabled: false,
                                            value: 2,
                                            valueField: 'id',
                                            displayField: 'text',

                                            store: new Ext.data.ArrayStore({
                                                id: 0,
                                                fields: ['id', 'text'],
                                                data: daysOfMonth
                                            })
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                {
                    xtype: 'fieldset',
                    title: 'Report Options',
                    autoHeight: true,
                    layout: 'form',
                    collapsed: false,
                    collapsible: true,
                    items: [this.resourcesList, this.appkernelsList],
                    buttons: [
                        {
                            text: 'Reset Selection',
                            tooltip: 'Reset selection of resources and appkernels to default',
                            scope: this,
                            handler: function () {
                                var form = this.notificationSettingsForm.getForm();
                                var formData = Ext.encode(form.getValues());

                                Ext.Ajax.request({
                                    url: XDMoD.REST.baseURL + 'app_kernels/notifications/default?token=' + XDMoD.REST.token,
                                    method: 'GET',
                                    params: {
                                        curent_tmp_settings: formData
                                    },
                                    timeout: 60000, // 1 Minute,
                                    scope: this,
                                    success: function (response) {
                                        var form2 = this.notificationSettingsForm.getForm();
                                        var response2 = Ext.decode(response.responseText);
                                        if (response2.success) {
                                            form2.setValues(response2.data);
                                        } else {
                                            Ext.Msg.alert('Load failed', response2.message);
                                        }
                                    },
                                    failure: function (response) {
                                        Ext.Msg.alert('Load failed', response.message);
                                    }
                                });
                            }
                        }]

                }
            ],
            buttons: [
                {
                    text: 'Load Settings',
                    tooltip: 'Load previously saved settings from profile, will overwrite modified settings',
                    scope: this,
                    handler: function () {
                        this.loadSettings();
                    }
                },
                {
                    text: 'Save Settings',
                    tooltip: 'Save modified preferences to the profile',
                    scope: this,
                    handler: function () {
                        var form = this.notificationSettingsForm.getForm();
                        var formData = Ext.encode(form.getValues());

                        Ext.Ajax.request({
                            url: XDMoD.REST.baseURL + 'app_kernels/notifications?token=' + XDMoD.REST.token,
                            method: 'PUT',
                            params: {
                                curent_tmp_settings: formData
                            },
                            timeout: 60000, // 1 Minute,
                            scope: this,
                            success: function (response) {
                                var response2 = Ext.decode(response.responseText);
                                if (!response2.success) {
                                    Ext.Msg.alert('Saving failed', 'Saving failed');
                                }
                            },
                            failure: function () {
                                Ext.Msg.alert('Saving failed', 'Saving failed');
                            }
                        });
                    }
                }
            ]
        });

        this.sendReportNowToolbar = new Ext.Toolbar({
            items: [
                {
                    xtype: 'datefield', // default for Toolbars, same as 'tbbutton'
                    format: 'Y-m-d',
                    value: new Date(),
                    id: 'sendReportNowForDatefield' + this.id
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'send_report',
                    text: 'Send Daily Report',
                    scope: this,
                    handler: function () {
                        var baseParams = {};
                        var sendReportNowForDatefield = Ext.get('sendReportNowForDatefield' + this.id);
                        baseParams.end_date = sendReportNowForDatefield.getValue();// .format('Y-m-d');
                        baseParams.report_type = 'daily_report';
                        baseParams.operation = 'send_report';

                        this.sendReport(baseParams);
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'send_report',
                    text: 'Send Weekly Report',
                    scope: this,
                    handler: function () {
                        var baseParams = {};
                        var sendReportNowForDatefield = Ext.get('sendReportNowForDatefield' + this.id);
                        baseParams.end_date = sendReportNowForDatefield.getValue();// .format('Y-m-d');
                        baseParams.report_type = 'weekly_report';
                        baseParams.operation = 'send_report';

                        this.sendReport(baseParams);
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'send_report',
                    text: 'Send Monthly Report',
                    scope: this,
                    handler: function () {
                        var baseParams = {};
                        var sendReportNowForDatefield = Ext.get('sendReportNowForDatefield' + this.id);
                        baseParams.end_date = sendReportNowForDatefield.getValue();// .format('Y-m-d');
                        baseParams.report_type = 'monthly_report';
                        baseParams.operation = 'send_report';

                        this.sendReport(baseParams);
                    }
                }

            ]
        });

        Ext.apply(this, {
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            labelWidth: 110,
            autoScroll: true,
            frame: false,
            items: [
                {
                    xtype: 'panel',
                    title: 'Notification options'
                },
                this.notificationSettingsForm,
                {
                    xtype: 'panel',
                    title: 'Send report via e-mail for specified period',
                    tbar: this.durationToolbar,
                    autoHeight: true
                },
                {
                    xtype: 'panel',
                    title: 'Send report via e-mail for specified day',
                    tbar: this.sendReportNowToolbar,
                    autoHeight: true
                }
            ]
        });
        XDMoD.Module.AppKernels.AppKernelNotificationPanel.superclass.initComponent.apply(this, arguments);

        this.initLoadSettings = function () {
            if (this.loadSettings()) {
                this.un('afterlayout', this.initLoadSettings, this);
            }

            // Ensure that we unmask the main interface once we're done loading.
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) {
                viewer.el.unmask();
            }
        };

        this.on('afterlayout', this.initLoadSettings, this);
    }
});
