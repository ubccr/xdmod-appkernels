Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.WalltimeEditEntryPanel = Ext.extend(Ext.FormPanel, {
    _DEFAULT_CONFIG: {
        layout: 'fit',
        width: 480,
        minWidth: 480,
        height: 170,
        minHeight: 170,
        plain: true,
        border: false
    },
    initComponent: function () {
        // CONFIGURE: this instance of the component with the newly created
        //            items and default settings.
        Ext.apply(this, this._DEFAULT_CONFIG);
        // CREATE: the required datastores.
        this._createDataStores();

        this._createButtons(this.buttonOptions || {});

        this.items = this._createItems();

        if (this.event_reference) this.event_reference.relayEvents(this, ['reload_entries']);

        XDMoD.Arr.WalltimeEditEntryPanel.superclass.initComponent.apply(this, arguments);

        this.form.url = XDMoD.REST.url + '/akrr/walltime?token=' + XDMoD.REST.token;
        this.form.method = 'PUT';
    }, // initComponent

    /**
     * Component wide event listeners are defined here.
     */
    listeners: {
        render: function () {
            this.resourcesLoaded = false;
            this.kernelsLoaded = false;

            this.resources.load();
            this.kernels.load();

            if (this.entry) {
                this.fireEvent('edit_entry', this.entry);
            }
        },
        entry_created: function () {
            var self = this;

            var resourceParams = Ext.getCmp('ce_resource_params');
            var appParams = Ext.getCmp('ce_app_params');
            var nnodesCmp = Ext.getCmp('ce-nnodes');

            var nodes = nnodesCmp.getValue();

            resourceParams.setValue(JSON.stringify({nnodes: nodes}));
            appParams.setValue(JSON.stringify({}));

            this.form.submit({
                success: function (form, action) {
                    self.fireEvent('clear');
                    self.fireEvent('reload_entries');
                    self.ownerCt.close();
                },
                failure: function (form, action) {
                    console.log('form submit failed');
                }
            });
        },

        edit_entry: function (entry) {
            if (this.resourcesLoaded && this.kernelsLoaded) {
                if (typeof entry === 'object') {
                    var hasGet = entry.get && typeof entry.get === 'function';
                    if (hasGet) {
                        var resource = entry.get('resource');
                        var app = entry.get('app');
                        var nodes = entry.get('nodes');
                        var wall_time = entry.get('walllimit');
                    } else {
                        var resource = entry.resource ? entry.resource : null;
                        var app = entry.app ? entry.app : null;
                        var nodes = entry.nodes ? entry.nodes : null;
                        var wall_time = entry.wall_time ? entry.wall_time : null;
                    }


                    this.fireEvent('set_resource', resource);
                    this.fireEvent('set_app', app, nodes);
                    this.fireEvent('set_walltime', wall_time);
                }
            } else {
                this.selected = entry;
            }
        },

        close_entry: function () {
            this.ownerCt.close();
        },
        set_resource: function (resource) {
            if (typeof resource === 'string') {
                var index = this.resources.find('name', resource);
            } else if (typeof resource === 'number') {
                var index = this.resources.find('id', resource);
            }
            var record = this.resources.getAt(index);
            if (record) {
                var component = Ext.getCmp('ce-resource');
                if (component && component.setValue) component.setValue(record.id);
            }
        }
        ,
        set_app: function (app, nodes) {
            if (typeof app === 'string') {
                var index = this.kernels.find('name', app);
            } else if (typeof app === 'number') {
                var index = this.kernels.find('id', app);
            }
            var record = this.kernels.getAt(index);
            if (record) {

                var component = Ext.getCmp('ce-appkernel');
                if (component && component.setValue) component.setValue(record.id);


                this._setNodeValue(record);

                if (typeof nodes === 'number') {
                    var nodeComponent = Ext.getCmp('ce-nnodes');
                    var index = this.nodes.find('value', nodes);
                    var nodeRecord = this.nodes.getAt(index);
                    if (nodeRecord) nodeComponent.setValue(nodeRecord.get('value'));
                }
            }

        }
        ,
        set_walltime: function (wall_time) {
            var walltime = typeof wall_time === 'number' ? wall_time : typeof wall_time === 'string' ? Number(wall_time) : null;
            if (walltime) {
                var component = Ext.getCmp('ce-walllimit');
                component.setValue(walltime);
            }
        }
        ,
        resources_loaded: function () {
            this.resourcesLoaded = true;
            if (this.kernelsLoaded && this.selected) {
                this.fireEvent('edit_entry', this.selected);
            }
        }
        ,
        kernels_loaded: function () {
            this.kernelsLoaded = true;
            if (this.resourcesLoaded && this.selected) {
                this.fireEvent('edit_entry', this.selected);
            }
        }
        ,
        clear: function () {
            var components = ["ce-nnodes", "ce-walllimit"];
            for (var i = 0; i < components.length; i++) {
                var component = Ext.getCmp(components[i]);
                if (component) this._clear(component);
            }
        }
        ,
        validate: function () {
            var components = ["ce-resource", "ce-appkernel", "ce-nnodes", "ce-walllimit"];
            var valid = 0;
            for (var i = 0; i < components.length; i++) {
                var component = Ext.getCmp(components[i]);
                var value = component && component.getValue ? component.getValue() : null;
                if (value !== null && value !== '' && value !== 0) valid += 1;
            }
            if (valid === components.length) {
                Ext.getCmp('ce-create').enable();
            } else {
                Ext.getCmp('ce-create').disable();
            }
        }
    }
    , // listeners
    _createButtons: function (options) {
        var submitText = options && options.submit && options.submit.text
            ? options.submit.text
            : 'Create';

        var cancelText = options && options.cancel && options.cancel.text
            ? options.cancel.text
            : 'Cancel';

        this.buttons = [
            {
                id: 'ce-create',
                text: submitText,
                disabled: true,
                handler: function () {
                    this.ownerCt.ownerCt.fireEvent('entry_created');
                }
            },
            {
                text: cancelText,
                handler: function () {
                    this.ownerCt.ownerCt.fireEvent('clear');
                    this.ownerCt.ownerCt.fireEvent('close_entry');
                }
            }
        ];
    }
    ,
    /**
     * Helper function that just encapsulates the setting up / creation
     * of the required data stores.
     * @private
     */
    _createDataStores: function () {
        var self = this;
        self.kernelsLoaded = false;
        self.resourcesLoaded = false;

        this.kernels = new Ext.data.JsonStore({
            root: 'data',
            messageProperty: 'message',
            successProperty: 'success',
            idProperty: 'id',
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/akrr/kernels?token=' + XDMoD.REST.token,
                baseParams: {
                    disabled: true
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
                    var component = Ext.getCmp('ce-appkernel');
                    var nnodes = Ext.getCmp('ce-nnodes');
                    if (component && records && records.length && records.length > 0) {
                        var record = records[0];
                        component.setValue(record.get('id'));
                        self._setNodeValue(record);
                    }
                    self.fireEvent('kernels_loaded');
                }
            }
        });

        this.resources = new Ext.data.JsonStore({
            root: 'data',
            messageProperty: 'message',
            successProperty: 'success',
            idProperty: 'id',
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/akrr/resources?token=' + XDMoD.REST.token
            }),
            fields: [
                {name: 'id', type: 'int'},
                {name: 'name', type: 'string'}
            ],
            listeners: {
                load: function (store, records, options) {
                    var component = Ext.getCmp('ce-resource');
                    if (component && records && records.length && records.length > 0) {
                        var record = records[0];
                        component.setValue(record.get('id'));
                    }
                    self.fireEvent('resources_loaded');
                }
            }
        });

        this.nodes = new Ext.data.ArrayStore({
            storeId: 'nnodeStore',
            fields: [
                {name: 'value', type: 'int'}
            ],
            proxy: new Ext.data.MemoryProxy()
        });
    }

    , // _createDataStores

    /**
     * Creates an array of objects suitable for inclusion as this components 'items' property.
     *
     * @returns {*[]}
     * @private
     */
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
                        xtype: 'combo',
                        fieldLabel: 'Resource',
                        name: 'resource',
                        id: 'ce-resource',
                        store: self.resources,
                        valueField: 'id',
                        displayField: 'name',
                        editable: false,
                        minChars: 2,
                        listeners: {
                            blur: function (field) {
                                field.ownerCt.ownerCt.fireEvent('validate');
                            }
                        }
                    }, {
                        xtype: 'combo',
                        fieldLabel: 'App Kernel',
                        name: 'app_kernel',
                        id: 'ce-appkernel',
                        store: self.kernels,
                        valueField: 'id',
                        displayField: 'name',
                        editable: false,
                        listeners: {
                            beforeselect: function (combo, record, index) {
                                self._setNodeValue(record);
                            },
                            blur: function (field) {
                                field.ownerCt.ownerCt.fireEvent('validate');
                            }
                        }
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: 'Nodes',
                        name: 'nnodes',
                        id: 'ce-nnodes',
                        store: self.nodes,
                        valueField: 'value',
                        displayField: 'value',
                        minChars: 1,
                        maxChars: 10,
                        submitValue: false,
                        editable: false,
                        listeners: {
                            blur: function (field) {
                                field.ownerCt.ownerCt.fireEvent('validate');
                            }
                        }
                    },
                    {
                        xtype: 'numberfield',
                        id: 'ce-walllimit',
                        name: 'walltime',
                        fieldLabel: 'Wall Limit<br/>(in minutes)',
                        enableKeyEvents: true,
                        listeners: {
                            keyup: function (field, event) {
                                field.ownerCt.ownerCt.fireEvent('validate');
                            }
                        }
                    },
                    {
                        xtype: 'hidden',
                        name: 'resource_param',
                        id: 'ce_resource_params'
                    },
                    {
                        xtype: 'hidden',
                        fieldLabel: 'Application Parameters',
                        name: 'app_param',
                        id: 'ce_app_params'
                    },
                    {
                        xtype: 'hidden',
                        name: 'comments'
                    }
                ]
            }
        ];
    }
    , // _createItems

    /**
     * A helper function that eases the complexity of setting the data source of the 'nodes' combobox as well as the
     * default value.
     *
     * @param record the AppKernel record that was selected.
     * @private
     */
    _setNodeValue: function (record) {
        var self = this;
        var parse_nodes = function (nnodes) {
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
        };

        if (record) {
            var nodes_list = record.get('nodes_list');
            if (nodes_list) {

                var nodes = parse_nodes(nodes_list);
                self.nodes.loadData(nodes);
                self.nodes.proxy.data = nodes;

                if (self.nodes.getTotalCount() > 0) {
                    var first_node = self.nodes.getAt(0);
                    var nnodes = Ext.getCmp('ce-nnodes');
                    nnodes.setValue(first_node.get('value'));
                }
            }
        }
    }
    ,// _setNodeValue

    /**
     * Helper function to clear the provided components value.
     *
     * @param component an object returned from Ext.getCmp();
     * @private
     */
    _clear: function (component) {
        if (component) {
            component.setValue(null);
        }
    } // _clear
})
;
