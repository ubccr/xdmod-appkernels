/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Feb-07 (version 1)
*
* @author Ryan Gentner
* @date 2013-Jun-23 (version 2)
*
* This class contains functionality for the App Kernels tab.
*
*/
/*XDMoD.Arr.ControlRegionsPanel = function (config) {

    XDMoD.Arr.ControlRegionsPanel.superclass.constructor.call(this, config);

}; //XDMoD.Module.AppKernelViewer

// ===========================================================================

//Add public static methods to the AppKernelViewer class
Ext.apply(XDMoD.Arr.ControlRegionsPanel, {


}); //Ext.apply(XDMoD.Module.AppKernelViewer, ...
*/
Ext.namespace('XDMoD', 'XDMoD.Arr', 'CCR');
Ext.QuickTips.init();
//Ext.form.Field.prototype.msgTarget = 'under';


// ===========================================================================
//Ext.define('XDMoD.Arr.ControlRegionsPanel2', {});
//Ext.define('XDMoD.Arr.ControlRegionsPanel',

XDMoD.Arr.ControlRegionsPanel=Ext.extend(XDMoD.PortalModule,
{
    /* called from thumbnail view
     * When a user clicks on thumbnail of a app kernel chart, this function will find
     * the sub node, expand the path to it and then select it so that the chart view
     * will change to view the selected app kernel chart.
     */
    id:'ak_control_regions',

    selectChildAppKernelChart: function (metric_id, resource_id, kernel_id) {

        if (metric_id == -1 || resource_id == -1 || kernel_id == -1) return;

        var viewer = images;

        if (viewer.el) viewer.el.mask('Loading...');

        var tree = Ext.getCmp('tree_app_kernels');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var nn = tree.getSelectionModel().getSelectedNode();

        if (!nn) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tree.expandPath(nn.getPath(), null, function (success, node) {

            if (!success) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            if (node.attributes.type == 'appkernel' && node.attributes.ak_id == kernel_id) {

                var nodeToExpand = node.findChild('resource_id', resource_id);

                tree.expandPath(nodeToExpand.getPath(), null, function (success2, node2) {

                    if (!success2) {

                        if (viewer.el) viewer.el.unmask();
                        return;

                    }

                    var nodeToSelect = node2.findChild('metric_id', metric_id, true);

                    if (!nodeToSelect) {

                        if (viewer.el) viewer.el.unmask();
                        return;

                    }

                    tree.getSelectionModel().select(nodeToSelect);

                }); //tree.expandPath(nodeToExpand...

            } else if (node.attributes.type == 'resource' && node.attributes.resource_id == resource_id) {

                var nodeToSelect = node.findChild('metric_id', metric_id, true);

                if (!nodeToSelect) {

                    if (viewer.el) viewer.el.unmask();
                    return;

                }

                tree.getSelectionModel().select(nodeToSelect);

            } else {
                if (viewer.el) viewer.el.unmask();
            }

        }); //tree.expandPath(nn)
    }, //selectChildAppKernelChart

    // ------------------------------------------------------------------

    /*
     * When a user clicks a data series pertaining to the number of processing units on a app kernel chart,
     * this call will expand the node in the tree and select the sub node representing the chart for the
     * selectected number of processing units.
     */

    selectChildUnitsChart: function (num_units) {

        var viewer = this;

        if (viewer.el) viewer.el.mask('Loading...');

        var tree = Ext.getCmp('tree_app_kernels');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var nn = tree.getSelectionModel().getSelectedNode();

        if (!nn) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tree.expandPath(nn.getPath(), null, function (success, node) {

            if (!success) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            var nodeToSelect = node.findChild('num_proc_units', num_units, true);

            if (!nodeToSelect) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            tree.getSelectionModel().select(nodeToSelect);

        }); //tree.expandPath(nn

    }, //selectChildUnitsChart

    // ------------------------------------------------------------------

    /*
     * This function can be used to change the selected tab to app kernel tab and select the app kernel node
     * indicated by kernel_id.
     */

    gotoAppKernel: function (kernel_id) {

        var tabPanel = Ext.getCmp('main_tab_panel');

        if (!tabPanel) return;

        tabPanel.setActiveTab('app_kernels');

        var tree = Ext.getCmp('tree_app_kernels');
        if (!tree) return;

        var root = tree.getRootNode();

        tree.expandPath(root.getPath(), null, function (success, node) {

            if (!success) return;

            var kernelNode = node.findChild('ak_id', kernel_id);
            tree.getSelectionModel().select(kernelNode);

        });

    }, //gotoAppKernel
    selectAppKernel: function(kernel_id) {
        var tree = Ext.getCmp('tree_ak_control_regions');
        if (!tree) return;

        var root = tree.getRootNode();
        var node_ids=null;

        if(kernel_id!==null)node_ids=kernel_id.split("_");
        var ak_id=null;
        var resource_id=null;

        if(kernel_id!==null){
            if(node_ids.length>=1)resource_id=node_ids[0];
            if(node_ids.length>=2)ak_id=node_ids[1];
        }

        tree.expandPath(root.getPath(), null, function (success, node) {
            if (!success) return;

            var resourceNode = null;
            if(resource_id){
                resourceNode=node.findChild('resource_id', resource_id);
            }else{
                var i;
                for(i=0;i<node.childNodes.length;i++){
                    if(node.childNodes[i].disabled===false){
                        resourceNode=node.childNodes[i];
                        break;
                    }

                }
            }
            if(!resourceNode) return;

            if(ak_id){
                tree.expandPath(resourceNode.getPath(), null, function (success, node) {
                    if (!success) return;

                    var akNode = node.findChild('ak_id', ak_id);
                    if(!akNode) return;

                    tree.getSelectionModel().select(akNode);
                });
            }else{
                tree.getSelectionModel().select(resourceNode);
            }
        });
    },
    module_id: 'control_regions',
    title: 'Control Regions',
    usesToolbar: true,

    toolbarItems: {

        durationSelector: {

            enable: true,

            config: {
                showAggregationUnit: false
            }

        }, //durationSelector

        exportMenu: true,
        printButton: true

    },

    legend_type: 'bottom_center',
    font_size: 3,
    swap_xy: false,

    showDateChooser: true,

    chartDataFields: [

        'hc_jsonstore',
        'title',
        'start_date',
        'end_date',
        'random_id',
        'comments',
        'resource_description',
        'ak_id',
        'resource_id',
        'metric_id',
        'ak_name',
        'resource_name',
        'format',
        'scale',
        'width',
        'height',
        'final_width',
        'final_height',
        'show_guide_lines', {
            name: 'short_title',
            mapping: 'short_title',
            convert: CCR.xdmod.ui.shortTitle
        }

    ], //chartDataFields

    largeTemplate: [

        '<tpl for=".">',
        //'<center>',
        //    '<h2>{title}</h2>',
        //    '<span class="date_range">{start_date} to {end_date}</span>',
        //'</center>',
        //'<br />',
        '<center>',
        '<div id="{random_id}">', '</div>',
        '</center>',
        '</tpl>'

    ], //largeTemplate

    thumbTemplate: [

        '<tpl for=".">',
        '<div class="chart_thumb-wrap2" id="{ak_id}{resource_id}{metric_id}">',
        '<span class="ak_thumb_title">{ak_name}: {resource_name}</span>',

        '<div class="chart_thumb">',
        //'<a href="javascript:XDMoD.Module.AppKernelViewer.selectChildAppKernelChart({metric_id},{resource_id},{ak_id});">',

        '<div id="{random_id}">',
        '</div>',

        '</a>',
        '</div>',
        '<span class="ak_thumb_subtitle">{short_title}</span>',
        '</div>',
        '</tpl>'

    ],
    CCR_xdmod_ui_thumbAspect:0.6,
    CCR_xdmod_ui_thumbWidth:600,
    resource_id:null,
    ak_def_id:null,

    chartScale : 1,
    chartThumbScale : CCR.xdmod.ui.thumbChartScale,
    chartWidth : 740,
    chartHeight : 345,

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        this.hiddenCharts = [];
        var layoutId = this.id;



        var treeTb = new Ext.Toolbar({

            items: [

                '->',

                {
                    iconCls: 'icon-collapse-all',
                    tooltip: 'Collapse All',
                    handler: function () {
                        XDMoD.TrackEvent('App Kernels', 'Clicked on Collapse All button above tree panel');
                        tree.root.collapse(true);
                    },
                    scope: this
                },

                {
                    iconCls: 'refresh',
                    tooltip: 'Refresh tree and clear drilldown nodes',

                    handler: function () {

                        XDMoD.TrackEvent('App Kernels', 'Clicked on Refresh button above tree panel');

                        var selModel = tree.getSelectionModel();
                        var selNode = selModel.getSelectedNode();

                        if (selNode) selModel.unselect(selNode, true);
                        tree.root.removeAll(true);
                        tree.loader.on('load', selectFirstNode, this, {
                            single: true
                        });
                        tree.loader.load(tree.root);

                    },

                    scope: this
                }

            ] //items

        }); //treeTb

        // ---------------------------------------------------------

        var tree = new Ext.tree.TreePanel({

            loader: new XDMoD.REST.TreeLoader({
                url: 'app_kernels/details',
                requestMethod: 'GET',
                listeners: {
                    beforeload: function (loader, node, callback) {
                        loader.baseParams = XDMoD.REST.removeEmptyParameters({
                            ak: node.attributes.ak_id,
                            resource: node.attributes.resource_id,
                            metric: node.attributes.metric_id,
                            num_proc_units: node.attributes.num_proc_units,
                            collected: node.attributes.collected,
                            resource_first:true
                        });
                    },
                    load: XDMoD.REST.TreeLoader.prototype.createStandardLoadListener(),
                    loadexception: function (loader, node, response) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'App Kernels',
                            wrapperMessage: 'The tree node failed to load.'
                        });
                    }
                }
            }),

            listeners: {

                'beforeappend': function (t, p, n) {
                    n.setIconCls(n.attributes.type);
                    var start_time = self.getDurationSelector().getStartDate() / 1000.0;
                    var end_time = self.getDurationSelector().getEndDate() / 1000.0;
                    var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                        (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                    if (enabled) n.enable();
                    else n.disable();
                    //we need only resource/app kernel
                    if (typeof p.isRoot  === 'undefined')
                        n.leaf=true;
                },

               expandnode: function(n) {

                  XDMoD.TrackEvent('App Kernels', 'Expanded item in tree panel', n.getPath('text'));

               },//expandnode

               collapsenode: function(n) {

                  XDMoD.TrackEvent('App Kernels', 'Collapsed item in tree panel', n.getPath('text'));

               }//collapsenode

            },

            root: {
                nodeType: 'async',
                text: this.title,
                draggable: false,
                id: 'app_kernels'
            },

            id: 'tree_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: true,
            enableDD: false,

            rootVisible: false,
            tbar: treeTb,

            //collapsible: true,
            //width: 325,
            //split: true,
            header: false,
            //title: this.title,
            // margins: '2 0 2 2',
            containerScroll: true,
            border: false,
            region: 'center'

        }); //tree

        // ---------------------------------------------------------

        function selectFirstNode() {

            var node = tree.getSelectionModel().getSelectedNode();
            if (node) return;

            var root = tree.getRootNode();

            if (root.hasChildNodes()) {

                var child = root.item(0);
                tree.getSelectionModel().select(child);
            }

        } //selectFirstNode

        /*tree.loader.on('load', selectFirstNode, this, {
            buffer: 500,
            single: true
        });*/

        // ---------------------------------------------------------

        function updateDescriptionLarge(s, showResource) {

            var data = {
                comments: s.getAt(0).get('comments'),
                resource_name: s.getAt(0).get('resource_name'),
                resource_description: s.getAt(0).get('resource_description')
            };

            //if (showResource)
            //    commentsTemplateWithResource.overwrite(commentsPanel.body, data);
            //else
            //   commentsTemplateWithoutResource.overwrite(commentsPanel.body, data);

        } //updateDescriptionLarge

        // ---------------------------------------------------------



        // ---------------------------------------------------------

        function onSelectNode(model, n) {
            model = tree.getSelectionModel();
            n = model.getSelectedNode();

            if (!n || n.disabled) {
                tree.getRootNode().eachChild(function (nn) {
                    if (!nn.disabled) {
                        tree.getSelectionModel().select(nn);
                        return false;
                    }
                    return true;
                });
                return;
            }

            if (!self.getDurationSelector().validate()) return;

            if (typeof n.parentNode.isRoot  !== 'undefined'){
                return;
            }

            this.store.load({params: {resource_id: n.attributes.resource_id,ak_def_id:n.attributes.ak_id}});
            this.resource_id=n.attributes.resource_id;
            this.ak_def_id=n.attributes.ak_id;

            //Ext.History.un('change', onHistoryChange);
            var token = 'appkernels:ak_control_regions?kernel=' + n.attributes.resource_id+"_"+n.attributes.ak_id;
            Ext.History.add(token, true);
            //Ext.History.on('change', onHistoryChange);

            images.setTitle(n.getPath('text'));

            /*if (n.attributes.type == 'units')
                toggleControlPlot.show();
            else
                toggleControlPlot.hide();*/

            var isChart = n.attributes.type == 'units' || n.attributes.type == 'metric';
            var isMenu = n.attributes.type == 'resource' || n.attributes.type == 'appkernel';

            //XDMoD.TrackEvent('App Kernels', 'Selected ' + n.attributes.type + ' From Tree', n.getPath('text'));

            if (isChart || isMenu) {

                var viewer = this;
                //if (viewer.el) viewer.el.mask('Loading...');
                self.getDurationSelector().disable();

                if (isChart) {
                    view.tpl = largeChartTemplate;
                } else if (isMenu) {
                    view.tpl = thumbnailChartTemplate;
                }

                var parameters = this.getParameters.call(this, n);

                if (isChart) {
                    view.tpl = largeChartTemplate;
                } else if (isMenu) {
                    view.tpl = thumbnailChartTemplate;
                }

                chartStore.load({
                    params: parameters
                });

            } else {

                if (viewer.el) viewer.el.unmask();

            }

        } //onSelectNode
        this.addEvents("reloadControlRegionsRep");
        this.on("reloadControlRegionsRep",onSelectNode,this);




        var thumbnailChartTemplate = new Ext.XTemplate(this.thumbTemplate);

        var largeChartTemplate = new Ext.XTemplate(this.largeTemplate);

        // ---------------------------------------------------------

        var chartStore = new Ext.data.JsonStore({

            plotlyPanels: [],
            storeId: 'Performance',
            autoDestroy: false,
            root: 'results',
            totalProperty: 'num',
            successProperty: 'success',
            messageProperty: 'message',
            fields: this.chartDataFields,

            proxy: XDMoD.REST.createHttpProxy({
                url: 'app_kernels/plots',
                method: 'GET'
            })

        }); //chartStore

        // ---------------------------------------------------------

        chartStore.on('exception', function (dp, type, action, opt, response, arg) {

            CCR.xdmod.ui.presentFailureResponse(response);

            var viewer = this;
            if (viewer.el) viewer.el.unmask();

        }, this); //chartStore.on('exception', …

        // ---------------------------------------------------------

        chartStore.on('beforeload', function () {

            if (!self.getDurationSelector().validate()) return;
            maximizeScale.call(this);
            view.un('resize', onResize, this);

        }, this);

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {

            var model = tree.getSelectionModel();
            var n = model.getSelectedNode();

            if (!n) return;

            var isChart = n.attributes.type == 'units' || n.attributes.type == 'metric';
            var isMenu = n.attributes.type == 'resource' || n.attributes.type == 'appkernel';

            if (isChart && this.chart) {

                delete this.chart;
                this.chart = null;

            }

            if (isMenu) {

                if (this.charts) {

                    for (var i = 0; i < this.charts.length; i++)
                        delete this.charts[i];

                    delete this.charts;

                } //if(this.charts)

                this.charts = [];

            } //if(isMenu)

            updateDescriptionLarge(chartStore, n.attributes.type != 'appkernel');

            //XDMoD.TrackEvent('App Kernels', 'Loaded AK Data', n.getPath('text'));

            var tool = images.getTool('restore');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            tool = images.getTool('plus');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            tool = images.getTool('minus');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            var viewer = this;
            if (viewer.el) viewer.el.unmask();

            var ind = 0;

            chartStore.each(function (r) {

                var id = r.get('random_id');
                var el = Ext.get(id); // Get Ext.Element object

                var task = new Ext.util.DelayedTask(function () {

                    var baseChartOptions = {
                        renderTo: id,
                        layout: {
                            width: isMenu ? CCR.xdmod.ui.thumbWidth * this.chartThumbScale : this.chartWidth * this.chartScale,
                            height: isMenu ? CCR.xdmod.ui.thumbHeight * this.chartThumbScale : this.chartHeight * this.chartScale
                        },
                        credits: {
                            enabled: true
                        }
                    }; //baseChartOptions

                    var chartOptions = r.get('hc_jsonstore');
                    chartOptions = XDMoD.utils.deepExtend({}, chartOptions, baseChartOptions);

                    chartOptions.layout.hovermode = chartOptions.swapXY ? 'y unified' : 'x unified';
                    if (chartOptions.data && chartOptions.data.length !== 0) {
                        chartOptions.data[0].hovertemplate = `${chartOptions.data[0].name}: <b>%{y:,}</b> <extra></extra>`;
                    }
                    if (isMenu) {
                        chartOptions.layout.thumbnail = true;
                        chartOptions.layout.margin = {
                            t: 20,
                            l: 5,
                            r: 5,
                            b: 35
                        };
                        for (let j = 0; j < chartOptions.layout.images.length; j++) {
                            chartOptions.layout.images[j].sizex *= 2;
                            chartOptions.layout.images[j].sizey = 40;
                        }
                        this.charts.push(XDMoD.utils.createChart(chartOptions));
                    } else {
                        this.chart = XDMoD.utils.createChart(chartOptions);
                        this.chart.id = id;
                    }

                    const chartDiv = document.getElementById(id);
                    chartDiv.on('plotly_click', (evt) => {
                        if (evt.points && evt.points.length !== 0) {
                            // Drilldowns exist when hovermode = 'closest' so theres only one point
                            let xValue;
                            const needle = evt.points[0].y;
                            const haystack = evt.points[0].data.seriesData;
                            for (let j = 0; j < haystack.length; j++) {
                                if (haystack[j].y === needle) {
                                    xValue = haystack[j].x;
                                    break;
                                }
                            }
                            if (xValue) {
                                self.contextMenuOnClick({ x: xValue });
                            }
                        }
                    });

                }, this); //task

                task.delay(0);

                return true;

            }, this); //chartStore.each(...

            view.on('resize', onResize, this);

            if (isMenu)
                self.getExportMenu().disable();
            else
                self.getExportMenu().enable();

            self.getDurationSelector().enable();

        }, this); //chartStore.on('load',...

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {

            var tool = images.getTool('refresh');
            if (tool) tool.show();

            tool = images.getTool('plus');
            if (tool) tool.show();

            tool = images.getTool('minus');
            if (tool) tool.show();

            tool = images.getTool('print');
            if (tool) tool.show();

            tool = images.getTool('restore');
            if (tool) tool.show();

        }, chartStore, {
            single: true
        });

        // ---------------------------------------------------------

        chartStore.on('clear', function (chartStore) {

            var tool = images.getTool('refresh');
            if (tool) tool.hide();

            tool = images.getTool('plus');
            if (tool) tool.hide();

            tool = images.getTool('minus');
            if (tool) tool.hide();

            tool = images.getTool('print');
            if (tool) tool.hide();

            tool = images.getTool('restore');
            if (tool) tool.hide();

        }, chartStore, {
            single: true
        });

        // ---------------------------------------------------------

        var view = new Ext.DataView({

            loadingText: "Loading...",
            itemSelector: 'chart_thumb-wrap',
            style: 'overflow:auto',
            multiSelect: true,
            store: chartStore,
            autoScroll: true,
            tpl: largeChartTemplate

        }); //view
        this.view=view;

        // ---------------------------------------------------------

        var viewPanel = new Ext.Panel({

            layout: 'fit',
            region: 'center',
            items: view,
            border: true

        }); //viewPanel

        // ---------------------------------------------------------
        // controlRegionsManipulator

        this.store = new Ext.data.JsonStore(
        {
            //storeId: 'ControlRegions',
            //autoDestroy: false,
            root: 'results',
            totalProperty: 'count',
            successProperty: 'success',
            idProperty: 'control_region_def_id',
            fields: [
                {name: 'control_region_def_id', type: 'string'},
                {name: 'resource_id', type: 'string'},
                {name: 'ak_def_id', type: 'string'},
                {name: 'control_region_type', type: 'string'},
                {name: 'control_region_starts', type: 'string'},
                {name: 'control_region_ends', type: 'string'},
                {name: 'control_region_points', type: 'string'},
                {name: 'comment', type: 'string'}
            ],
            proxy: XDMoD.REST.createHttpProxy({
                url: 'app_kernels/control_regions',
                method: 'GET'
            })
        });

        var controlRegionsTimeFrameManipulatorToolbar = new Ext.Toolbar({
            defaultType:'button',
            items: [
                {
                    iconCls: 'add',
                    text: 'New Control Region',
                    scope: this,
                    handler: self.onAddNewControlRegionTimeFrame
                },{
                    iconCls: 'edit_data',
                    text: 'Modify Region',
                    scope: this,
                    handler: self.onUpdateControlRegionTimeFrame
                }, {
                    iconCls: 'delete',
                    text: 'Delete Control Region(s)',
                    scope: this,
                    handler: self.onDeleteControlRegion
                }
            ]
        });

        this.controlRegionsTimeFrameManipulatorGridPanel = new Ext.grid.GridPanel({
            id: 'controlRegionsTimeFrameManipulatorGridPanel',
            region: 'center',
            autoScroll: true,
            border: true,
            //title: 'Control Regions',
            columnLines: true,
            enableColumnMove: false,
            enableColumnHide: false,
            listeners: {
                scope: this,
                viewready: function () {
                    //this.store.load();
                    var token = XDMoD.Dashboard.tokenize(document.location.hash);
                    if (token !== undefined && "root" in token && "tab" in token && "params" in token) {
                        var kernel_id = XDMoD.Dashboard.getParameterByName('kernel', token.content);
                        if(kernel_id===""){kernel_id=null;}
                        this.selectAppKernel(kernel_id);
                    }
                }

            },
            store: this.store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: false
                },
                columns: [
                    {header: 'Starts Date', dataIndex: 'control_region_starts'},
                    {header: 'Type', dataIndex: 'control_region_type'},
                    {header: 'End Date', dataIndex: 'control_region_ends'},
                    {header: 'Number of Points', dataIndex: 'control_region_points'},
                    {header: 'comment', dataIndex: 'comment',width: 360}
                ]
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: false
            })
        });

        var controlRegionsTimeFrameManipulatorPanel = new Ext.Panel({
            title: 'Control Regions Time Interval',
            region: 'south',
            autoScroll: true,
            collapsible: true,
            bodyStyle: 'padding: 5px 5px 0',
            layout: 'border',
            split: true,
            //border: false,
            //frame: false,
            height :200,
            tbar: controlRegionsTimeFrameManipulatorToolbar,
            items: [
                this.controlRegionsTimeFrameManipulatorGridPanel
            ]

        });

        // ---------------------------------------------------------

        var reloadChartFunc = function () {

            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {

                tree.getSelectionModel().unselect(node, true);
                tree.getSelectionModel().select(node);

            } //if (node != null)

        }; //reloadChartFunc

        // ---------------------------------------------------------

        var reloadChartTask = new Ext.util.DelayedTask(reloadChartFunc, this);

        var reloadChartStore = function (delay) {

            reloadChartTask.delay(delay || 0);

        }; //reloadChartStore

        // ---------------------------------------------------------

        // Fired when this tab is activated.
        self.on('activate', function(ak_control_regions) {
            if(document.location.hash!==""){
                var token = XDMoD.Dashboard.tokenize(document.location.hash);
                var kernel_id = XDMoD.Dashboard.getParameterByName('kernel', token.content);
                var start = XDMoD.Dashboard.getParameterByName('start', token.content);
                var end = XDMoD.Dashboard.getParameterByName('end', token.content);

                if(start!=="" && end!==""){
                    self.getDurationSelector().setValues(start,end);
                    if(kernel_id!=="")
                        Ext.History.add(token.root+":"+token.tab+ '?kernel=' + kernel_id);
                    else
                        Ext.History.add(token.root+":"+token.tab);
                }

                if (token !== undefined && "root" in token && "tab" in token && "params" in token) {
                    var kernel_id = XDMoD.Dashboard.getParameterByName('kernel', token.content);
                    if(kernel_id===""){kernel_id=null;}
                    ak_control_regions.selectAppKernel(kernel_id);
                }
            }
        }); // self.on('activate')

        self.on('duration_change', function (d) {
            var start_time = self.getDurationSelector().getStartDate() / 1000.0;
            var end_time = self.getDurationSelector().getEndDate() / 1000.0;
            tree.getRootNode().cascade(function (n) {
                var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                    (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                if (enabled) n.enable();
                else n.disable();
                //if(!enabled)n.setText(n.text+'*');

                return true;
            });
            reloadChartStore();

        }); //self.on('duration_change',...

        // ---------------------------------------------------------

        self.on('export_option_selected', function (opts) {

            var selectedNode = tree.getSelectionModel().getSelectedNode();

            if (selectedNode != null) {

                var parameters = getParameters.call(self, selectedNode);
                parameters.inline = 'n';

                Ext.apply(parameters, opts);

                var urlEndComponent = parameters.format == 'png' || parameters.format == 'svg' || parameters.format == 'eps' ? 'plots' : 'datasets';
                XDMoD.REST.download({
                    url: 'app_kernels/' + urlEndComponent,
                    method: 'GET',
                    params: parameters
                });

            } //if (selectedNode != null)

        }); //self.on('export_option_selected', ...

        // ---------------------------------------------------------

        function maximizeScale() {

            chartWidth = view.getWidth();
            chartHeight = view.getHeight() - (images.tbar ? images.tbar.getHeight() : 0);

        } //maximizeScale

        // ---------------------------------------------------------

        var images = new Ext.Panel({

            title: 'Viewer',
            region: 'center',
            margins: '2 2 2 0',
            layout: 'border',
            split: true,
            //border: false,
            items: [viewPanel, controlRegionsTimeFrameManipulatorPanel],

            tools: [

                {

                    id: 'restore',
                    qtip: 'Restore Chart Size',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if (node.attributes.metric_id != null)
                                chartScale = 1.0;
                            else
                                chartThumbScale = CCR.xdmod.ui.thumbChartScale;

                            onSelectNode.call(this, model, node);

                        } //if (node != null)

                    } //handler

                }, //restore

                {

                    id: 'minus',
                    qtip: 'Reduce Chart Size',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {


                            if ((chartThumbScale - CCR.xdmod.ui.deltaThumbChartScale)  > CCR.xdmod.ui.minChartScale)
                            {
                                chartThumbScale -= CCR.xdmod.ui.deltaThumbChartScale;

                                onSelectNode.call(this, model, node);
                            }

                        } //if (node != null)

                    } //handler

                }, //minus

                {

                    id: 'plus',
                    qtip: 'Increase Chart Size',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if ((this.chartThumbScale + CCR.xdmod.ui.deltaThumbChartScale) < CCR.xdmod.ui.maxChartScale)
                            {
                                this.chartThumbScale += CCR.xdmod.ui.deltaThumbChartScale;

                                onSelectNode.call(this, model, node);
                            }

                        } ////if (node != null)

                    } //handler

                } //plus

            ] //tools

        }); //images

        // ---------------------------------------------------------

        var onHistoryChange = function (token) {

            if (token) {

                var parts = token.split(CCR.xdmod.ui.tokenDelimiter);

                if (parts[0] == layoutId) {

                    var treePanel = Ext.getCmp('tree_' + layoutId);
                    var nodeId = parts[1];

                    Ext.menu.MenuMgr.hideAll();
                    treePanel.getSelectionModel().select(treePanel.getNodeById(nodeId));

                } //if (parts[0] == layoutId)

            } //if (token)

        }; //onHistoryChange

        // ---------------------------------------------------------

        // Handle this change event in order to restore the UI to the appropriate history state
        // Ext.History.on('change', onHistoryChange);

        self.on('print_clicked', function () {

            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {
                Ext.ux.Printer.print(view);
            } //if (node != null)

        }); //self.on('print_clicked', ...

        // ---------------------------------------------------------

        var toggleChangeIndicator = new Ext.Button({

            text: 'Change Indicators',
            enableToggle: true,
            scope: this,
            iconCls: 'exclamation',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'For each app kernel plot, show an exclamation point icon whenever a change has occurred in the execution environment (library version, compiler version, etc).'

        }); //toggleChangeIndicator

        var toggleRunningAverages = new Ext.Button({

            text: 'Running Averages',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'Show the running average values as a dashed line on the chart. The running average is the linear average of the last five values.'

        }); //toggleRunningAverages

        var toggleControlInterval = new Ext.Button({

            text: 'Control Band',
            enableToggle: true,
            hidden: false,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'Show a band on the chart representing the values of the running average considered "In Control" at any given time. <br>A control region is picked to be first few points in a dataset and updated whenever an execution environment change is detected by the app kernel system. The control band then is calculated by clustering the control region into two sets based on the median and then finding the average of each set. The two averages define the control band.'

        }); //toggleControlInterval

        var toggleControlZones = new Ext.Button({

            text: 'Control Zones',
            enableToggle: true,
            hidden: false,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'Show a red interval on the plot when the control value falls below -0.5, indicating an out of control (worse than expected) running average, and a green interval when the control value is greater than 0, indicating a better than control (better than expected) running average. Other running average values are considered "In Control"'

        }); //toggleControlZones

        // ---------------------------------------------------------

        var toggleControlPlot = new Ext.Button({

            text: 'Control Plot',
            enableToggle: true,
            hidden: false,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,

            listeners: {

                show: function () {

                    if (this.pressed)
                        toggleDiscreteControls.show();
                    else
                        toggleDiscreteControls.hide();

                    toggleControlZones.show();
                    toggleRunningAverages.show();
                    toggleControlInterval.show();

                }, //show

                hide: function () {

                    toggleDiscreteControls.hide();
                    toggleControlZones.hide();
                    toggleRunningAverages.hide();
                    toggleControlInterval.hide();

                }, //hide

                toggle: function (t, pressed) {

                    if (!pressed)
                        toggleDiscreteControls.hide();
                    else
                        toggleDiscreteControls.show();

                } //toggle

            }, //listeners

            tooltip: 'Plot the value of the control on the chart as a dotted line. The control is calculated as the distance of the running average to the nearest boundary of the control band, normalized over the range of the control band.'

        }); //toggleControlPlot

        // ---------------------------------------------------------

        var toggleDiscreteControls = new Ext.Button({

            text: 'Discrete Controls',
            enableToggle: true,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            hidden: false,
            pressed: true,
            tooltip: 'Convert the control values from real numbers to discrete values of [-1, 0, 1]. Values less than zero become -1 and values greater than zero become 1.'

        }); //toggleDiscreteControls
        this.plotSwitches={};
        this.plotSwitches.toggleChangeIndicator=toggleChangeIndicator;
        this.plotSwitches.toggleControlPlot=toggleControlPlot;
        this.plotSwitches.toggleDiscreteControls=toggleDiscreteControls;
        this.plotSwitches.toggleControlZones=toggleControlZones;
        this.plotSwitches.toggleRunningAverages=toggleRunningAverages;
        this.plotSwitches.toggleControlInterval=toggleControlInterval;

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

                        XDMoD.TrackEvent('App Kernels', 'Updated title', t.getValue());

                        reloadChartStore.call(this);

                    }

                }, //change

                specialkey: function (t, e) {

                    if (t.isValid(false) && e.getKey() == e.ENTER) {

                        XDMoD.TrackEvent('App Kernels', 'Updated title', t.getValue());

                        reloadChartStore.call(this);

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

                    XDMoD.TrackEvent('App Kernels', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));

                    this.legend_type = record.get('id');
                    reloadChartStore.call(this, 2000);

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

                    XDMoD.TrackEvent('App Kernels', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    reloadChartStore.call(this, 2000);

                } //change

            } //listeners

        }); //this.fontSizeSlider

        // ---------------------------------------------------------

        this.chartSwapXYField = new Ext.form.Checkbox({

            fieldLabel: 'Invert Axis',
            name: 'swap_xy',
            boxLabel: 'Swap the X and Y axis',
            checked: this.swap_xy,

            listeners: {

                scope: this,

                check: function (checkbox, check) {

                    XDMoD.TrackEvent('App Kernels', 'Clicked on Swap the X and Y axis', Ext.encode({checked: check}));

                    this.swap_xy = check;
                    reloadChartStore.call(this, 2000);

                } //check

            } //listeners

        }); //this.chartSwapXYField

        // ---------------------------------------------------------

        var leftPanel = new Ext.Panel({

            split: true,
            bodyStyle: 'padding:5px 5px ;',
            collapsible: true,
            header: true,
            title: 'Query Options',
            autoScroll: true,
            width: 250,
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
                    height: 97,
                    border: false,

                    items: [{

                        xtype: 'fieldset',
                        header: false,
                        layout: 'form',
                        hideLabels: false,
                        border: false,

                        defaults: {
                            anchor: '0' // '-20' // leave room for error icon
                        },

                        items: [

                            //this.chartTitleField,
                            this.legendTypeComboBox,
                            this.fontSizeSlider,
                            this.chartSwapXYField

                        ]

                    }]

                },

                tree

            ] //items

        }); //leftPanel

        // ---------------------------------------------------------

        Ext.apply(this, {

            customOrder: [

                XDMoD.ToolbarItem.DURATION_SELECTOR,
                XDMoD.ToolbarItem.EXPORT_MENU,
                XDMoD.ToolbarItem.PRINT_BUTTON,

                {
                    item: toggleChangeIndicator,
                    separator: true
                },

                {
                    item: toggleRunningAverages,
                    separator: false
                },

                {
                    item: toggleControlInterval,
                    separator: false
                },

                {
                    item: toggleControlZones,
                    separator: false
                },

                {
                    item: toggleControlPlot,
                    separator: false
                },

                {
                    item: toggleDiscreteControls,
                    separator: false
                }

            ],

            items: [leftPanel, images]

        }); //Ext.apply

        // ---------------------------------------------------------

        function onResize(t, adjWidth, adjHeight, rawWidth, rawHeight) {

            maximizeScale();
            if (this.chart) {
                const chartDiv = document.getElementById(this.chart.id);
                if (chartDiv) {
                    Plotly.relayout(this.chart.id, { width: adjWidth, height: adjHeight });
                    if (chartDiv._fullLayout.annotations.length > 0) {
                        const update = relayoutChart(chartDiv, adjHeight, false);
                        Plotly.relayout(this.chart.id, update);
                    }
                }
            }
        } //onResize

        // ---------------------------------------------------------
        view.on('render', function () {

            var viewer = this;
            //if (viewer.el) viewer.el.mask('Loading...');

            var thumbAspect = this.CCR_xdmod_ui_thumbAspect;
            var thumbWidth = this.CCR_xdmod_ui_thumbWidth * this.chartThumbScale;
            //replaced view.getWidth() with
            var portalWidth = view.getWidth() - (CCR.xdmod.ui.scrollBarWidth - CCR.xdmod.ui.thumbPadding/2)-375; // comp for scrollbar

            portalColumnsCount = Math.max(1, Math.round(portalWidth / thumbWidth) );

            thumbWidth = portalWidth / portalColumnsCount;
            thumbWidth -= CCR.xdmod.ui.thumbPadding;

            if(thumbWidth<this.CCR_xdmod_ui_thumbWidth * this.chartThumbScale){
                thumbWidth = this.CCR_xdmod_ui_thumbWidth * this.chartThumbScale;
                portalColumnsCount=1;
            }

            this.chartThumbScale = thumbWidth / (this.chartThumbScale * CCR.xdmod.ui.thumbWidth);

            self.getDurationSelector().disable();

            tree.getSelectionModel().on('selectionchange', onSelectNode, this);

        }, this, {
            single: true
        });
        view.on('resize', function () {

            var viewer = this;
            //if (viewer.el) viewer.el.mask('Loading...');

            var thumbAspect = this.CCR_xdmod_ui_thumbAspect;
            var thumbWidth = this.CCR_xdmod_ui_thumbWidth * this.chartThumbScale;

            var portalWidth = view.getWidth() - (CCR.xdmod.ui.scrollBarWidth - CCR.xdmod.ui.thumbPadding/2); // comp for scrollbar
            portalColumnsCount = Math.max(1, Math.round(portalWidth / thumbWidth) );

            thumbWidth = portalWidth / portalColumnsCount;
            thumbWidth -= CCR.xdmod.ui.thumbPadding;

            this.chartThumbScale = thumbWidth / (this.chartThumbScale * CCR.xdmod.ui.thumbWidth);

            self.getDurationSelector().disable();

            tree.getSelectionModel().on('selectionchange', onSelectNode, this);

        }, this, {
            single: true
        });
        this.addEvents('clickOnDataPoint');
        this.on('clickOnDataPoint',function(){
            console.log("clickOnDataPoint\n");
        },this)

        // Call parent (required)
        XDMoD.Arr.ControlRegionsPanel.superclass.initComponent.apply(this, arguments);

    }, //initComponent,
    getParameters:function(n){
        var parameters = {

            show_change_indicator: this.plotSwitches.toggleChangeIndicator.pressed ? 'y' : 'n',
            collected: n.attributes.collected,
            start_time: this.getDurationSelector().getStartDate() / 1000.0,
            end_time: this.getDurationSelector().getEndDate() / 1000.0,
            timeframe_label: this.getDurationSelector().getDurationLabel(),
            //title: this.chartTitleField.getValue(),
            legend_type: this.legendTypeComboBox.getValue(),
            font_size: this.fontSizeSlider.getValue(),
            swap_xy: this.swap_xy,
            contextMenuOnClick: this.id

        }; //parameters

        {
            //parameters.num_proc_units = n.attributes.num_proc_units;
            //parameters.metric = 4;//n.attributes.metric_id;
            parameters.metric = "Wall Clock Time";
            parameters.resource = n.attributes.resource_id;
            parameters.ak = n.attributes.ak_id;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.show_title = 'y';
            parameters.width = this.chartWidth * this.chartScale;
            parameters.height = this.chartHeight * this.chartScale;
            parameters.show_control_plot = this.plotSwitches.toggleControlPlot.pressed ? 'y' : 'n';
            parameters.discrete_controls = this.plotSwitches.toggleDiscreteControls.pressed ? 'y' : 'n';
            parameters.show_control_zones = this.plotSwitches.toggleControlZones.pressed ? 'y' : 'n';
            parameters.show_running_averages = this.plotSwitches.toggleRunningAverages.pressed ? 'y' : 'n';
            parameters.show_control_interval = this.plotSwitches.toggleControlInterval.pressed ? 'y' : 'n';


            parameters.resource = n.attributes.resource_id;
            parameters.ak = n.attributes.ak_id;
            parameters.width = CCR.xdmod.ui.thumbWidth * this.chartThumbScale;
            parameters.height = CCR.xdmod.ui.thumbHeight * this.chartThumbScale;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.thumbnail = 'y';
            parameters.show_guide_lines = 'n';
            parameters.font_size = parameters.font_size - 3;
            parameters.show_num_proc_units_separately = 'y';

        }

        return parameters;

    },
    contextMenuOnClick: function(args) {
        console.log(args.x);
        var d = new Date(args.x*1000);
        var h = "00"+d.getHours();
        var m = "00"+d.getMinutes();
        var args2={};
        args2.startDate=""+(d.getMonth()+1)+"/"+d.getDate()+"/"+d.getFullYear();
        args2.startTime=h.substr(h.length-2)+":"+m.substr(m.length-2);
        var dref=args.x*1000;
        console.log(args2.startDate);
        console.log(args2.startTime);
        var contextMenu = new Ext.menu.Menu({
            items: [{
              text: 'New Control Region Time Interval',
              scope:this,
              handler: function () {
                  this.onAddNewControlRegionTimeFrame(args2);
              }
            },{
              text: 'Delete Control Region Time Interval on Left',
              scope:this,
              handler: function () {
                  var control_regions=this.controlRegionsTimeFrameManipulatorGridPanel.store.data.items;

                  if(control_regions.length==0){
                      Ext.MessageBox.alert(title="Can not delete control region time intervals",
                        msg="Can not delete control region time intervals. There are no control region time intervals.");
                      return;
                  }

                  var ds=control_regions[0].data.control_region_starts.split(/(?:-| |:)+/);
                  var d = (new Date(ds[0],ds[1],ds[2],ds[3],ds[4],ds[5])).getTime();

                  if(d>dref){
                      Ext.MessageBox.alert(title="Can not delete control region time intervals",
                        msg="Can not delete control region time intervals. There are no control region time intervals on left.");
                      return;
                  }

                  var i;
                  var idel=null;

                  for(i=1;i<control_regions.length;i++){
                      var ds=control_regions[i].data.control_region_starts.split(/(?:-| |:)+/);
                      var d = (new Date(ds[0],ds[1],ds[2],ds[3],ds[4],ds[5])).getTime();

                      if(d>dref){
                          idel=i-1;
                          break;
                      }
                  }
                  if(idel===null){
                      idel=control_regions.length-1;
                  }


                  var selectoions=[
                      {'data':{'control_region_def_id':control_regions[idel].data.control_region_def_id,
                              'control_region_starts':control_regions[idel].data.control_region_starts}}];
                  this.deleteControlRegion(selectoions);
              }
            }]
          });
        contextMenu.showAt(Ext.EventObject.getXY());
    },
    onAddNewControlRegionTimeFrame: function(args) {
        if(this.ak_def_id!==null && this.resource_id!==null) {
            this.addNewControlRegionTimeFrame_RequestValues(false,args);
        }
        else {
            Ext.MessageBox.alert(title="Can not create new control region time intervals",
                msg="Can not create new control region time intervals. Resource and App Kernel are not selected.");
        }
    },
    onUpdateControlRegionTimeFrame: function() {
        if(this.ak_def_id!==null && this.resource_id!==null){
            var selModel=this.controlRegionsTimeFrameManipulatorGridPanel.getSelectionModel();
            var selectoions=selModel.getSelections();
            if(selectoions.length===0){
                Ext.MessageBox.alert(title="Can not update control region time intervals",
                    msg="Control region time interval is not selected");
                return;
            }
            if(selectoions.length>1){
                Ext.MessageBox.alert(title="Can not update control region time intervals",
                    msg="More then one control region time intervals are selected <br/>"+
                        "Can update only one at a time.");
                return;
            }
            var args={};
            var selectoion=selectoions[0].data;

            args.control_region_def_id=selectoion.control_region_def_id;
            args.control_region_time_interval_type=selectoion.control_region_type;
            args.n_points=selectoion.control_region_points;
            args.comment=selectoion.comment;

            var ds=selectoion.control_region_starts.split(/(?:-| |:)+/);
            args.startDate=""+ds[1]+"/"+ds[2]+"/"+ds[0];
            args.startTime=ds[3]+":"+ds[4];
            ds=selectoion.control_region_ends.split(/(?:-| |:)+/);
            args.endDate=""+ds[1]+"/"+ds[2]+"/"+ds[0];
            args.endTime=ds[3]+":"+ds[4];

            this.addNewControlRegionTimeFrame_RequestValues(true,args);
        }
        else{
            Ext.MessageBox.alert(title="Can not update control region time intervals",
                msg="Can not update control region time intervals. Resource and App Kernel are not selected.");
        }
    },
    addNewControlRegionTimeFrame_RequestValues: function(update,args) {
        var self = this;
        if(typeof args === 'undefined'){
            var d = new Date();
            var d2 = new Date();
            d2.setDate(d2.getDate() + 28);
            args={
                n_points:20,
                startDate:""+(d.getMonth()+1)+"/"+d.getDate()+"/"+d.getFullYear(),
                startTime:"00:00",
                endDate:""+(d2.getMonth()+1)+"/"+d2.getDate()+"/"+d2.getFullYear(),
                endTime:"00:00",
                control_region_time_interval_type:'data_points',
                comment:""
            };
        }
        else{
            var d = new Date();
            var d2 = new Date();
            d2.setDate(d2.getDate() + 28);
            if(!('n_points' in args)){args.n_points=20;}
            if(!('startDate' in args)){args.startDate=""+(d.getMonth()+1)+"/"+d.getDate()+"/"+d.getFullYear();}
            if(!('startTime' in args)){args.startTime="00:00";}
            if(!('endDate' in args)){args.endDate=""+(d2.getMonth()+1)+"/"+d2.getDate()+"/"+d2.getFullYear();}
            if(!('endTime' in args)){args.endTime="00:00";}
            if(!('control_region_time_interval_type' in args)){args.control_region_time_interval_type='data_points';}
            if(!('comment' in args)){args.comment="";}
        }

        var controlRegionTimeFrameWindow = new Ext.Window({
            layout: 'fit',
            title: ((update)?'Update Control Region':'Add New Control Region'),
            width: 480,
            minWidth: 480,
            height: 500,
            minHeight: 500,
            //closeAction: 'hide',
            plain: true,
            border: false,
            modal: true,

            items: new Ext.FormPanel({
                border: 'false',
                id: 'controlRegionTimeFrameForm',
                bodyStyle: 'padding: 5px',
                defaults: {
                    anchor: '0'
                },
                items: [
                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Region Starting Date',
                        msgTarget : 'side',
                        anchor    : '-20',
                        defaults: {
                            flex: 1
                        },
                        items: [
                            {
                                xtype: 'datefield',
                                name : 'startDate',
                                allowBlank:false
                            },
                            {
                                xtype: 'timefield',
                                format: 'H:i',
                                name : 'startTime',
                                allowBlank:false
                            }
                        ]
                    },{
                        xtype: 'radiogroup',
                        fieldLabel: 'Region End Type',
                        defaultType: 'radio',
                        id: 'cbg_control_region_time_interval_type',
                        items: [
                            {boxLabel: 'Total Number of Runs', inputValue: 'data_points', name: 'control_region_time_interval_type', checked: true},
                            {boxLabel: 'End Date', inputValue: 'date_range', name: 'control_region_time_interval_type'}
                        ]
                    },{
                        xtype: 'spinnerfield',
                        fieldLabel: 'Number of Runs in Region',
                        id:'n_points',
                        name: 'n_points',
                        minValue: 1,
                        allowDecimals: false,
                        decimalPrecision: 1,
                        incrementValue: 1,
                        accelerate: true
                    },{
                        xtype: 'compositefield',
                        id:'endDateTime',
                        fieldLabel: 'Region End Date',
                        msgTarget : 'side',
                        anchor    : '-20',
                        defaults: {
                            flex: 1
                        },
                        items: [
                            {
                                xtype: 'datefield',
                                name : 'endDate',
                                allowBlank:false
                            },
                            {
                                xtype: 'timefield',
                                format: 'H:i',
                                name : 'endTime',
                                allowBlank:false
                            }
                        ]
                    },{
                        xtype     : 'textfield',
                        name      : 'comment',
                        fieldLabel: 'Comment',
                        anchor    : '-20'
                    }
                ]
            }),
            buttons: [
                {
                    text: ((update)?'Update':'Add'),
                    scope: this,
                    handler: function () {
                        var formPanel = controlRegionTimeFrameWindow.getComponent('controlRegionTimeFrameForm');
                        var form = formPanel.getForm();

                        if(form.isValid()){
                            var values=form.getValues();
                            var d;
                            var t;
                            if(values.endDate&&values.endTime){
                                d=values.endDate.split("/");
                                t=values.endTime.split(":");
                                values.endDateTime=""+d[2]+"-"+d[0]+"-"+d[1]+" "+t[0]+":"+t[1]+":00";
                                delete values.endDate;
                                delete values.endTime;
                            }
                            d=values.startDate.split("/");
                            t=values.startTime.split(":");
                            values.startDateTime=""+d[2]+"-"+d[0]+"-"+d[1]+" "+t[0]+":"+t[1]+":00";
                            delete values.startDate;
                            delete values.startTime;
                            if(values.control_region_time_interval_type==='data_points'){
                                delete values.endDateTime;
                            }
                            if(values.control_region_time_interval_type==='date_range'){
                                delete values.n_points;
                            }
                            if(update){
                                values.control_region_def_id=args.control_region_def_id;
                            }
                            controlRegionTimeFrameWindow.close();
                            this.addNewControlRegionTimeFrame(values);
                        }
                        else {
                            Ext.MessageBox.alert('Error', 'Some of the input values are invalid!');
                        }
                    }
                },
                {
                    text: 'Cancel',
                    //scope: this,
                    handler: function () {
                        controlRegionTimeFrameWindow.close();
                    }
                }
            ]
        });
        var formPanel = controlRegionTimeFrameWindow.getComponent('controlRegionTimeFrameForm');
        var cbg_control_region_time_interval_type = formPanel.getComponent('cbg_control_region_time_interval_type');
        cbg_control_region_time_interval_type.on('change', function (th, checked) {
            var n_points=this.getComponent('n_points');
            var endDateTime=this.getComponent('endDateTime');
            var selected=checked.inputValue;
            if(selected==='data_points'){
                n_points.enable();
                endDateTime.disable();
            }
            if(selected==='date_range'){
                n_points.disable();
                endDateTime.enable();
            }
        },formPanel);

        //set initial values
        var form = formPanel.getForm();
        form.setValues(args);
        //set enable/disable
        cbg_control_region_time_interval_type.fireEvent('change',this,{inputValue:args.control_region_time_interval_type});

        //
        controlRegionTimeFrameWindow.show();
    },
    addNewControlRegionTimeFrame: function(args) {
        var self=this;
        var update=true;
        if(typeof args.control_region_def_id === 'undefined'){
            update=false;
        }
        var finishedAddingNewControlRegionTimeFrame = function (options, success, response) {
            if (success) {
                success = CCR.checkJSONResponseSuccess(response);
            }

            Ext.MessageBox.hide();

            if (success) {
                self.fireEvent("reloadControlRegionsRep");
            }
            else{
                var attemptedAction = update ? 'updating' : 'adding new';
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: 'App Kernels',
                    wrapperMessage: 'An error occurred while ' + attemptedAction + ' control region(s).'
                });
            }
        };
        Ext.MessageBox.show({
            msg: (update?"Updatng":"Adding new")+' control region time interval <br/> and recalculating control values ...',
            progressText: (update?"Updatng":"Adding new")+' control region time interval <br/> and recalculating control values ...',
            width: 300,
            wait: true,
            waitConfig: {
                interval: 200
            }
        });
        args.ak_def_id=this.ak_def_id,
        args.resource_id=this.resource_id,
        args.debug=1;
        XDMoD.REST.connection.request({
            url: 'app_kernels/control_regions',
            method: update ? 'PUT' : 'POST',
            params: args,
            callback: finishedAddingNewControlRegionTimeFrame
        });
    },
    onDeleteControlRegion: function () {
        var selModel=this.controlRegionsTimeFrameManipulatorGridPanel.getSelectionModel();
        var selectoions=selModel.getSelections();

        this.deleteControlRegion(selectoions);
    },
    deleteControlRegion: function (selectoions) {
        if(selectoions.length===0){
            Ext.MessageBox.alert(title="Can not delete control region time intervals",msg="Control region time intervals was not selected for deletion");
            return;
        }
        if(this.ak_def_id===null || this.resource_id===null) {
            Ext.MessageBox.alert(title="Can not delete control region time intervals",
                msg="Can not delete control region time intervals. Resource and App Kernel are not selected.");
        }

        var self=this;

        var restArgs={
            'ak_def_id':this.ak_def_id,
            'resource_id':this.resource_id,
            'controlRegiondIDs':[],
            'debug':1
        };
        var confirmMessage="Do you want to delete following control regions:<br/>";
        for (i = 0; i < selectoions.length; i++) {
            restArgs.controlRegiondIDs[i]=selectoions[i].data.control_region_def_id;
            confirmMessage+="&nbsp;&nbsp;&nbsp;&nbsp;Starts from: ";
            confirmMessage+=selectoions[i].data.control_region_starts;
            confirmMessage+='<br/>';
        }
        //TODO: check that there is no initial control region
        var executionConfirmed = function (btn) {
            if(btn!=='yes')return;

            var finishedDeleteControlRegion = function (options, success, response) {
                if (success) {
                    success = CCR.checkJSONResponseSuccess(response);
                }

                Ext.MessageBox.hide();

                if (success) {
                    self.fireEvent("reloadControlRegionsRep");
                }
                else{
                    CCR.xdmod.ui.presentFailureResponse(response, {
                        title: 'App Kernels',
                        wrapperMessage: 'An error occurred while deleting control region(s).'
                    });
                }
            };
            Ext.MessageBox.show({
                msg: 'Deleting Control Regions Time Intervals<br/> and recalculating control values ......',
                progressText: 'Deleting Control Regions Time Intervals<br/> and recalculating control values ......',
                width: 300,
                wait: true,
                waitConfig: {
                    interval: 200
                }
            });

            XDMoD.REST.connection.request({
                url: 'app_kernels/control_regions',
                method: 'DELETE',
                params: restArgs,
                callback: finishedDeleteControlRegion
            });
        };
        Ext.MessageBox.confirm('Confirm', confirmMessage, executionConfirmed,this);
    },
    reloadControlRegions: function() {
        if(this.resource_id!==null && this.ak_def_id!==null){
            this.store.load({
                    params:
                    {
                        resource_id: this.resource_id,
                        ak_def_id:this.ak_def_id
                    }
                });
        }
    },
    getParameterByName: function(name, source) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&#]" + name + "=([^&#]*)"),
            results = regex.exec(source);
        return results === null
            ? ""
            : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

}); //Ext.extend(XDMoD.Module.AppKernelViewer, Ext.Panel
