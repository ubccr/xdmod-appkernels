Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.CreateSchedulePanel = Ext.extend(Ext.FormPanel, {
    id: 'create-schedule-panel',
    resourcesLoaded: false,
    kernelsLoaded: false,

    initComponent: function () {

        Ext.apply(this, {
            border: false,
            width: 480,
            minWidth: 480,
            height: 500,
            minHeight: 500
        });

        this.resources = this._createResources();
        this.kernels = this._createKernels();
        this.nodes = this._createNodes();

        this.items = this._createItems();

        this.buttons = this._createButtons();

        XDMoD.Arr.CreateSchedulePanel.superclass.initComponent.apply(this, arguments);

        this.form.url = XDMoD.REST.url + '/akrr/tasks/scheduled?token=' + XDMoD.REST.token;
    },

    listeners: {
        afterrender: function () {
            this.relayEvents(this.ownerCt, ['show']);
        },
        show: function () {
            this.resources.load();
            this.kernels.load();

            Ext.QuickTips.init();

            var explanation = "When the preceeding Year, Month or Day value is greater than 0 than this time value will be forced to '00:00'.";

            new Ext.ToolTip({
                target: 'ct_time_label2',
                anchor: 'left',
                html: explanation
            });
        },

        set_defaults: function () {
            var ri_years = Ext.getCmp('ct_ri_years');
            var ri_months = Ext.getCmp('ct_ri_months');
            var ri_days = Ext.getCmp('ct_ri_days');
            var ri_time = Ext.getCmp('ct_ri_time');
            var tts_date = Ext.getCmp('ct_tts_date');
            var tts_time = Ext.getCmp('ct_tts_time');

            ri_years.setValue(0);
            ri_months.setValue(0);
            ri_days.setValue(0);
            ri_time.setValue('00:00');

            ri_years.updated = true;
            ri_months.updated = true;
            ri_days.updated = true;
            ri_time.updated = true;
            tts_date.updated = true;
            tts_time.updated = true;

            var now = new Date();
            tts_date.setValue(now);
            tts_time.setValue(now);

            this.check_status();
        },

        resources_loaded: function () {
            this.resourcesLoaded = true;

            var count = this.resources.getTotalCount();
            if (count > 0) {
                var record = this.resources.getAt(0);
                var resource = Ext.getCmp('ct-resource');
                if (record && resource) {
                    var name = record.get('name');
                    resource.setValue(name);
                }
            }
            if (this.resourcesLoaded && this.kernelsLoaded) {
                this.fireEvent('set_defaults');
            }
        },

        kernels_loaded: function () {
            this.kernelsLoaded = true;

            var count = this.kernels.getTotalCount();

            if (count > 0) {

                var record = this.kernels.getAt(0);
                if (record) {

                    var kernels = Ext.getCmp('ct-appkernel');
                    kernels.setValue(record.get('name'));

                    var raw_nodes = record.get('nodes_list');
                    var nodes = this.parse_nodes(raw_nodes);

                    this.nodes.loadData(nodes);
                    this.nodes.proxy.data = nodes;

                    var node_count = this.nodes.getTotalCount();
                    if (node_count > 0) {
                        var nnodes = Ext.getCmp('ct-nnodes');
                        var node = this.nodes.getAt(0);
                        nnodes.setValue(node.get('value'));
                    }
                }
            }
            if (this.resourcesLoaded && this.kernelsLoaded) {
                this.fireEvent('set_defaults');
            }
        },

        task_created: function () {
            var self = this;

            var ri_years = Ext.getCmp('ct_ri_years');
            var ri_months = Ext.getCmp('ct_ri_months');
            var ri_days = Ext.getCmp('ct_ri_days');
            var ri_time = Ext.getCmp('ct_ri_time');
            var tts_date = Ext.getCmp('ct_tts_date');
            var tts_time = Ext.getCmp('ct_tts_time');

            var repeat_in = Ext.getCmp('ct_repeat_in');
            var next_run = Ext.getCmp('ct_time_to_start');

            var nnodes = Ext.getCmp('ct-nnodes');
            var resource_params = Ext.getCmp('ct_resource_params');
            var app_param = Ext.getCmp('ct_app_params');
            var task_param = Ext.getCmp('ct_task_params');

            var resource = Ext.getCmp('ct-resource');
            var kernel = Ext.getCmp('ct-appkernel');

            var formatted_repeat_in = this.format_repeat_in(
                ri_years.getValue(),
                ri_months.getValue(),
                ri_days.getValue(),
                ri_time.getValue()
            );
            var formatted_time_to_start = this.format_time_to_start(
                tts_date.getValue().format('Y-m-d'),
                tts_time.getValue()
            );

            repeat_in.setValue(formatted_repeat_in);
            next_run.setValue(formatted_time_to_start);

            resource_params.setValue(
                JSON.stringify(
                    {
                        'nnodes': nnodes.getValue()
                    }
                )
            );

            resource_params.enable();

            ri_years.originalValue = ri_years.getValue();
            ri_months.originalValue = ri_months.getValue();
            ri_days.originalValue = ri_days.getValue();
            ri_time.originalValue = ri_time.getValue();
            tts_date.originalValue = tts_date.getValue();
            tts_time.originalValue = tts_time.getValue();
            repeat_in.originalValue = repeat_in.getValue();
            next_run.originalValue = next_run.getValue();
            resource.originalValue = resource.getValue();
            kernel.originalValue = kernel.getValue();
            resource_params.originalValue = resource_params.getValue();
            app_param.originalValue = app_param.getValue();
            task_param.originalValue = task_param.getValue();

            ri_years.updated = false;
            ri_months.updated = false;
            ri_days.updated = false;
            ri_time.updated = false;
            tts_date.updated = false;
            tts_time.updated = false;
            repeat_in.updated = false;
            next_run.updated = false;

            this.form.submit({
                success: function () {
                    resource_params.disable();
                    if (self.parentPanel) {
                        self.parentPanel.fireEvent('reload_tasks');
                    }
                    self.fireEvent('task_create_canceled');
                }
            })
        },

        task_create_canceled: function () {
            var ri_years = Ext.getCmp('ct_ri_years');
            var ri_months = Ext.getCmp('ct_ri_months');
            var ri_days = Ext.getCmp('ct_ri_days');
            var ri_time = Ext.getCmp('ct_ri_time');
            var tts_date = Ext.getCmp('ct_tts_date');
            var tts_time = Ext.getCmp('ct_tts_time');

            var repeat_in = Ext.getCmp('ct_repeat_in');
            var next_run = Ext.getCmp('ct_time_to_start');

            var nnodes = Ext.getCmp('ct-nnodes');
            var resource_params = Ext.getCmp('ct_resource_params');
            var app_param = Ext.getCmp('ct_app_params');
            var task_param = Ext.getCmp('ct_task_params');

            var resource = Ext.getCmp('ct-resource');
            var kernel = Ext.getCmp('ct-appkernel');

            this.clear(ri_years);
            this.clear(ri_months);
            this.clear(ri_days);
            this.clear(ri_time);
            this.clear(tts_date);
            this.clear(tts_time);
            this.clear(repeat_in);
            this.clear(next_run);

            this.clear(nnodes);
            this.clear(resource_params);
            this.clear(app_param);
            this.clear(task_param);
            this.clear(resource);
            this.clear(kernel);

            this.fireEvent('close');
        },

        close: function () {
            this.ownerCt.hide();
        }
    },

    _createResources: function () {
        var self = this;
        return new Ext.data.JsonStore({
            root: 'data',
            messageProperty: 'message',
            successProperty: 'success',
            idProperty: 'id',
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: '/rest/AKRR/AKRR/resources?token=' + XDMoD.REST.token
            }),
            fields: [
                {name: 'id', type: 'int'},
                {name: 'name', type: 'string'}
            ],
            listeners: {
                load: function (store, records, options) {
                    self.fireEvent('resources_loaded');
                }
            }
        });
    },

    _createKernels: function () {
        var self = this;
        return new Ext.data.JsonStore({
            root: 'data',
            messageProperty: 'message',
            successProperty: 'success',
            idProperty: 'id',
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: '/rest/AKRR/AKRR/kernels?token=' + XDMoD.REST.token,
                baseParams: {
                    disabled: false
                }
            }),
            fields: [
                {name: 'id', type: 'int'},
                {name: 'name', type: 'string'},
                {name: 'enabled', type: 'int'},
                {name: 'nodes_list', type: 'string'}
            ],
            listeners: {
                load: function (store, records, options) {
                    self.fireEvent('kernels_loaded');
                }
            }
        });
    },

    _createNodes: function () {
        return new Ext.data.ArrayStore({
            storeId: 'nnodeStore',
            fields: [
                {name: 'value', type: 'int'}
            ],
            proxy: new Ext.data.MemoryProxy()
        });
    },

    _createItems: function () {
        var self = this;
        return [
            {
                id: 'ct-left-column',
                xtype: 'fieldset',
                border: false,
                columnWidth: .5,
                minWidth: 450,
                defaults: {
                    anchor: '-20'
                },
                defaultType: 'textfield',
                bodyStyle: 'padding: 5px 5px 0',
                items: [
                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Repeat Every',
                        items: [
                            {
                                xtype: 'numberfield',
                                maxLength: 1,
                                maxValue: 9,
                                minValue: 0,
                                msgTarget: 'side',
                                maxText: 'You may only enter values <= 9.',
                                minText: 'You may only enter values >= 0',
                                startValue: 0,
                                name: 'ct_ri_years',
                                id: 'ct_ri_years',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        if (field.isDirty() && "" + newValue != field.originalValue && !field.updated && field.isValid()) {
                                            field.updated = true;
                                        } else if ("" + newValue === field.originalValue) {
                                            field.updated = false;
                                        }
                                        self.check_status();
                                    }
                                }
                            }, {
                                xtype: 'label',
                                text: 'Years',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;'
                            },
                            {
                                xtype: 'label',
                                text: '(#)',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;margin:0 0 0 40px'
                            }
                        ]
                    }, {
                        xtype: 'compositefield',
                        items: [
                            {
                                xtype: 'numberfield',
                                maxLength: 2,
                                maxValue: 99,
                                minValue: 0,
                                name: 'ct_ri_months',
                                id: 'ct_ri_months',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        var padded = self.pad("" + newValue, 2, "0", STR_PAD_LEFT);
                                        if (field.isDirty() && padded != field.originalValue && !field.updated) {
                                            field.setValue(padded)
                                            field.updated = true;
                                        } else if (padded === field.originalValue) {
                                            field.updated = false;
                                        }

                                        self.check_status();
                                    }
                                }
                            },
                            {
                                xtype: 'label',
                                text: 'Months',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;'

                            },
                            {
                                xtype: 'label',
                                text: '(##)',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;margin:0 0 0 31px'
                            }
                        ]
                    }, {
                        xtype: 'compositefield',
                        items: [
                            {
                                xtype: 'numberfield',
                                maxLength: 3,
                                maxValue: 999,
                                minValue: 0,
                                name: 'ct_ri_days',
                                id: 'ct_ri_days',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        var padded = self.pad("" + newValue, 3, "0", STR_PAD_LEFT);
                                        if (field.isDirty() && padded != field.originalValue && !field.updated) {
                                            field.setValue(padded)
                                            field.updated = true;
                                        } else if (padded === field.originalValue) {
                                            field.updated = false;
                                        }

                                        self.check_status();
                                    }
                                }
                            }, {
                                xtype: 'label',
                                text: 'Days',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;'
                            },
                            {
                                xtype: 'label',
                                text: '(###)',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;margin:0 0 0 45px;'
                            }
                        ]
                    },
                    {
                        xtype: 'compositefield',
                        items: [
                            {
                                xtype: 'timefield',
                                format: 'H:i',
                                name: 'ct_ri_time',
                                id: 'ct_ri_time',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    select: function (field, record, index) {
                                        if (field.isDirty() && field.getValue() !== field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.updated = true;
                                        } else if (field.originalValue === field.getValue()) {
                                            self.fireEvent('task_cancel');
                                            field.updated = false;
                                        }
                                        self.check_status();
                                    }
                                }
                            },
                            {
                                xtype: 'label',
                                id: 'ct_time_label1',
                                text: 'Time',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;'
                            },
                            {
                                xtype: 'label',
                                id: 'ct_time_label2',
                                text: '(HH:MM)',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0; background-repeat: no-repeat; background-position-x: 45px; width: 60px',
                                cls: 'info'
                            }
                        ]
                    },

                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Next Run',
                        name: 'ct_time_to_start',
                        items: [
                            {
                                xtype: 'datefield',
                                id: 'ct_tts_date',
                                name: 'ct_tts_date',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    select: function (field, record, index) {
                                        if (field.isDirty() && field.getValue() !== field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.updated = true;
                                        } else if (field.originalValue === field.getValue()) {
                                            self.fireEvent('task_cancel');
                                            field.updated = false;
                                        }
                                        self.check_status();
                                    }
                                }
                            },
                            {
                                xtype: 'timefield',
                                format: 'H:i',
                                id: 'ct_tts_time',
                                name: 'ct_tts_time',
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    select: function (field, record, index) {
                                        if (field.isDirty() && field.getValue() !== field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.updated = true;
                                        } else if (field.originalValue === field.getValue()) {
                                            self.fireEvent('task_cancel');
                                            field.updated = false;
                                        }
                                        self.check_status();
                                    }
                                }
                            }
                        ]
                    },
                    {
                        xtype: 'hidden',
                        name: 'repeat_in',
                        id: 'ct_repeat_in'
                    }, {
                        xtype: 'hidden',
                        name: 'time_to_start',
                        id: 'ct_time_to_start'
                    }
                ]
            }, {
                xtype: 'fieldset',
                autoHeight: true,
                columnWidth: .5,
                border: false,
                defaults: {
                    anchor: '-20'
                },
                defaultType: 'textfield',
                items: [
                    {
                        xtype: 'combo',
                        fieldLabel: 'Resource',
                        name: 'resource',
                        id: 'ct-resource',
                        store: self.resources,
                        valueField: 'id',
                        displayField: 'name'
                    }, {
                        xtype: 'combo',
                        fieldLabel: 'App Kernel',
                        name: 'app_kernel',
                        id: 'ct-appkernel',
                        store: self.kernels,
                        valueField: 'id',
                        displayField: 'name',
                        listeners: {
                            beforeselect: function (combo, record, index) {
                                if (record) {
                                    var nodes_list = record.get('nodes_list');
                                    if (nodes_list) {

                                        var nodes = self.parse_nodes(nodes_list);
                                        self.nodes.loadData(nodes);
                                        self.nodes.proxy.data = nodes;

                                        if (self.nodes.getTotalCount() > 0) {
                                            var first_node = self.nodes.getAt(0);
                                            var nnodes = Ext.getCmp('ct-nnodes');
                                            nnodes.setValue(first_node.get('value'));
                                        }
                                    }
                                }
                                self.check_status();
                            }
                        }
                    }, {
                        xtype: 'combo',
                        fieldLabel: 'Nodes',
                        name: 'nnodes',
                        id: 'ct-nnodes',
                        store: self.nodes,
                        valueField: 'value',
                        displayField: 'value',
                        minChars: 1,
                        maxChars: 10,
                        submitValue: false
                    }, {
                        fieldLabel: 'Resource Parameters',
                        disabled: true,
                        name: 'resource_param',
                        id: 'ct_resource_params'
                    }, {
                        xtype: 'hidden',
                        fieldLabel: 'Application Parameters',
                        name: 'app_param',
                        id: 'ct_app_params'
                    }, {
                        xtype: 'hidden',
                        fieldLabel: 'Task Parameters',
                        name: 'task_param',
                        id: 'ct_task_params'
                    }, {
                        xtype: 'hidden',
                        name: 'group_id'
                    }
                ]
            }
        ];
    },

    _createButtons: function () {
        var self = this;
        return [
            {
                id: 'ct-create',
                text: 'Create',
                disabled: true,
                handler: function () {
                    self.fireEvent('task_created');
                }
            },
            {
                text: 'Cancel',
                handler: function () {
                    self.fireEvent('task_create_canceled');
                }
            }
        ];
    },

    check_status: function () {
        var years = Ext.getCmp('ct_ri_years');
        var months = Ext.getCmp('ct_ri_months');
        var days = Ext.getCmp('ct_ri_days');
        var time = Ext.getCmp('ct_ri_time');
        var tts_date = Ext.getCmp('ct_tts_date');
        var tts_time = Ext.getCmp('ct_tts_time');

        var years_value = years ? years.getValue() : 0;
        var months_value = months ? months.getValue() : 0;
        var days_value = days ? days.getValue() : 0;

        if ((years_value > 0 ) || (months_value > 0 ) || (days_value > 0)) {

            time.setValue('00:00');
            time.originalValue = '00:00';
            time.updated = true;
            time.disable();
        } else {
            time.enable();
        }

        var years_updated = years && years.updated && this.exists(years_value);
        var months_updated = months && months.updated && this.exists(months_value);
        var days_updated = days && days.updated && this.exists(days_value);
        var time_updated = time && time.updated && this.exists(time.getValue());
        var tts_date_updated = tts_date && tts_date.updated && this.exists(tts_date.getValue());
        var tts_time_updated = tts_time && tts_time.updated && this.exists(tts_time.getValue());

        if (years_updated && months_updated && days_updated
            && time_updated && tts_date_updated && tts_time_updated) {
            Ext.getCmp('ct-create').enable();
        } else {
            Ext.getCmp('ct-create').disable();
        }
    },
    exists: function (value) {
        var value = value !== null && value !== undefined ? value.toString() : "";
        return value && typeof(value) === 'string' && value.length > 0;
    },
    parse_nodes: function (nnodes) {
        if (nnodes && typeof(nnodes) === 'string') {
            var temp = nnodes.split(';');
            var results = [];
            for (var i = 0; i < temp.length; i++) {
                var result = undefined;
                try {
                    result = temp[i];
                    results.push([result]);
                } catch (error) {
                    /* NO-OP we're just not going to add it to the results. */
                }
            }
            return results;
        } else {
            return [nnodes];
        }
    },
    format_repeat_in: function (years, months, days, time) {
        var padded_months = this.pad("" + months, 2, "0", STR_PAD_LEFT);
        var padded_days = this.pad("" + days, 3, "0", STR_PAD_LEFT);

        return years + "-" + padded_months + "-" + padded_days + " " + time + ":00";
    },
    format_time_to_start: function (date, time) {
        return date + " " + time + ":00";
    },
    clear: function (component) {
        if (component && typeof component === 'object') {
            component.setValue(null);
            component.originalValue = null;
        }
    },
    pad: function (str, len, pad, dir) {
        if (typeof(len) == "undefined") {
            var len = 0;
        }
        if (typeof(pad) == "undefined") {
            var pad = ' ';
        }
        if (typeof(dir) == "undefined") {
            var dir = STR_PAD_RIGHT;
        }

        if (len + 1 >= str.length) {

            switch (dir) {

                case STR_PAD_LEFT:
                    str = Array(len + 1 - str.length).join(pad) + str;
                    break;

                case STR_PAD_BOTH:
                    var right = Math.ceil((len = len - str.length) / 2);
                    var left = len - right;
                    str = Array(left + 1).join(pad) + str + Array(right + 1).join(pad);
                    break;

                default:
                    str = str + Array(len + 1 - str.length).join(pad);
                    break;

            } // switch

        }

        return str;
    }
});