/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Aug-25 (version 1)
*
* @author Ryan Gentner
* @date 2013-Jun-23 (version 2)
*
*
* Contains the code for the App Kernel Explorer
*
*/


XDMoD.Module.AppKernels.AppKernelExplorer = function (config) {

    XDMoD.Module.AppKernels.AppKernelExplorer.superclass.constructor.call(this, config);

};

// ===========================================================================

Ext.extend(XDMoD.Module.AppKernels.AppKernelExplorer, XDMoD.PortalModule, {

    module_id: 'data_explorer',

    usesToolbar: true,

    toolbarItems: {

        durationSelector: {

            enable: true,

            config: {
                showAggregationUnit: false
            }

        }, //durationSelector

        exportMenu: true,
        printButton: true,
        reportCheckbox: true

    },

    legend_type: 'bottom_center',
    font_size: 3,
    swap_xy: false,

    // ------------------------------------------------------------------

    getSelectedResourceIds: function () {

        var resources = [];
        var selNodes = this.resourcesTree.getChecked();

        Ext.each(selNodes, function (node) {
            if (!node.disabled) resources.push(node.id);
        });

        return resources;

    }, //getSelectedResourceIds

    // ------------------------------------------------------------------

    getSelectedPUCounts: function () {

        var nodes = [];
        var selNodes = this.pusTree.getChecked();

        Ext.each(selNodes, function (node) {
            if (!node.disabled) nodes.push(node.id);
        });

        return nodes;

    }, //getSelectedPUCounts

    // ------------------------------------------------------------------

    getExpandedAppKernels: function () {

        var aks = [];

        this.metricsTree.root.cascade(function (node) {

            if (node.isExpanded()) {
                aks.push(node.id);
            }

        }, this);

        return aks;

    }, //getExpandedAppKernels

    // ------------------------------------------------------------------

    getSelectedMetrics: function () {

        var metrics = [];

        var selNodes = this.metricsTree.getChecked();

        Ext.each(selNodes, function (node) {
            if (!node.disabled) metrics.push(node.id);
        });

        return metrics;

    }, //getSelectedMetrics

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        var chartScale = 1;
        var chartThumbScale = 0.45;
        var chartWidth = 740;
        var chartHeight = 345;

        // ---------------------------------------------------------

        self.on('duration_change', function (d) {
            var start_time = self.getDurationSelector().getStartDate() / 1000.0;
            var end_time = self.getDurationSelector().getEndDate() / 1000.0;
            self.metricsTree.getRootNode().cascade(function (n) {
                var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                    (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                if (enabled) n.enable();
                else n.disable();
                //if(!enabled)n.setText(n.text+'*');

                return true;
            });
            reloadResources.call(this);
            reloadChart.call(this, 150);

        }); //self.on('duration_change', ...

        // ---------------------------------------------------------

        var resourcesStore = new CCR.xdmod.CustomJsonStore({

            url: 'controllers/data_explorer.php',

            baseParams: {
                operation: 'get_tree',
                node: 'resources'
            },

            root: 'data',
            fields: ['nodes'],
            totalProperty: 'totalCount',
            messageProperty: 'message',
            scope: this,

            listeners: {

                'load': {

                    fn: function (tstore) {

                        if (tstore.getCount() <= 0) {
                            return;
                        }

                        var root = new Ext.tree.AsyncTreeNode({

                            nodeType: 'async',
                            text: 'Resources',
                            draggable: false,
                            id: 'resources',
                            children: Ext.util.JSON.decode(tstore.getAt(0).get('nodes'))

                        }); //root

                        this.resourcesTree.setRootNode(root);
                        this.resourcesTree.render();
                        root.expand();

                    }, //fn

                    scope: this

                } //load

            } //listeners

        }); //resourcesStore

        // ---------------------------------------------------------

        var getBaseParams = function () {

            var selectedResourceIds = this.getSelectedResourceIds();
            var selectedPUCounts = this.getSelectedPUCounts();
            var selectedMetrics = this.getSelectedMetrics();
            var expandedAppKernels = this.getExpandedAppKernels();

            var baseParams = {};
            baseParams.show_change_indicator = toggleChangeIndicator.pressed ? 'y' : 'n';
            baseParams.show_title = 'n';
            baseParams.start_date = self.getDurationSelector().getStartDate().format('Y-m-d');
            baseParams.end_date = self.getDurationSelector().getEndDate().format('Y-m-d');
            baseParams.selectedResourceIds = selectedResourceIds.join(',');
            baseParams.selectedMetrics = selectedMetrics.join(',');
            baseParams.expandedAppKernels = expandedAppKernels.join(',');
            baseParams.selectedPUCounts = selectedPUCounts.join(',');
            baseParams.active_role = CCR.xdmod.ui.activeRole;
            baseParams.title = this.chartTitleField.getValue();
            baseParams.legend_type = this.legendTypeComboBox.getValue();
            baseParams.font_size = this.fontSizeSlider.getValue();
            baseParams.swap_xy = this.swap_xy;
            baseParams.show_title = 'y';

            return baseParams;

        }; //getBaseParams

        // ---------------------------------------------------------

        var reloadResources = function () {

            Ext.apply(resourcesStore.baseParams, getBaseParams.call(this));
            resourcesStore.load();

        }; //reloadResources

        // ---------------------------------------------------------

        var suppressResourceCheckTrackCall = false;

        var resoucesTreeCheckChange = function (node, checked) {

            if (suppressResourceCheckTrackCall == false)
               XDMoD.TrackEvent('App Kernel Explorer', 'Clicked a checkbox in the Resources tree', Ext.encode({item: node.getPath('text'), checked: checked}));

            reloadChart.call(this);

        }; //resoucesTreeCheckChange

        // ---------------------------------------------------------

        this.resourcesTree = new Ext.tree.TreePanel({

            title: 'Resources',
            id: 'tree_resources_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,
            region: 'north',
            //height: 200,

            root: {
                nodeType: 'async',
                text: 'Resources',
                draggable: false,
                id: 'resources'
            },

            rootVisible: false,
            containerScroll: true,

            tools: [

                {

                    id: 'unselect',
                    qtip: 'Toggle resource selection',
                    scope: this,

                    handler: function () {

                        XDMoD.TrackEvent('App Kernel Explorer', 'Clicked on the uncheck all tool in the Resources tree');
                        suppressResourceCheckTrackCall = true;

                        this.resourcesTree.un('checkchange', resoucesTreeCheckChange, this);
                        var lastNode = null;
                         var selectAll = true;
                        this.resourcesTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if(ui.isChecked()) selectAll=false;
                            lastNode = n;
                         });

                         if(selectAll){
                           XDMoD.TrackEvent('App Kernel Explorer', 'Checking all items in the Resources tree');
                            this.resourcesTree.getRootNode().cascade(function(n) {
                              var ui = n.getUI();
                              if(!ui.isChecked()) ui.toggleCheck(true);
                              lastNode = n;
                            });
                         }
                         else{
                           XDMoD.TrackEvent('App Kernel Explorer', 'Clearing all checked items in the Resources tree');
                            this.resourcesTree.getRootNode().cascade(function(n) {
                               var ui = n.getUI();
                               if(ui.isChecked()) ui.toggleCheck(false);
                               lastNode = n;
                            });
                         }

                        if (lastNode) resoucesTreeCheckChange.call(this, lastNode, false);
                        this.resourcesTree.on('checkchange', resoucesTreeCheckChange, this);

                        suppressResourceCheckTrackCall = false;

                    } //handler

                },

                {

                    id: 'refresh',
                    qtip: 'Refresh',
                    hidden: true,
                    scope: this,
                    handler: reloadResources

                }

            ], //tools

            margins: '0 0 0 0',
            border: false,
            split: true,
            flex: 1.5

        }); //this.resourcesTree

        // ---------------------------------------------------------

        this.resourcesTree.on('checkchange', resoucesTreeCheckChange, this);

        var pusStore = new CCR.xdmod.CustomJsonStore({

            url: 'controllers/data_explorer.php',

            baseParams: {
                operation: 'get_tree',
                node: 'pus'
            },

            root: 'data',
            fields: ['nodes'],
            totalProperty: 'totalCount',
            messageProperty: 'message',
            scope: this,

            listeners: {

                'load': {

                    fn: function (tstore) {

                        if (tstore.getCount() <= 0) {
                            return;
                        }

                        var root = new Ext.tree.AsyncTreeNode({

                            nodeType: 'async',
                            text: 'Processing Units',
                            draggable: false,
                            id: 'nodes',
                            children: Ext.util.JSON.decode(tstore.getAt(0).get('nodes'))

                        }); //root

                        this.pusTree.setRootNode(root);
                        this.pusTree.render();

                        root.expand();

                    }, //fn

                    scope: this

                } //load

            } //listeners

        }); //pusStore

        // ---------------------------------------------------------

        var reloadPUs = function () {

            Ext.apply(pusStore.baseParams, getBaseParams.call(this));
            pusStore.load();

        }; //reloadPUs

        // ---------------------------------------------------------

        var suppressPUsCheckTrackCall = false;

        var pusTreeCheckChange = function (node, checked) {

            if (suppressPUsCheckTrackCall == false)
               XDMoD.TrackEvent('App Kernel Explorer', 'Clicked a checkbox in the Processing Units tree', Ext.encode({item: node.getPath('text'), checked: checked}));

            reloadResources.call(this);
            //reloadMetrics.call(this);
            reloadChart.call(this);

        }; //pusTreeCheckChange

        // ---------------------------------------------------------

        this.pusTree = new Ext.tree.TreePanel({

            flex: 1,
            title: "Processing Units",
            id: 'tree_pus_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,

            root: {
                nodeType: 'async',
                text: 'Processing Units',
                draggable: false,
                id: 'pus'
            },

            rootVisible: false,
            containerScroll: true,

            tools: [

                {

                    id: 'unselect',
                    qtip: 'Toggle processing unit selection',
                    scope: this,

                    handler: function () {

                        XDMoD.TrackEvent('App Kernel Explorer', 'Clicked on the uncheck all tool in the Processing Units tree');
                        suppressPUsCheckTrackCall = true;

                        this.pusTree.un('checkchange', pusTreeCheckChange, this);
                        var lastNode = null;

                         var selectAll = true;

                           this.pusTree.getRootNode().cascade(function(n) {
                              var ui = n.getUI();
                              if(ui.isChecked()) selectAll=false;
                              lastNode = n;
                           });

                           if(selectAll){
                             XDMoD.TrackEvent('App Kernel Explorer', 'Checking all items in the Processing Units tree');
                              this.pusTree.getRootNode().cascade(function(n) {
                                var ui = n.getUI();
                                if(!ui.isChecked()) ui.toggleCheck(true);
                                lastNode = n;
                              });
                           }
                           else{
                             XDMoD.TrackEvent('App Kernel Explorer', 'Clearing all checked items in the Processing Units tree');
                              this.pusTree.getRootNode().cascade(function(n) {
                                 var ui = n.getUI();
                                 if(ui.isChecked()) ui.toggleCheck(false);
                                 lastNode = n;
                              });
                           }

                        if (lastNode) pusTreeCheckChange.call(this, lastNode, false);
                        this.pusTree.on('checkchange', pusTreeCheckChange, this);

                        suppressPUsCheckTrackCall = false;

                    } //handler

                },

                {

                    id: 'refresh',
                    qtip: 'Refresh',
                    scope: this,
                    hidden: true,
                    handler: reloadPUs

                }

            ], //tools

            margins: '0 0 0 0',
            border: false,

            listeners: {

                'checkchange': {
                    fn: pusTreeCheckChange,
                    scope: this
                }

            } //listeners

        }); //this.pusTree

        // ---------------------------------------------------------

        this.pusTree.on('checkchange', pusTreeCheckChange, this);

        var metricsStore = new CCR.xdmod.CustomJsonStore({

            url: 'controllers/data_explorer.php',

            baseParams: {
                operation: 'get_tree',
                node: 'app_kernels'
            },

            root: 'data',
            fields: ['nodes'],
            totalProperty: 'totalCount',
            messageProperty: 'message',
            scope: this,

            listeners: {

                'load': {

                    fn: function (tstore) {

                        if (tstore.getCount() <= 0) {
                            return;
                        }

                        var root = new Ext.tree.AsyncTreeNode({

                            nodeType: 'async',
                            text: 'Metrics',
                            draggable: false,
                            id: 'app_kernels',
                            children: Ext.util.JSON.decode(tstore.getAt(0).get('nodes'))

                        }); //root

                        this.metricsTree.setRootNode(root);
                        this.metricsTree.render();
                        root.expand();

                    }, //fn

                    scope: this

                }
            } //listeners

        }); //metricsStore

        // ---------------------------------------------------------

        var reloadMetrics = function () {

            Ext.apply(metricsStore.baseParams, getBaseParams.call(this));
            metricsStore.load();

        }; //reloadMetrics

        // ---------------------------------------------------------

        var suppressMetricCheckTrackCall = false;

        var metricsTreeCheckChange = function (node, checked) {

            if (suppressMetricCheckTrackCall == false)
               XDMoD.TrackEvent('App Kernel Explorer', 'Clicked a checkbox in the Metrics tree', Ext.encode({item: node.getPath('text'), checked: checked}));

            reloadResources.call(this);
            //reloadPUs.call(this);
            reloadChart.call(this);

        }; //metricsTreeCheckChange

        // ---------------------------------------------------------

        this.metricsTree = new Ext.tree.TreePanel({

            split: true,
            title: 'Metrics',
            region: 'center',

            id: 'tree_metrics_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,

            root: {

                nodeType: 'async',
                text: 'Metrics',
                draggable: false,
                id: 'app_kernels'

            }, //root

            rootVisible: false,
            containerScroll: true,

            tools: [

                {

                    id: 'unselect',
                    qtip: 'Toggle metric selection',
                    scope: this,

                    handler: function () {

                        XDMoD.TrackEvent('App Kernel Explorer', 'Clicked on the uncheck all tool in the Metrics tree');
                        suppressMetricCheckTrackCall = true;

                        this.metricsTree.un('checkchange', metricsTreeCheckChange, this);
                        var lastNode = null;

                        var selectAll = true;

                        this.metricsTree.getRootNode().cascade(function(n) {
                         var ui = n.getUI();
                         if(ui.isChecked()) selectAll=false;
                         lastNode = n;
                        });

                        if(selectAll){
                         XDMoD.TrackEvent('App Kernel Explorer', 'Checking all (rendered) items in Metrics tree');
                         this.metricsTree.getRootNode().cascade(function(n) {
                           var ui = n.getUI();
                           if(!ui.isChecked()) ui.toggleCheck(true);
                           lastNode = n;
                         });
                        }
                        else{
                         XDMoD.TrackEvent('App Kernel Explorer', 'Clearing all (rendered) checked items in Metrics tree');
                         this.metricsTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if(ui.isChecked()) ui.toggleCheck(false);
                            lastNode = n;
                         });
                        }
                        if (lastNode) metricsTreeCheckChange.call(this, lastNode, false);
                        this.metricsTree.on('checkchange', metricsTreeCheckChange, this);
                        suppressMetricCheckTrackCall = false;

                    } //handler

                },

                {

                    id: 'refresh',
                    qtip: 'Refresh',
                    scope: this,
                    hidden: true,
                    handler: reloadMetrics

                }

            ], //tools

            margins: '0 0 0 0',
            border: false,

            listeners: {

                'checkchange': {
                    fn: metricsTreeCheckChange,
                    scope: this
                },

                beforeappend: {
                    fn: function (t, p, n) {
                        var start_time = self.getDurationSelector().getStartDate() / 1000.0;
                        var end_time = self.getDurationSelector().getEndDate() / 1000.0;
                        var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                            (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                        if (enabled) n.enable();
                        else n.disable();
                    },
                    scope: this
                },

               expandnode: function(n) {

                  XDMoD.TrackEvent('App Kernel Explorer', 'Expanded item in Metrics tree', n.getPath('text'));

               },//expandnode

               collapsenode: function(n) {

                  XDMoD.TrackEvent('App Kernel Explorer', 'Collapsed item in Metrics tree', n.getPath('text'));

               }//collapsenode

            }, //listeners

            flex: 5

        }); //this.metricsTree

        // ---------------------------------------------------------

        this.metricsTree.on('checkchange', metricsTreeCheckChange, this);

        this.chartTitleField = new Ext.form.TextField({

            fieldLabel: 'Title',
            name: 'title',
            emptyText: 'Chart Title',
            validationDelay: 1000,
            enableKeyEvents: true,

            listeners: {

                scope: this,

                change: function (t, n, o) {

                    if (n != o) {

                        XDMoD.TrackEvent('App Kernel Explorer', 'Updated chart title', t.getValue());
                        reloadChart.call(this);

                    }

                }, //change

                specialkey: function (t, e) {

                    if (t.isValid(false) && e.getKey() == e.ENTER) {

                        //XDMoD.TrackEvent('App Kernel Explorer', 'Updated chart title', t.getValue());
                        reloadChart.call(this);

                    }

                } //specialkey

            } //listeners

        }); //this.chartTitleField

        // ---------------------------------------------------------

        this.legendTypeComboBox = new Ext.form.ComboBox({

            fieldLabel: 'Legend',
            name: 'legend_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,

            store: new Ext.data.ArrayStore({

                id: 0,

                fields: [
                    'id',
                    'text'
                ],

                data: [

                    ['top_center', 'Top Center'],
                    ['bottom_center', 'Bottom Center (Default)'],
                    ['left_center', 'Left'],
                    ['left_top', 'Top Left'],
                    ['left_bottom', 'Bottom Left'],
                    ['right_center', 'Right'],
                    ['right_top', 'Top Right'],
                    ['right_bottom', 'Bottom Right'],
                    ['floating_top_center', 'Floating Top Center'],
                    ['floating_bottom_center', 'Floating Bottom Center'],
                    ['floating_left_center', 'Floating Left'],
                    ['floating_left_top', 'Floating Top Left'],
                    ['floating_left_bottom', 'Floating Bottom Left'],
                    ['floating_right_center', 'Floating Right'],
                    ['floating_right_top', 'Floating Top Right'],
                    ['floating_right_bottom', 'Floating Bottom Right'],
                    ['off', 'Off']

                ]

            }), //store

            disabled: false,
            value: this.legend_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',

            listeners: {

                scope: this,

                'select': function (combo, record, index) {

                    XDMoD.TrackEvent('App Kernel Explorer', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));

                    this.legend_type = record.get('id');
                    reloadChart.call(this);

                } //select

            } //listeners

        }); //this.legendTypeComboBox

        // ---------------------------------------------------------

        this.fontSizeSlider = new Ext.slider.SingleSlider({

            fieldLabel: 'Font Size',
            name: 'font_size',
            minValue: -5,
            maxValue: 10,
            value: this.font_size,
            increment: 1,

            plugins: new Ext.slider.Tip(),

            listeners: {

                scope: this,

                'change': function (t, n, o) {

                    XDMoD.TrackEvent('App Kernel Explorer', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    reloadChart.call(this);

                } //change

            } //listeners

        }); //this.fontSizeSlider
        
        // ---------------------------------------------------------

        this.northPanel = new Ext.Panel({

            layout: 'hbox',
            margins: '0 0 0 0',
            border: false,

            layoutConfig: {
                align: 'stretch'
            },

            items: [this.resourcesTree, this.pusTree],
            flex: 4

        }); //this.northPanel

        // ---------------------------------------------------------

        var leftPanel = new Ext.Panel({

            split: true,
            bodyStyle: 'padding:5px 5px ;',
            collapsible: true,
            header: true,
            title: 'Query Options',
            autoScroll: true,
            width: 375,
            margins: '2 0 2 2',
            border: true,
            region: 'west',
            layout: 'border',

            plugins: new Ext.ux.collapsedPanelTitlePlugin('Query Options'),

            items: [

                {

                    xtype: 'form',
                    layout: 'fit',
                    region: 'north',
                    height: 100,
                    border: false,

                    items: [{

                        xtype: 'fieldset',
                        header: false,
                        layout: 'form',
                        hideLabels: false,
                        border: false,

                        defaults: {
                            anchor: '0'
                        },

                        items: [

                            this.chartTitleField,
                            this.legendTypeComboBox,
                            this.fontSizeSlider

                        ]

                    }] //items

                },

                {

                    region: 'center',
                    xtype: 'panel',

                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },

                    border: false,

                    items: [
                        this.metricsTree,
                        this.northPanel
                    ]

                }

            ] //items

        }); //leftPanel

        // ---------------------------------------------------------

        var chartStore = new CCR.xdmod.CustomJsonStore({

            storeId: 'chart_store_' + this.id,
            autoDestroy: false,
            root: 'data',
            totalProperty: 'totalCount',
            successProperty: 'success',
            messageProperty: 'message',

            fields: [
                'chart',
                'credits',
                'title',
                'subtitle',
                'xAxis',
                'yAxis',
                'tooltip',
                'legend',
                'series',
                'plotOptions',
                'credits',
                'dimensions',
                'metrics',
                'exporting',
                'reportGeneratorMeta'
            ],

            baseParams: {
                operation: 'get_ak_plot'
            },

            proxy: new Ext.data.HttpProxy({

                method: 'POST',
                url: 'controllers/data_explorer.php'

            }) //proxy

        }); //chartStore

        // ---------------------------------------------------------

        chartStore.on('beforeload', function () {

            if (!self.getDurationSelector().validate()) return;
            highChartPanel.un('resize', onResize, this);

            maximizeScale.call(this);
            Ext.apply(chartStore.baseParams, getBaseParams.call(this));

            chartStore.baseParams.timeframe_label = self.getDurationSelector().getDurationLabel(),
            chartStore.baseParams.show_guide_lines = 'y';
            chartStore.baseParams.scale = 1;
            chartStore.baseParams.format = 'hc_jsonstore';
            chartStore.baseParams.width = chartWidth * chartScale;
            chartStore.baseParams.height = chartHeight * chartScale;

            chartStore.baseParams.controller_module = self.getReportCheckbox().getModule();

            // While we're still pre-load make sure to mask the appropriate
            // component so that the User knows that we're retrieving data.
            view.el.mask('Loading...');
        }, this); //chartStore.on('beforeload'

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {
            // Now that we're done loading, make sure to unmask the appropriate
            // component so that the User knows that we're done.
            view.el.unmask();

            // Ensure that we unmask the main interface once we're done loading
            // too.
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) {
                viewer.el.unmask();
            }

            if (chartStore.getCount() != 1) {
                return;
            }

            var selectedResourceIds = this.getSelectedResourceIds();
            var selectedMetrics = this.getSelectedMetrics();
            var noData = selectedResourceIds.length === 0 || selectedMetrics.length === 0;

            chartViewPanel.getLayout().setActiveItem(noData ? 1 : 0);

            self.getExportMenu().setDisabled(noData);
            self.getPrintButton().setDisabled(noData);
            self.getReportCheckbox().setDisabled(noData);

            var reportGeneratorMeta = chartStore.getAt(0).get('reportGeneratorMeta');

            self.getReportCheckbox().storeChartArguments(reportGeneratorMeta.chart_args,
                reportGeneratorMeta.title,
                reportGeneratorMeta.params_title,
                reportGeneratorMeta.start_date,
                reportGeneratorMeta.end_date,
                reportGeneratorMeta.included_in_report);

            highChartPanel.on('resize', onResize, this);
        }, this); //chartStore.on('load'

        // ---------------------------------------------------------

        var reloadChartFunc = function () {
            chartStore.load();
        };

        var reloadChartTask = new Ext.util.DelayedTask(reloadChartFunc, this);

        var reloadChart = function (delay) {
            reloadChartTask.delay(delay || 2000);
        };

        var chartViewTemplate = new Ext.XTemplate(
            '<tpl for=".">',
            '<center>',
            '<map id="Map{random_id}" name="Map{random_id}">{chart_map}</map>',
            '<img class="xd-img" src="{chart_url}" usemap="#Map{random_id}"/>',
            '</center>',
            '</tpl>'
        );

        // ---------------------------------------------------------

        var assistPanel = new CCR.xdmod.ui.AssistPanel({

            region: 'center',
            border: false,
            headerText: 'No data is available for viewing',
            subHeaderText: 'Please refer to the instructions below:',
            graphic: 'gui/images/ak_explorer_instructions.png',
            userManualRef: 'app+kernel+explorer'

        }); //assistPanel

        // ---------------------------------------------------------

        var highChartPanel = new CCR.xdmod.ui.HighChartPanel({

            id: 'hc-panel' + this.id,
            store: chartStore

        }); //highChartPanel

        // ---------------------------------------------------------

        var chartViewPanel = new Ext.Panel({

            frame: false,
            layout: 'card',
            activeItem: 1,

            region: 'center',

            border: false,

            items: [
                highChartPanel,
                assistPanel
            ]

        }); //chartViewPanel

        // ---------------------------------------------------------

        //self.getDurationSelector().dateSlider.region = 'south';

        var datasheetTab = new Ext.Panel({
            title: 'Datasheet'
        });

        var detailsTab = new Ext.Panel({
            title: 'Details'
        });

        var glossaryTab = new Ext.Panel({
            title: 'Glossary'
        });

        var southTabPanel = new Ext.TabPanel({

            activeTab: 0,
            region: 'center',
            items: [datasheetTab, detailsTab, glossaryTab]

        }); //southTabPanel

        // ---------------------------------------------------------

        var southView = new Ext.Panel({

            hideTitle: true,
            split: true,
            collapsible: true,
            header: false,
            collapseMode: 'mini',
            region: 'south',
            height: 250,
            layout: 'border',
            items: [southTabPanel]

        }); //southView

        // ---------------------------------------------------------

        var view = new Ext.Panel({

            region: 'center',
            layout: 'border',
            margins: '2 2 2 0',
            border: true,
            items: [chartViewPanel]

        }); //view

        // ---------------------------------------------------------

        self.on('print_clicked', function () {

            var parameters = chartStore.baseParams;

            parameters.scale = 1;
            parameters.format = 'png';
            parameters.width = 757 * 2;
            parameters.height = 400 * 2;

            var params = '';

            for (i in parameters) {
                params += i + '=' + parameters[i] + '&';
            }

            params = params.substring(0, params.length - 1);

            Ext.ux.Printer.print({

                getXTypes: function () {
                    return 'html';
                },
                html: '<img src="/controllers/data_explorer.php?' + params + '" />'

            });

        }); //self.on('print_clicked',...

        // ---------------------------------------------------------

        self.on('export_option_selected', function (opts) {

            var parameters = chartStore.baseParams;

            Ext.apply(parameters, opts);

            CCR.invokePost("controllers/data_explorer.php", parameters);

        }); //self.on('export_option_selected', …

        // ---------------------------------------------------------

        var toggleChangeIndicator = new Ext.Button({

            text: 'Change Indicators',
            enableToggle: true,
            scope: this,
            iconCls: 'exclamation',

            toggleHandler: function (but, state) {
                reloadChart.call(this, 1);
            },

            pressed: true,
            tooltip: 'When this option is checked, each app kernel data series plot will show an exclamation point icon whenever a change has occurred in the execution environment of the app kernel (library version, compiler version, etc).'

        }); //toggleChangeIndicator

        // ---------------------------------------------------------

        function reloadAll() {

            reloadResources.call(this);
            reloadPUs.call(this);
            reloadMetrics.call(this);
            reloadChart.call(this, 150);

        } //reloadAll

        // ---------------------------------------------------------

        this.on('render', reloadAll, this, {
            single: true
        });

        // ---------------------------------------------------------

        function maximizeScale() {

            chartWidth = chartViewPanel.getWidth();
            chartHeight = chartViewPanel.getHeight() - (chartViewPanel.tbar ? chartViewPanel.tbar.getHeight() : 0);

        } //maximizeScale

        // ---------------------------------------------------------

        function onResize(t) {

            maximizeScale.call(this);

        } //onResize

        // ---------------------------------------------------------

        Ext.apply(this, {

            customOrder: [

                XDMoD.ToolbarItem.DURATION_SELECTOR,
                XDMoD.ToolbarItem.EXPORT_MENU,
                XDMoD.ToolbarItem.PRINT_BUTTON,
                toggleChangeIndicator,
                XDMoD.ToolbarItem.REPORT_CHECKBOX

            ],

            items: [leftPanel, view]

        }); //Ext.apply

        XDMoD.Module.AppKernels.AppKernelExplorer.superclass.initComponent.apply(this, arguments);

    } //initComponent

}); //XDMoD.Module.AppKernels.AppKernelExplorer
