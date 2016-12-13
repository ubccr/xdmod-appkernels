Ext.namespace('XDMoD', 'XDMoD.Arr');


/**
 * The XDMoD.Arr.WalltimePanel contains the functionality required to interact with
 * the 'mod_akrr.akrr_default_walllimit' table. This table defines the amount of time
 * ( in wall time ) that a given application is expected to take per resource
 * per node count. This component will provide standard CRUD functionality.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
XDMoD.Arr.WalltimePanel = Ext.extend(Ext.FormPanel, {
    id: 'walltime',
    title: 'Walltime',


    /**
     * Default constructor for the panel. Note that when the component is
     * constructed as such:
     * var panel = new XDMoD.Arr.WalltimePanel(
     *   {
      *    option1: value1
     *   });
     * 'option1' will be available as 'this.option1' while execution
     * resides within 'initComponent'.
     */
    initComponent: function () {
        this.toolbar = this.createToolbar();
        this.store = this.createDataStore(this);
        this.grid = this.createGridPanel();

        Ext.apply(this, {
            layout: 'border',
            frame: false,
            tbar: this.toolbar,
            items: [
                this.grid
            ]
        });
        XDMoD.Arr.WalltimePanel.superclass.initComponent.apply(this, arguments);
    },

    /**
     * Methods that will be executed when the event indicated is received.
     */
    listeners: {
        reload_entries: function (panel) {
            this.store.load();
        },
        entry_selected: function (panel, record) {
            this.selected = record;
        },
        create_entry: function (panel) {
            var self = this;
            new Ext.Window({
                id: 'create-entry-window',
                layout: 'fit',
                title: 'Create New Entry',
                plain: true,
                border: false,
                items: new XDMoD.Arr.WalltimeNewEntryPanel({
                    event_reference: self,
                    id: 'entry-creation-panel'
                })
            }).show();
        },

        edit_entry: function (panel) {
            var self = this;
            if (this.selected) {

                new Ext.Window({
                    id: 'edit-entry-window',
                    layout: 'fit',
                    title: 'Edit Entry',
                    plain: true,
                    border: false,
                    items: new XDMoD.Arr.WalltimeEditEntryPanel({
                        event_reference: self,
                        entry: self.selected,
                        id: 'entry-edit-panel',
                        buttonOptions: {
                            submit: {
                                text: 'Edit'
                            }
                        }
                    })
                }).show();
            } else {
                Ext.Msg.alert('Unable to Edit', 'You must select an entry to edit.');
            }

        },
        delete_entry: function (panel) {
            var self = this;
            if (!this.selected) {

            } else {
                var id = this.selected.get('id') || this.selected.id;
                var url = XDMoD.REST.url + '/akrr/walltime/' + id + '?token=' + XDMoD.REST.token;
                Ext.Ajax.request({
                    url: url,
                    method: 'DELETE',
                    success: function () {
                        Ext.Msg.alert('Success', 'Entry successfully removed!');
                        self.fireEvent('reload_entries');
                    },
                    failure: function () {
                        Ext.Msg.alert('Error', 'There was a problem removing the selected entry.');
                    }
                })
            }

        },
        render: function () {
            this.store.load();
        }
    }
    ,
    createGridPanel: function () {
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
                    {header: 'Resource', sortable: true, dataIndex: 'resource'},
                    {header: 'App. Kernel', sortable: true, dataIndex: 'app', width: 240},
                    {header: 'Wall Limit', sortable: true, dataIndex: 'walllimit'},
                    {header: 'Nodes', sortable: true, dataIndex: 'nodes'},
                    {header: 'Resource Param', sortable: true, dataIndex: 'resource_param'}
                ]
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (sm, row, record) {
                        self.fireEvent('entry_selected', self, record);
                    }
                }
            })
        });
    },
    createToolbar: function () {
        var self = this;
        return new Ext.Toolbar({
            items: [
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'refresh',
                    text: 'Reload',
                    scope: this,
                    handler: function () {
                        self.fireEvent('reload_entries', self);
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'add',
                    text: 'New Entry',
                    scope: this,
                    handler: function () {
                        self.fireEvent('create_entry', self);
                    }
                },
                {
                    xtype: 'button',
                    iconCls: 'date_edit',
                    text: 'Edit Entry',
                    scope: this,
                    handler: function () {
                        self.fireEvent('edit_entry', self);
                    }
                },
                {
                    xtype: 'button', // default for Toolbars, same as 'tbbutton'
                    iconCls: 'delete',
                    text: 'Delete Entry',
                    scope: this,
                    handler: function () {
                        self.fireEvent('delete_entry', self);
                    }
                }
            ]
        });

    },

    createDataStore: function (panel) {
        return new Ext.data.JsonStore({
            panelParent: panel,
            messageProperty: 'message',
            successProperty: 'success',
            root: 'data',
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/akrr/walltime?token=' + XDMoD.REST.token,
                baseParams: {
                    disabled: false
                }
            }),
            listeners: {
                load: function (store, records, options) {
                    var records_length = records.length;
                    var selected = store.panelParent && store.panelParent.selected ? store.panelParent.selected : null;

                    for (var i = 0; i < records_length; i++) {
                        var record = records[i];

                        var resource_param = record.get('resource_param');
                        var processed = resource_param !== null && resource_param !== undefined
                            ? resource_param.replace(/'/g, '"')
                            : resource_param;
                        var nnodes = processed !== null && processed !== undefined
                            ? JSON.parse(processed)
                            : {};
                        var nodes = nnodes !== null && nnodes !== undefined &&
                        nnodes.nnodes !== null && nnodes.nnodes !== undefined
                            ? nnodes.nnodes
                            : 0;
                        record.set('nodes', nodes);

                        if (selected && selected.id === record.id) store.panelParent.selected = record;
                    }
                }
            },
            fields: [
                {name: 'walllimit', type: 'int'},
                {name: 'app', type: 'string'},
                {name: 'comments', type: 'string'},
                {name: 'last_update', type: 'string'},
                {name: 'resource_param', type: 'string'},
                {name: 'app_param', type: 'string'},
                {name: 'resource', type: 'string'},
                {name: 'nodes', type: 'int'}
            ]
        });
    }

});
