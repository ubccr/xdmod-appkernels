Ext.namespace('XDMoD', 'XDMoD.Arr');

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

XDMoD.Arr.EditSchedulePanel = Ext.extend(Ext.Panel, {
    id: 'edit-schedule-panel',
    title: 'Details',
    updated: 0,

    _DEFAULT_CONFIG: {
        autoScroll: true,
        collapsible: true,
        split: true,
        title: 'Details',
        height: 220,
        layout: 'column',
        bodyStyle: 'padding: 5px 5px 0'
    },

    initComponent: function () {
        Ext.apply(this, this._DEFAULT_CONFIG);

        this.buttons = this._createButtons();
        this.items = this._createItems();

        XDMoD.Arr.EditSchedulePanel.superclass.initComponent.apply(this, arguments);
    },

    listeners: {

        afterrender: function (panel) {
            var explanation = "When the preceeding Year, Month or Day value is greater than 0 than this time value will be forced to '00:00'.";

            new Ext.ToolTip({
                target: 'et_time_label',
                anchor: 'left',
                html: explanation
            });
            Ext.QuickTips.init();
        },

        task_selected: function (record) {
            var time_to_start = this.split_datetime(record.get('time_to_start'));
            var repeat_in = this.split_datetime(record.get('repeat_in'));
            repeat_in[0] = this.retrieve_date(repeat_in[0]);

            var time_to_start_is_valid = time_to_start.length === 2;
            if (time_to_start_is_valid) {
                var start_date = Ext.getCmp('tts_date');
                var start_time = Ext.getCmp('tts_time');

                start_date.setValue(time_to_start[0]);
                start_time.setValue(time_to_start[1]);

                start_date.originalValue = time_to_start[0];
                start_time.originalValue = time_to_start[1];
                var time_store = start_time.getStore();
                time_store.add(new time_store.recordType({field1: time_to_start[1]}));
                time_store.sort('field1');
            }
            var repeat_in_is_valid = repeat_in.length === 2;
            if (repeat_in_is_valid) {
                var years = Ext.getCmp('ri_years');
                var months = Ext.getCmp('ri_months');
                var days = Ext.getCmp('ri_days');
                var time = Ext.getCmp('ri_time');

                years.setValue(repeat_in[0][0]);
                months.setValue(repeat_in[0][1]);
                days.setValue(repeat_in[0][2]);
                time.setValue(repeat_in[1]);

                years.originalValue = repeat_in[0][0];
                months.originalValue = repeat_in[0][1];
                days.originalValue = repeat_in[0][2];
                time.originalValue = repeat_in[1];
            }

            Ext.getCmp('detail-cancel').enable();
            this.check_status();

            if (this.collapsed) {
                this.show();
            }
        },

        edit_requested: function () {
            var ri_years = Ext.getCmp('ri_years');
            var ri_months = Ext.getCmp('ri_months');
            var ri_days = Ext.getCmp('ri_days');
            var ri_time = Ext.getCmp('ri_time');
            var tts_date = Ext.getCmp('tts_date');
            var tts_time = Ext.getCmp('tts_time');

            var repeat_in = Ext.getCmp('repeat_in');
            var next_run = Ext.getCmp('time_to_start');

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

            ri_years.originalValue = ri_years.getValue();
            ri_months.originalValue = ri_months.getValue();
            ri_days.originalValue = ri_days.getValue();
            ri_time.originalValue = ri_time.getValue();
            tts_date.originalValue = tts_date.getValue();
            tts_time.originalValue = tts_time.getValue();

            ri_years.updated = false;
            ri_months.updated = false;
            ri_days.updated = false;
            ri_time.updated = false;
            tts_date.updated = false;
            tts_time.updated = false;

            this.updated = 0;

            Ext.getCmp('detail-save').disable();

            var form = this.parentPanel.getForm();
            var task_id = form.active_record.get('task_id');
            form.url = XDMoD.REST.url + '/akrr/tasks/scheduled/' + task_id + "?token=" + XDMoD.REST.token;

            form.updateRecord(form.active_record);
            form.submit({
                success: function (data) {
                    if (data && data.responseText) {
                        var response = JSON.parse(data.responseText);
                        var success = response && response.success ? response.success : false;
                        var event = success ? 'record_updated' : 'record_update_failed';
                        var eventData = success ? form.active_record : null;
                        if (this.parentPanel) {
                            this.parentPanel.fireEvent(event, eventData);
                        }
                    }
                },
                error: function () {
                    if (this.parentPanel) {
                        this.parentPanel.fireEvent('record_update_failed');
                    }
                }
            });
        },

        edit_canceled: function () {
            var ri_years = Ext.getCmp('ri_years');
            var ri_months = Ext.getCmp('ri_months');
            var ri_days = Ext.getCmp('ri_days');
            var ri_time = Ext.getCmp('ri_time');
            var tts_date = Ext.getCmp('tts_date');
            var tts_time = Ext.getCmp('tts_time');

            var repeat_in = Ext.getCmp('repeat_in');
            var next_run = Ext.getCmp('time_to_start');

            ri_years.originalValue = null;
            ri_months.originalValue = null;
            ri_days.originalValue = null;
            ri_time.originalValue = null;
            tts_date.originalValue = null;
            tts_time.originalValue = null;
            repeat_in.originalValue = null;
            next_run.originalValue = null;

            ri_years.updated = false;
            ri_months.updated = false;
            ri_days.updated = false;
            ri_time.updated = false;
            tts_date.updated = false;
            tts_time.updated = false;

            ri_years.reset();
            ri_months.reset();
            ri_days.reset();
            ri_time.reset();
            tts_date.reset();
            tts_time.reset();
            repeat_in.reset();
            next_run.reset();

            ri_years.disable();
            ri_months.disable();
            ri_days.disable();
            ri_time.disable();
            tts_date.disable();
            tts_time.disable();

            this.parentPanel.getForm().reset();

            this.updated = 0;

            this.fireEvent('task_cancel');
        },

        task_updated: function () {
            this.updated += 1;
            if (this.updated >= 1) {
                Ext.getCmp('detail-save').enable();
            }
            console.log(this.updated);
        },

        task_cancel: function () {
            this.updated -= 1;
            if (this.updated === 0) {
                Ext.getCmp('detail-save').disable();
            } else if (this.updated < 0) {
                this.updated = 0;
            }
            console.log(this.updated);
        },

        task_removed: function (record) {
            var ri_years = Ext.getCmp('ri_years');
            var ri_months = Ext.getCmp('ri_months');
            var ri_days = Ext.getCmp('ri_days');
            var ri_time = Ext.getCmp('ri_time');
            var tts_date = Ext.getCmp('tts_date');
            var tts_time = Ext.getCmp('tts_time');

            var repeat_in = Ext.getCmp('repeat_in');
            var next_run = Ext.getCmp('time_to_start');

            ri_years.setValue('');
            ri_months.setValue('');
            ri_days.setValue('');
            ri_time.setValue('');
            tts_date.setValue('');
            tts_time.setValue('');
            repeat_in.setValue('');
            next_run.setValue('');

            ri_years.originalValue = '';
            ri_months.originalValue = '';
            ri_days.originalValue = '';
            ri_time.originalValue = '';
            tts_date.originalValue = '';
            tts_time.originalValue = '';

            this.parentPanel.getForm().reset();
            this.parentPanel.store.remove(record);
        }
    },

    _createButtons: function () {
        var self = this;
        return [
            {
                text: 'Save',
                id: 'detail-save',
                iconCls: 'query_save',
                disabled: true,
                handler: function () {
                    self.fireEvent('edit_requested');
                }
            },
            {
                text: 'Cancel',
                id: 'detail-cancel',
                iconCls: 'general_btn_close',
                disabled: true,
                handler: function () {
                    self.fireEvent('edit_canceled');
                }
            }
        ];
    },

    _createItems: function () {
        var self = this;
        return [
            {
                id: 'left-column',
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
                                name: 'ri_years',
                                id: 'ri_years',
                                submitValue: false,
                                updated: false,
                                disabled: true,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        if (field.isDirty() && "" + newValue != field.originalValue && !field.updated && field.isValid()) {
                                            self.fireEvent('task_updated');
                                            field.updated = true;
                                        } else if ("" + newValue === field.originalValue) {
                                            self.fireEvent('task_cancel');
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
                                name: 'ri_months',
                                id: 'ri_months',
                                submitValue: false,
                                updated: false,
                                disabled: true,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        var padded = self.pad("" + newValue, 2, "0", STR_PAD_LEFT);
                                        if (field.isDirty() && padded != field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.setValue(padded)
                                            field.updated = true;
                                        } else if (padded === field.originalValue) {
                                            self.fireEvent('task_cancel');
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
                                name: 'ri_days',
                                id: 'ri_days',
                                submitValue: false,
                                updated: false,
                                disabled: true,
                                listeners: {
                                    change: function (field, newValue, oldValue) {
                                        var padded = self.pad("" + newValue, 3, "0", STR_PAD_LEFT);
                                        if (field.isDirty() && padded != field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.setValue(padded)
                                            field.updated = true;
                                        } else if (padded === field.originalValue) {
                                            self.fireEvent('task_cancel');
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
                                name: 'ri_time',
                                id: 'ri_time',
                                submitValue: false,
                                updated: false,
                                disabled: true,
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
                                text: 'Time',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;'
                            },
                            {
                                xtype: 'label',
                                id: 'et_time_label',
                                text: '(HH:MM)',
                                style: 'color: #333;font-weight: bold;font-size: 11px;font-family: tahoma,arial,verdana,sans-serif;padding: 4px 0 0 0;background-repeat: no-repeat; background-position-x: 45px; width: 60px;',
                                cls: 'info'
                            }
                        ]
                    },

                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Next Run',
                        name: 'time_to_start',
                        items: [
                            {
                                xtype: 'datefield',
                                id: 'tts_date',
                                name: 'tts_date',
                                submitValue: false,
                                updated: false,
                                disabled: true,
                                listeners: {
                                    select: function (field, record, index) {
                                        var currentValue = field.getValue();
                                        var newValue = self.format_date(
                                            currentValue.getFullYear(),
                                            currentValue.getMonth() + 1,
                                            currentValue.getDate()
                                        );

                                        if (field.isDirty() && newValue !== field.originalValue && !field.updated) {
                                            self.fireEvent('task_updated');
                                            field.updated = true;
                                        } else if (newValue === field.originalValue) {
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
                                id: 'tts_time',
                                name: 'tts_time',
                                submitValue: false,
                                updated: false,
                                disabled: true,
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
                        id: 'repeat_in'
                    }, {
                        xtype: 'hidden',
                        name: 'time_to_start',
                        id: 'time_to_start'
                    }
                ]
            }, {
                xtype: 'fieldset',
                autoHeight: true,
                columnWidth: .5,
                border: false,
                defaults: {
                    anchor: '-20',
                    disabled: true
                },
                defaultType: 'textfield',
                items: [
                    {
                        fieldLabel: 'Resource',
                        name: 'resource'
                    }, {
                        fieldLabel: 'App Kernel',
                        name: 'app'
                    }, {
                        fieldLabel: 'Nodes',
                        name: 'nnodes'
                    }, {
                        fieldLabel: 'Resource Parameters',
                        name: 'resource_param'
                    }, {
                        fieldLabel: 'Application Parameters',
                        name: 'app_param'
                    }, {
                        fieldLabel: 'Task Parameters',
                        name: 'task_param'
                    }, {
                        fieldLabel: 'Group',
                        name: 'group_id'
                    }, {
                        fieldLabel: 'Task ID',
                        name: 'task_id'
                    }, {
                        fieldLabel: 'Parent Task ID',
                        name: 'parent_task_id'
                    }
                ]
            }];
    },

    check_status: function () {
        var years = Ext.getCmp('ri_years');
        var months = Ext.getCmp('ri_months');
        var days = Ext.getCmp('ri_days');
        var time = Ext.getCmp('ri_time');
        var tts_date = Ext.getCmp('tts_date');
        var tts_time = Ext.getCmp('tts_time');

        var repeat_in = Ext.getCmp('repeat_in');
        var next_run = Ext.getCmp('time_to_start');

        var years_value = years ? years.getValue() : 0;
        var months_value = months ? months.getValue() : 0;
        var days_value = days ? days.getValue() : 0;

        if (years.disabled) years.enable();
        if (months.disabled) months.enable();
        if (days.disabled) days.enable();
        if (time.disabled) time.enable();
        if (tts_date.disabled) tts_date.enable();
        if (tts_time.disabled) tts_time.enable();

        if ((years_value > 0 ) || (months_value > 0 ) || (days_value > 0)) {
            time.setValue('00:00');
            time.originalValue = '00:00';
            time.updated = false;
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
        var next_run_updated = next_run && next_run.updated && this.exists(next_run.getValue());

        if (years_updated || months_updated || days_updated
            || time_updated || tts_date_updated || tts_time_updated || next_run_updated) {
            Ext.getCmp('detail-save').enable();
        } else {
            Ext.getCmp('detail-save').disable();
        }
    },

    /**
     * Function to properly take in a datetime formatted string "date<space>time" and return an
     * array [date, time] that's suitable for setting an extjs datefield, timefield. Or, if the
     * input is some how found to be invalid ( often via the time value being improperly formatted )
     * then an empty array is returned.
     * @param {String} datetime
     * @returns {Array}
     */
    split_datetime: function (datetime) {
        var results = [];
        if (datetime && typeof(datetime === 'string') && datetime.length > 0) {
            var parts = datetime.split(' ');
            var num_of_parts = parts.length;
            switch (num_of_parts) {
                case 2:
                    var time = parts[1];
                    var colon_count = time.match(/:/g).length;
                    switch (colon_count) {
                        case 1:
                            // Ok, standard time dealio, just pass it on.
                            results = parts;
                            break;
                        case 2:
                            results[0] = parts[0];
                            // Only retrieve the HH:MM portion of the time.
                            results[1] = time.substring(0, time.lastIndexOf(':'));
                            ;
                            break;
                        case 0:
                            // If there are no ':' found then is it at least a valid hour?
                            if (time.match(/\d{1,2}/g)) {
                                results = parts;
                            }
                            break;
                        default:
                            /* NO CLUE WHAT'S GOING ON... WE SUPPORT
                             * HH:MM:SS OR  OR SOME COMBINATION THERE OF */
                            break;
                    }
                    break;
                default:
                    /* If we don't have a date and a time then no idea what to do, fall through.*/
                    break;
            }
        }
        return results;
    },

    retrieve_date: function (date, separator) {
        var results = [];
        separator = separator || '-';
        if (date && typeof(date === 'string') && date.length > 0) {
            var parts = date.split(separator);
            var num_of_date_parts = parts.length;
            switch (num_of_date_parts) {
                case 3:
                    // We have Y-MM-DDD.
                    results = parts;
                    break;
                default:
                    // No clue what we have, return an empty array.
                    break;
            }
        }

        return results;
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
            len = 0;
        }
        if (typeof(pad) == "undefined") {
            pad = ' ';
        }
        if (typeof(dir) == "undefined") {
            dir = STR_PAD_RIGHT;
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
    },
    exists: function (value) {
        var value = value !== null && value !== undefined ? value.toString() : "";
        return value && typeof(value) === 'string' && value.length > 0;
    }

});