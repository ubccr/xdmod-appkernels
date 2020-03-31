Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.ActiveTasksPanel = Ext.extend(Ext.Panel, {
    id: 'active-tasks',
    title: 'Active Tasks',

    _DEFAULT_CONFIG: {
        root_url: '/active_tasks'
    },

    initComponent: function () {
        // eslint-disable-next-line no-underscore-dangle
        Ext.apply(this, this._DEFAULT_CONFIG);
        // eslint-disable-next-line no-underscore-dangle
        this.toolbar = this._createToolbar();
        // eslint-disable-next-line no-underscore-dangle
        this.store = this._createDataStore();
        // eslint-disable-next-line no-underscore-dangle
        this.grid = this._createGridPanel();

        Ext.apply(this, {
            layout: 'border',
            frame: false,
            tbar: this.toolbar,
            items: [
                this.grid
            ]
        });

        XDMoD.Arr.ActiveTasksPanel.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        render: function () {
            this.store.load();
        },

        reload: function () {
            this.store.load();
        },

        edit_task: function () {
            if (!this.selected) {
                this.fireEvent('no_task_selected');
                return;
            }

            new Ext.Window({
                title: 'Edit Active Task',

                items: new XDMoD.Arr.EditActiveTaskPanel({
                    parentPanel: this,
                    task: this.selected
                })
            }).show();
        },

        delete_task: function () {
            var self = this;
            if (!this.selected) {
                this.fireEvent('no_task_selected');
                return;
            }

            var processDeletion = function (selection) {
                var lower = typeof selection === 'string' ? selection.toLowerCase() : '';
                switch (lower) {
                    case Ext.MessageBox.buttonText.ok.toLowerCase():
                    case Ext.MessageBox.buttonText.yes.toLowerCase():

                        var task = self.selected.get('task_id');
                        var url = XDMoD.REST.url + '/akrr/tasks/active/' + task + '?token=' + XDMoD.REST.token;

                        Ext.Ajax.request({
                            url: url,
                            method: 'DELETE',
                            success: function (response) {
                                if (response && response.responseText) {
                                    var data = JSON.parse(response.responseText);

                                    var success = data && data.success ? data.success : false;

                                    if (success) {
                                        Ext.Msg.show({
                                            title: 'Task Deleted',
                                            msg: 'Task successfully deleted.',
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.INFO,
                                            fn: function () {
                                                self.fireEvent('reload');
                                            }
                                        });
                                    } else {
                                        var message = 'An error occurred while attempting to delete task. [ ' + (data.message ? data.message : 'N/A') + ' ]';
                                        Ext.Msg.show({
                                            title: 'Error',
                                            msg: message,
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.ERROR
                                        });
                                    }
                                }
                            },
                            failure: function () {
                                Ext.Msg.show({
                                    title: 'Error',
                                    msg: 'An error occurred while attempting to delete task.',
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.ERROR
                                });
                            }
                        });
                        break;
                    case Ext.MessageBox.buttonText.no.toLowerCase():
                    case Ext.MessageBox.buttonText.cancel.toLowerCase():
                    default:
                    /* NO-OP as they answered no / cancel */
                }
            };

            Ext.Msg.show({
                title: 'Delete?',
                msg: 'Are you sure you want to delete the selected active task?',
                buttons: Ext.MessageBox.YESNO,
                icon: Ext.MessageBox.WARNING,
                fn: processDeletion
            });
        },

        select_task: function (task) {
            this.selected = task;
        },

        no_task_selected: function () {
            Ext.Msg.show({
                title: 'Error',
                msg: 'No task selected.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
        }
    },

    _createToolbar: function () {
        var self = this;
        return new Ext.Toolbar({
            items: [
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'refresh',
                    text: 'Reload',
                    scope: this,
                    handler: function () {
                        self.fireEvent('reload');
                    }
                },
                {
                    xtype: 'button',
                    iconCls: 'date_edit',
                    text: 'Edit Active Task',
                    scope: this,
                    handler: function () {
                        self.fireEvent('edit_task');
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'delete',
                    text: 'Delete Active Task',
                    scope: this,
                    handler: function () {
                        self.fireEvent('delete_task');
                    }
                }
            ]
        });
    },

    _createDataStore: function () {
        return new Ext.data.JsonStore({
            messageProperty: 'message',
            successProperty: 'success',
            root: 'data',
            restful: true,
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/akrr/tasks/active?token=' + XDMoD.REST.token
            }),
            fields: [
                { name: 'status_update_time', type: 'string' },
                { name: 'master_task_id', type: 'int' },
                { name: 'app', type: 'string' },
                { name: 'resource_param', type: 'string' },
                { name: 'task_lock', type: 'int' },
                { name: 'datetime_stamp', type: 'string' },
                { name: 'time_submitted_to_queue', type: 'string' },
                { name: 'fatal_errors_count', type: 'int' },
                { name: 'fails_to_submit_to_the_queue', type: 'int' },
                { name: 'status', type: 'string' },
                { name: 'next_check_time', type: 'string' },
                { name: 'time_to_start', type: 'string' },
                { name: 'status_info', type: 'string' },
                { name: 'resource', type: 'string' },
                { name: 'task_id', type: 'int' },
                { name: 'time_activated', type: 'string' },
                { name: 'repeat_in', type: 'string' },
                { name: 'task_param', type: 'string' },
                { name: 'task_exec_log', type: 'string' },
                { name: 'parent_task_id', type: 'int' },
                { name: 'app_param', type: 'string' },
                { name: 'group_id', type: 'string' }
            ]
        });
    },
    _createGridPanel: function () {
        var self = this;
        return new Ext.grid.GridPanel({
            id: 'gridPanel',
            region: 'center',
            autoScroll: true,
            border: true,
            title: 'Schedule',
            columnLines: true,
            enableColumnMove: false,
            enableColumnHide: false,

            store: self.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: false
                },
                columns: [
                    { header: 'Resource', dataIndex: 'resource', sortable: true },
                    { header: 'Application', dataIndex: 'app', sortable: true },
                    { header: 'resource_param', dataIndex: 'resource_param', sortable: true },
                    { header: 'Time Submitted', dataIndex: 'time_submitted_to_queue', sortable: true },
                    { header: 'time_to_start', dataIndex: 'time_to_start', sortable: true },
                    { header: 'next_check_time', dataIndex: 'next_check_time', sortable: true },
                    { header: 'status_update_time', dataIndex: 'status_update_time', sortable: true },
                    { header: 'time_activated', dataIndex: 'time_activated', sortable: true },
                    { header: 'datetime_stamp', dataIndex: 'datetime_stamp', sortable: true },
                    { header: 'status', dataIndex: 'status', sortable: true },
                    { header: 'status_info', dataIndex: 'status_info', sortable: true },
                    { header: 'task_exec_log', dataIndex: 'task_exec_log', sortable: true },
                    { header: 'fatal_errors_count', dataIndex: 'fatal_errors_count', sortable: true },
                    { header: 'fails_to_submit_to_the_queue', dataIndex: 'fails_to_submit_to_the_queue', sortable: true },
                    { header: 'task_lock', dataIndex: 'task_lock', sortable: true }
                ]
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (sm, row, record) {
                        self.fireEvent('select_task', record);
                    }
                }
            })
        });
    }
});
