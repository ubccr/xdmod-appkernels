Ext.namespace('AppKernel');
AppKernel.debugMode = true;

// --------------------------------------------------------------------------------
// Construct the arguments to a rest call
// --------------------------------------------------------------------------------

AppKernel.constructNodeArguments = function (node) {
    const startDate = AppKernel.dateRange.getForm().findField('startdt').getRawValue();
    const endDate = AppKernel.dateRange.getForm().findField('enddt').getRawValue();
    const status = AppKernel.dateRange.getForm().findField('akstatus').getValue();

    let call_arguments = {
        debug: AppKernel.debugMode,
        ak: node.attributes.ak_id,
        resource: node.attributes.resource_id,
        metric: node.attributes.metric_id,
        num_proc_units: node.attributes.num_proc_units,
        collected: node.attributes.collected,
        instance_id: node.attributes.instance_id
    };

    XDMoD.REST.removeEmptyParameters(call_arguments);

    if ("" !== startDate) {
        call_arguments.start_time = Date.parse(startDate) / 1000.0;
    }
    if ("" !== endDate) {
        call_arguments.end_time = Date.parse(endDate) / 1000.0;
    }
    if ("" !== status) {
        call_arguments.status = status;
    }

    return call_arguments;
};

// --------------------------------------------------------------------------------
// Callback required by the node click callback for populating the right-hand
// pane with instance data.
// --------------------------------------------------------------------------------

AppKernel.cbInstanceData = function(options, success, response) {

    let data;
    if (success) {
        data = CCR.safelyDecodeJSONResponse(response);
        success = CCR.checkDecodedJSONResponseSuccess(data);
    }

    if (!success) {
        CCR.xdmod.ui.presentFailureResponse(
            response,
            {
                title: 'App Kernel Explorer',
                wrapperMessage: 'The instance failed to load.'
            }
        );
        return;
    }

    Ext.getCmp('xmlpanel').body.update(data.results);

};  // cbInstanceData()

// --------------------------------------------------------------------------------
// Add an event handler for a click event on a tree node.  If the node is a leaf
// then call the load() method of the xml panel and populate it with the
// reporter instance body XML.
// --------------------------------------------------------------------------------

AppKernel.nodeClickCb = function(node) {

    if ('units' == node.attributes.type) {
        let call_arguments = AppKernel.constructNodeArguments(node);
        call_arguments.format = 'img_tag';

        XDMoD.REST.connection.request({
                url: '/app_kernels/plots',
                method : 'GET',
                params: call_arguments,
                timeout: 60000,
                success : function(response) {
                    Ext.getCmp('xmlpanel').body.update(response.responseText);
                },
                failure : function(response, opt) {
                    const msg = "AJAX Request Error: status = '" + response.statusText +
                    "' (" + response.status + ")<br><br>" +
                    (response.responseText ? response.responseText : "" );
                    Ext.MessageBox.alert('REST', msg);
                }
            });


    }

    if ( node && ! node.leaf ) { return; }

    if ( 'instance' == node.attributes.type ) {
        let call_arguments = AppKernel.constructNodeArguments(node);
        XDMoD.REST.connection.request({
            url: '/app_kernels/details',
            method: 'GET',
            callback: AppKernel.cbInstanceData,
            params: call_arguments
        });
    }
};

// --------------------------------------------------------------------------------

AppKernel.treeTb = new Ext.Toolbar({
    items: [
        '->',
        {
            iconCls: 'refresh',
            tooltip: 'Refresh tree',
            handler: function ()
            {
                AppKernel.tree.root.removeAll(true);
                AppKernel.tree.loader.on('load', null, this, { single: true });
                AppKernel.tree.loader.load(AppKernel.tree.root);
            },
            scope: this
        }
    ]
});

AppKernel.tree = new Ext.tree.TreePanel({
    title: 'App Kernels',
    rootVisible: false,
    tbar: AppKernel.treeTb,
    useArrows: true,
    autoScroll: true,
    animate: true,
    root: new Ext.tree.AsyncTreeNode(),
    loader: new XDMoD.REST.TreeLoader({
        url: '/app_kernels/details',
        requestMethod: 'GET',
        listeners: {
            beforeload: function (loader, node, callback) {
                loader.baseParams = AppKernel.constructNodeArguments(node);
            },
            load: XDMoD.REST.TreeLoader.prototype.createStandardLoadListener(),
            loadexception: function (loader, node, response) {
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: 'App Kernel Explorer',
                    wrapperMessage: 'The tree node failed to load.'
                });
            }
        }
    }),
    selModel: new Ext.tree.DefaultSelectionModel({
        listeners: {
            selectionchange: function (model, node) {
                AppKernel.nodeClickCb(node);
            }
        }
    }),

    listeners: {
        'beforeappend': function (t, p, n) {
            if ( n.leaf) n.setCls("status-" + n.attributes.status);
        }
    }
});

// --------------------------------------------------------------------------------
const now = new Date();

AppKernel.dateRange = new Ext.FormPanel({
    labelWidth: 75,
    frame: true,
    title: 'Date Range',
    bodyStyle:'padding:5px 5px 0',
    width: 'auto',
    defaults: {width: 150},
    defaultType: 'datefield',
    items: [{
        fieldLabel: 'Start Date',
        name: 'startdt',
        id: 'startdt',
        xtype: 'datefield',
        value: '3/21/2011'
    },
    {
        fieldLabel: 'End Date',
        name: 'enddt',
        id: 'enddt',
        xtype: 'datefield',
        value: (now.getMonth() + 1) + '/' +  now.getDate() + '/' + now.getFullYear()
    },
    {
        fieldLabel: 'AK Status',
        name: 'akstatus',
        triggerAction: 'all',
        xtype: 'combo',
        mode: 'local',
        editable: false,
        allowBlank: false,
        value: '',
        valueField: 'All',
        store: [
            ['', 'All'],
            ['success', 'Success'],
            ['failure', 'Failure'],
            ['error', 'Error'],
            ['queued', 'Queued']
        ]
    }]
});

Ext.onReady(function(){
    // Create the viewpot that contains a nested border layout.  The west
    // region contains the inca suite pulldown and reportertree view.  The
    // center region contains the XML display for the reporter body.

    const vp = new Ext.Viewport({
        layout: 'border',
        title: 'Main Viewport',
        renderTo: 'akexplorer',
        defaults: {
            collapsible: true,
            split: true,
            bodyStyle: 'padding: 5px',
            autoScroll: true
        },
        items: [
            {
                region: 'west',
                width: 300,
                margins: '5 0 5 5',
                items: [
                    AppKernel.dateRange,
                    AppKernel.tree
                ]
            },
            {
                title: 'Application Kernel Details',
                collapsible: false,
                id: 'xmlpanel',
                xtype: 'panel',
                region:'center',
                layout: 'fit',
                margins: '5 5 5 0',
                autoScroll: true,
                items: []
            }
        ]
    });
});
