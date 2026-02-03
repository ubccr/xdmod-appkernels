Ext.namespace('XDMoD', 'XDMoD.Arr');

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

XDMoD.Arr.EditActiveTaskPanel = Ext.extend(Ext.FormPanel, {
    id: 'edit-active-task-panel',
    dirty: false,
    fields: ['eat-nct-date', 'eat-nct-time'],
    allFields: ['eat-nct-date', 'eat-nct-time', 'eat-resource', 'eat-application', 'eat-resource-param'],

    _DEFAULT_CONFIG: {
        layout: 'fit',
        width: 500,
        minWidth: 500,
        height: 170,
        minHeight: 170,
        plain: true,
        border: false
    },

    initComponent: function () {

        Ext.applyIf(this, this._DEFAULT_CONFIG);

        this.items = this._createItems();
        this.buttons = this._createButtons();

        XDMoD.Arr.EditActiveTaskPanel.superclass.initComponent.apply(this, arguments);


        this.form.method = 'PUT';
    },

    listeners: {
        render: function () {
            this.submitButton = Ext.getCmp('eat-submit');
            if (this.task) this._setTask(this.task);
        },

        field_dirty: function () {
            this.dirty = true;
            this.submitButton.enable();
        },

        field_cleaned: function () {
            var clean = this._checkAllAreClean(this.fields);
            this.dirty = !clean;
            if (clean) {
                this.submitButton.disable();
            }
        },

        update_requested: function () {
            var self = this;

            var format_time_to_start = function (date, time) {
                return date + " " + time + ":00";
            }

            var raw_date = Ext.getCmp('eat-nct-date');
            var raw_time = Ext.getCmp('eat-nct-time');
            var next_check_time = Ext.getCmp('eat-next-check-time');

            var formatted_time_to_start = format_time_to_start(
                raw_date.getValue().format('Y-m-d'),
                raw_time.getValue()
            );

            next_check_time.setValue(formatted_time_to_start);
            this.form.submit({
                success: function () {
                    Ext.Msg.show({
                        title: 'Success',
                        msg: 'Successfully updated the next time this task will be checked.',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.INFO,
                        fn: function (selection) {
                            self.parentPanel.fireEvent('reload');
                            self.fireEvent('update_canceled');
                        }
                    });
                },
                error: function () {
                    Ext.Msg.show({
                        title: 'Error',
                        msg: 'There was an error while attempting to perform the requested update.',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR
                    });
                }
            })
        },

        update_canceled: function () {
            this._resetComponents(this.allFields);
            this.ownerCt.close();
        }
    },

    _createItems: function () {
        var self = this;
        return [
            {
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
                        id: 'eat-resource',
                        fieldLabel: 'Resource',
                        disabled: true
                    },
                    {
                        id: 'eat-application',
                        fieldLabel: 'Application',
                        disabled: true
                    },
                    {
                        id: 'eat-resource-param',
                        fieldLabel: 'Resource Param',
                        disabled: true
                    },
                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Next Check Time',
                        name: 'eat_time_to_start',
                        items: [
                            {
                                xtype: 'datefield',
                                id: 'eat-nct-date',
                                name: 'Date',
                                minValue: new Date(),
                                minText: 'The Date cannot be before today',
                                minLength: 8,
                                minLengthText: 'The Date is malformed',
                                enableKeyEvents: true,
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    select: function (field, record, index) {
                                        this._dirtyCheck(field);
                                    },
                                    keyup: function (field, event) {
                                        this._dirtyCheck(field);
                                    }
                                },
                                _dirtyCheck: function (field) {
                                    if (field.getRawValue() !== field.originalValue && !field.updated) {
                                        self.fireEvent('field_dirty');
                                        field.updated = true;
                                    } else if (field.originalValue === field.getRawValue()) {
                                        self.fireEvent('field_cleaned');
                                        field.updated = false;
                                    }
                                }
                            },
                            {
                                xtype: 'timefield',
                                format: 'H:i',
                                id: 'eat-nct-time',
                                name: 'Time',
                                minValue: new Date(),
                                minText: 'The Time cannot be before now.',
                                minLength: 5,
                                minLengthText: 'The Time is malformed',
                                enableKeyEvents: true,
                                submitValue: false,
                                updated: false,
                                listeners: {
                                    select: function (field, record, index) {
                                        this._dirtyCheck(field);
                                    },
                                    keyup: function (field, event) {
                                        this._dirtyCheck(field);
                                    }
                                },
                                _dirtyCheck: function (field) {
                                    if (field.getRawValue() !== field.originalValue && !field.updated) {
                                        self.fireEvent('field_dirty');
                                        field.updated = true;
                                    } else if (field.originalValue === field.getRawValue()) {
                                        self.fireEvent('field_cleaned');
                                        field.updated = false;
                                    }
                                }
                            },
                            {
                                xtype: 'button',
                                text: 'Today',
                                style: '',
                                handler: function () {
                                    var date = Ext.getCmp('eat-nct-date');
                                    var time = Ext.getCmp('eat-nct-time');
                                    if (date && time) {
                                        var today = new Date();
                                        date.setValue(today);
                                        time.setValue(today);
                                        date.updated = true;
                                        time.updated = true;
                                        self.fireEvent('field_dirty');
                                    }
                                }
                            },
                            {
                                xtype: 'hidden',
                                id: 'eat-next-check-time',
                                name: 'next_check_time'
                            },
                            {
                                xtype: 'hidden',
                                id: 'eat-task-id',
                                name: 'task_id'
                            }
                        ]
                    },
                ]
            }
        ]
    },

    _createButtons: function (options) {
        var self = this;

        var submitText = options && options.submit && options.submit.text
            ? options.submit.text
            : 'Update';

        var cancelText = options && options.cancel && options.cancel.text
            ? options.cancel.text
            : 'Cancel';

        return [
            {
                id: 'eat-submit',
                text: submitText,
                disabled: true,
                handler: function () {
                    self.fireEvent('update_requested');
                }
            },
            {
                text: cancelText,
                handler: function () {
                    self.fireEvent('update_canceled');
                }
            }
        ];
    },

    _setTask: function (task) {
        if (!task) return;

        var resourceField = Ext.getCmp('eat-resource');
        var appField = Ext.getCmp('eat-application');
        var resourceParamField = Ext.getCmp('eat-resource-param');
        var nctDate = Ext.getCmp('eat-nct-date');
        var nctTime = Ext.getCmp('eat-nct-time');
        var taskIdField = Ext.getCmp('eat-task-id');

        var resource = task.get('resource');
        var app = task.get('app');
        var resourceParam = task.get('resource_param');
        var rawNextCheckTime = task.get('next_check_time');
        var nextCheckTime = this._splitDatetime(rawNextCheckTime);
        var taskId = task.get('task_id');

        resourceField.setValue(resource);
        appField.setValue(app);
        resourceParamField.setValue(resourceParam);
        nctDate.setValue(nextCheckTime[0]);
        nctTime.setValue(nextCheckTime[1]);
        taskIdField.setValue(taskId);

        this.form.url = XDMoD.REST.url + '/akrr/tasks/active/' + taskId + '?token=' + XDMoD.REST.token;
    },

    _checkAllAreClean: function (components) {
        var result = true;
        for (var i = 0; i < components.length; i++) {
            var component = Ext.getCmp(components[i]);
            result &= !component.isDirty();
        }
        return result;
    },

    _resetComponents: function (components) {
        for (var i = 0; i < components.length; i++) {
            var component = Ext.getCmp(components[i]);
            if (component) component.setValue(null);
        }
    },

    _formatDate: function (value) {
        var date = [
            value.getFullYear(),
            this._pad(String(value.getMonth()), 2, '0', STR_PAD_LEFT),
            this._pad(String(value.getDate()), 2, '0', STR_PAD_LEFT)
        ];

        var time = [
            this._pad(String(value.getHours()), 2, '0', STR_PAD_LEFT),
            this._pad(String(value.getMinutes()), 2, '0', STR_PAD_LEFT),
            this._pad(String(value.getSeconds()), 2, '0', STR_PAD_LEFT)
        ];

        return date.join("/") + " " + time.join(":");
    },

    _pad: function (str, len, pad, dir) {
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
    },

    _splitDatetime: function (datetime) {
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
    }

});