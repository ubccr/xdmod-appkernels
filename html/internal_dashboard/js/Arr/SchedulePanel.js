/**
 * ARR Status panel.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 *
 */

Ext.namespace('XDMoD', 'XDMoD.Arr', 'CCR');

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

Ext.QuickTips.init();
Ext.form.Field.prototype.msgTarget = 'under';

XDMoD.Arr.SchedulePanel = Ext.extend(Ext.FormPanel, {
    id: 'schedule-panel',
    title: 'Schedule',
    updated: 0,
    createTaskWindow: null,
    createGroupTaskWindow: null,

    initComponent: function () {

        this.addEvents(
            'reload_tasks',
            'task_create'
        );

        this.store = this._createStore();
        this.toolbar = this._createToolbar();
        this.grid = this._createGrid();
        this.detailsPanel = this._createDetailPanel();

        Ext.apply(this, {
            layout: 'border',
            frame: false,
            tbar: this.toolbar,
            items: [
                this.grid,
                this.detailsPanel
            ]
        });

        XDMoD.Arr.SchedulePanel.superclass.initComponent.apply(this, arguments);

        this.form.method = 'PUT';
        this.form.url = XDMoD.REST.url + '/akrr/tasks/scheduled?token=' + XDMoD.REST.token;
    },

    listeners: {
        reload_tasks: function () {
            if (this.store) {
                this.store.load();
            }
        },
        task_create: function () {
            var self = this;
            if (!this.createTaskWindow) {
                this.createTaskWindow = new Ext.Window({
                    layout: 'fit',
                    title: 'Create New Task',
                    width: 480,
                    minWidth: 480,
                    height: 500,
                    minHeight: 500,
                    closeAction: 'hide',
                    plain: true,
                    border: false,
                    items: new XDMoD.Arr.CreateSchedulePanel({
                        parentPanel: self
                    })
                });
            }
            this.createTaskWindow.show();
        },
        group_tasks_create: function () {
            var self = this;
            if (!this.createGroupTaskWindow) {
                this.createGroupTaskWindow = new Ext.Window({
                    layout: 'fit',
                    title: 'Create New Group Tasks',
                    width: 700,
                    minWidth: 700,
                    height: 500,
                    minHeight: 500,
                    closeAction: 'hide',
                    plain: true,
                    border: false,
                    items: new XDMoD.Arr.CreateGroupTasksPanel({
                        parentPanel: self
                    })
                });
            }
            this.createGroupTaskWindow.show();
        },
        task_removed: function(record) {
            this.store.remove(record);
        }
    },

    _createStore: function () {
        return new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/akrr/tasks/scheduled?token=' + XDMoD.REST.token,
            root: 'data',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',
            idProperty: 'task_id',
            fields: [
                {name: 'task_id', type: 'int'},
                {name: 'time_to_start', type: 'string'},
                {name: 'repeat_in', type: 'string'},
                {name: 'resource', type: 'string'},
                {name: 'app', type: 'string'},
                {name: 'resource_param', type: 'string'},
                {name: 'app_param', type: 'string'},
                {name: 'task_param', type: 'string'},
                {name: 'group_id', type: 'string'},
                {name: 'parent_task_id', type: 'int'},
                {name: 'nnodes', type: 'int'}
            ]
        });
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
                        self.fireEvent('reload_tasks');
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'add',
                    text: 'New Task(s)',
                    scope: this,
                    handler: function () {
                        self.fireEvent('task_create');
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'add',
                    text: 'New Group Tasks',
                    tooltip: "Create and submit a set of new tasks",
                    scope: this,
                    handler: function () {
                        self.fireEvent('group_tasks_create');
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'delete',
                    text: 'Delete Task(s)',
                    scope: this,
                    handler: function () {
                        if (self.getForm().active_record) {

                            var url =  XDMoD.REST.url + '/akrr/tasks/scheduled/' +
                                self.getForm().active_record.data.task_id +
                                '?token=' + XDMoD.REST.token;

                            jQuery.ajax({
                                method: 'DELETE',
                                url: url,
                                success: function (data, textStatus, jqXHR) {
                                    self.fireEvent('task_removed', self.getForm().active_record);
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log('An Exception has occurred: ' + textStatus + ' ' + errorThrown);
                                }
                            });
                        } else {
                            console.log("No record selected for deletion.");
                        }

                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'bullet',
                    text: 'Estimate Core-hours Consumption',
                    disabled: true,
                    scope: this,
                    handler: function () {
                    }
                }
            ]
        });
    },

    _createGrid: function () {
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
            listeners: {
                viewready: function () {
                    self.store.load();
                }

            },
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: false
                },
                columns: [
                    {header: 'Resource', sortable: true, dataIndex: 'resource'},
                    {header: 'App. Kernel', sortable: true, dataIndex: 'app', width: 240},
                    {header: 'Nodes', sortable: true, dataIndex: 'nnodes', width: 60},
                    {header: 'Repeat Every (Y-MM-DDD HH:MM)', sortable: true, dataIndex: 'repeat_in', width: 200},
                    {header: 'Next Run', sortable: true, dataIndex: 'time_to_start'},
                    {header: 'Resource Param', sortable: true, dataIndex: 'resource_param'},
                    {header: 'App Param', sortable: true, dataIndex: 'app_param'},
                    {header: 'Task Param', sortable: true, dataIndex: 'task_param'},
                    {header: 'Group', sortable: true, dataIndex: 'group_id', width: 240},
                    {id: 'task_id', header: 'Task ID', sortable: true, dataIndex: 'task_id'},
                    {header: 'Parent Task ID', sortable: true, dataIndex: 'parent_task_id'}

                ]
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (sm, row, record) {
                        self.fireEvent('task_selected', record);
                        self.getForm().loadRecord(record);
                        self.getForm().active_record = record;
                    }
                }
            })
        });
    },

    _createDetailPanel: function() {
        var panel = new XDMoD.Arr.EditSchedulePanel({
            region: 'south',
            parentPanel: this
        });

        panel.relayEvents(this, ['task_selected']);

        return panel;
    }

});


