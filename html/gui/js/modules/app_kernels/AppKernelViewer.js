/**
 * This class contains functionality for the App Kernels tab.
 *
 * @author Amin Ghadersohi
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 * @author Nikolay Simakov
 */
XDMoD.Module.AppKernels.AppKernelViewer = function (config) {
    XDMoD.Module.AppKernels.AppKernelViewer.superclass.constructor.call(this, config);
};

/**
 * Add public static methods to the AppKernelViewer class.
 */
Ext.apply(XDMoD.Module.AppKernels.AppKernelViewer, {

    /**
     * Select a child node of the currently selected node.
     *
     * This method must be static since it is referenced by name from
     * HTML in a chart.
     *
     * When a user clicks on thumbnail of a app kernel chart, this
     * function will find the sub node, expand the path to it and then
     * select it so that the chart view will change to view the selected
     * app kernel chart.
     *
     * @param {Number} metric_id The selected metric_id.
     * @param {Number} resource_id The selected resource_id.
     * @param {Number} kernel_id The selected kernel_id.
     */
    selectChildAppKernelChart: function (metric_id, resource_id, kernel_id) {
        if (metric_id == -1 || resource_id == -1 || kernel_id == -1) {
            return;
        }

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Loading...');
        }

        var tree = Ext.getCmp('tree_app_kernel_viewer');

        if (!tree) {
            if (viewer.el) {
                viewer.el.unmask();
            }

            return;
        }

        var selectedNode = tree.getSelectionModel().getSelectedNode();

        if (!selectedNode) {
            if (viewer.el) {
                viewer.el.unmask();
            }

            return;
        }

        tree.expandPath(selectedNode.getPath(), null, function (success, node) {
            if (!success) {
                if (viewer.el) {
                    viewer.el.unmask();
                }

                return;
            }

            if (
                node.attributes.type == 'appkernel' &&
                node.attributes.ak_id == kernel_id
            ) {
                var nodeToExpand = node.findChild('resource_id', resource_id);

                tree.expandPath(nodeToExpand.getPath(), null, function (success2, node2) {
                    if (!success2) {
                        if (viewer.el) {
                            viewer.el.unmask();
                        }

                        return;
                    }

                    var nodeToSelect = node2.findChild('metric_id', metric_id, true);

                    if (!nodeToSelect) {
                        if (viewer.el) {
                            viewer.el.unmask();
                        }

                        return;
                    }

                    tree.getSelectionModel().select(nodeToSelect);
                });
            } else if (
                node.attributes.type == 'resource' &&
                node.attributes.resource_id == resource_id
            ) {
                var nodeToSelect = node.findChild('metric_id', metric_id, true);

                if (!nodeToSelect) {
                    if (viewer.el) {
                        viewer.el.unmask();
                    }

                    return;
                }

                tree.getSelectionModel().select(nodeToSelect);
            } else {
                if (viewer.el) {
                    viewer.el.unmask();
                }
            }
        });
    },

    /**
     * When a user clicks on thumbnail of a app kernel chart, this
     * function will find the sub node, expand the path to it and then
     * select it so that the chart view will change to view the selected
     * app kernel chart.
     *
     * This method must be static since it is referenced by name from
     * HTML in a chart.
     *
     * @param {String} num_units The number of units that was selected.
     */
    selectChildUnitsChart: function (num_units) {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Loading...');
        }

        var tree = Ext.getCmp('tree_app_kernel_viewer');

        if (!tree) {
            if (viewer.el) {
                viewer.el.unmask();
            }

            return;
        }

        var nn = tree.getSelectionModel().getSelectedNode();

        if (!nn) {
            if (viewer.el) {
                viewer.el.unmask();
            }

            return;
        }

        tree.expandPath(nn.getPath(), null, function (success, node) {
            if (!success) {
                if (viewer.el) {
                    viewer.el.unmask();
                }

                return;
            }

            var nodeToSelect = node.findChild('num_proc_units', num_units, true);

            if (!nodeToSelect) {
                if (viewer.el) {
                    viewer.el.unmask();
                }

                return;
            }

            tree.getSelectionModel().select(nodeToSelect);
        });
    }
});

/**
 * The application kernels module.
 */
Ext.extend(XDMoD.Module.AppKernels.AppKernelViewer, XDMoD.PortalModule, {
    module_id: 'application_kernel_viewer',
    usesToolbar: true,
    toolbarItems: {
        durationSelector: {
            enable: true,
            config: {
                showAggregationUnit: false
            }
        },
        exportMenu: true,
        printButton: true
    },
    legend_type: 'bottom_center',
    font_size: 3,
    swap_xy: false,
    showDateChooser: true,
    current_hash:'',
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
    ],

    /**
     * Template for displaying a single application kernel.
     */
    largeTemplate: [
        '<tpl for=".">',
        '<center>',
        '<div id="{random_id}">',
        '</div>',
        '</center>',
        '</tpl>'
    ],

    /**
     * Template for displaying multiple application kernel thumbnails.
     */
    thumbTemplate: [
        '<tpl for=".">',
        '<div class="chart_thumb-wrap2" id="{ak_id}{resource_id}{metric_id}">',
        '<span class="ak_thumb_title">{ak_name}: {resource_name}</span>',
        '<div class="chart_thumb">',
        '<a href="javascript:XDMoD.Module.AppKernels.AppKernelViewer.selectChildAppKernelChart({metric_id},{resource_id},{ak_id});">',
        '<div id="{random_id}">',
        '</div>',
        '</a>',
        '</div>',
        '<span class="ak_thumb_subtitle">{short_title}</span>',
        '</div>',
        '</tpl>'
    ],

    /**
     * Template used for comments that include the resource.
     */
    commentsTemplateWithResourceTemplate: [
        '<table class="xd-table">',
        '<tr>',
        '<td width="50%">',
        '<span class="kernel_description_label">Application Kernel:</span>',
        '<br/>',
        '<span class="kernel_description">{comments}</span>',
        '</td>',
        '<td width="50%">',
        '<span class="kernel_description_label">{resource_name}:</span>',
        '<br/>',
        '<span class="kernel_description">{resource_description}</span>',
        '</td>',
        '</tr>',
        '</table>'
    ],

    /**
     * Template used for comments that don't include the resource.
     */
    commentsTemplateWithoutResourceTemplate: [
        '<table class="xd-table">',
        '<tr>',
        '<td width="100%">',
        '<span class="kernel_description_label">Application Kernel:</span>',
        '<br/>',
        '<span class="kernel_description">{comments}</span>',
        '</td>',
        '</tr>',
        '</table>'
    ],

    chartScale: 1,
    chartThumbScale: CCR.xdmod.ui.thumbChartScale,
    chartWidth: 740,
    chartHeight: 345,
    leftPanelWidth:375,

    /**
     * Initialize app kernel module.
     */
    initComponent: function () {

        var treeTb = new Ext.Toolbar({
            items: [
                '->',
                {
                    iconCls: 'icon-collapse-all',
                    tooltip: 'Collapse All',
                    handler: function () {
                        XDMoD.TrackEvent('App Kernels', 'Clicked on Collapse All button above tree panel');
                        this.tree.root.collapse(true);
                    },
                    scope: this
                },
                {
                    iconCls: 'refresh',
                    tooltip: 'Refresh tree and clear drilldown nodes',
                    handler: function () {
                        XDMoD.TrackEvent('App Kernels', 'Clicked on Refresh button above tree panel');

                        var selModel = this.tree.getSelectionModel();
                        var selNode = selModel.getSelectedNode();

                        if (selNode) {
                            selModel.unselect(selNode, true);
                        }

                        this.tree.root.removeAll(true);
                        this.tree.loader.on('load', this.selectFirstNode, this, {
                            single: true
                        });
                        this.tree.loader.load(this.tree.root);
                    },
                    scope: this
                }
            ]
        });

        this.tree = new Ext.tree.TreePanel({
            id: 'tree_' + this.id,
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
                            collected: node.attributes.collected
                        });
                    },
                    load: XDMoD.REST.TreeLoader.prototype.createStandardLoadListener()
                }
            }),
            listeners: {
                scope: this,
                beforeappend: function (t, p, n) {
                    n.setIconCls(n.attributes.type);

                    var start_time = this.getDurationSelector().getStartDate() / 1000.0;
                    var end_time = this.getDurationSelector().getEndDate() / 1000.0;

                    var attr = n.attributes;

                    var enabled =
                        (start_time <= attr.end_ts && attr.end_ts <= end_time) ||
                        (attr.start_ts <= end_time && end_time <= attr.end_ts);

                    if (enabled) {
                        n.enable();
                    } else {
                        n.disable();
                    }
                },
                expandnode: function (n) {
                    XDMoD.TrackEvent(
                        'App Kernels',
                        'Expanded item in tree panel',
                        n.getPath('text')
                    );
                },
                collapsenode: function (n) {
                    XDMoD.TrackEvent(
                        'App Kernels',
                        'Collapsed item in tree panel',
                        n.getPath('text')
                    );
                }
            },
            root: {
                id: 'app_kernels',
                nodeType: 'async',
                text: this.title,
                draggable: false
            },
            useArrows: true,
            autoScroll: true,
            animate: true,
            enableDD: false,

            rootVisible: false,
            tbar: treeTb,

            header: false,
            containerScroll: true,
            border: false,
            region: 'center'
        });

        this.tree.loader.on('load', this.selectFirstNode, this, {
            buffer: 500,
            single: true
        });

        this.thumbnailChartTemplate = new Ext.XTemplate(this.thumbTemplate);
        this.largeChartTemplate = new Ext.XTemplate(this.largeTemplate);

        this.chartStore = new Ext.data.JsonStore({
            highChartPanels: [],
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
        });

        // Handle chart store exceptions.
        this.chartStore.on('exception', function (dp, type, action, opt, response, arg) {
            CCR.xdmod.ui.presentFailureResponse(response);

            var viewer = CCR.xdmod.ui.Viewer.getViewer();

            if (viewer.el) {
                viewer.el.unmask();
            }
        }, this);

        // Check that the duration is valid before loading and update
        // scale and sizing.
        this.chartStore.on('beforeload', function () {
            if (!this.getDurationSelector().validate()) {
                return;
            }

            this.maximizeScale();
            this.view.un('resize', this.onViewResize, this);
        }, this);

        // Display charts after loading chart data.
        this.chartStore.on('load', function (chartStore) {
            var n = this.selectedNode;

            if (!n) {
                return;
            }

            // Is a single chart being displayed?
            var isChart =
                n.attributes.type == 'units' ||
                n.attributes.type == 'metric';

            // Are multiple charts being displayed with the menu?
            var isMenu =
                n.attributes.type == 'resource' ||
                n.attributes.type == 'appkernel';

            // Delete the current chart if only one will be displayed.
            if (isChart && this.chart) {
                delete this.chart;
                this.chart = null;
            }

            // Delete all the charts if multiple will be displayed.
            if (isMenu) {
                if (this.charts) {
                    for (var i = 0; i < this.charts.length; i++) {
                        delete this.charts[i];
                    }

                    delete this.charts;
                }

                this.charts = [];
            }

            this.updateDescriptionLarge(
                chartStore,
                n.attributes.type != 'appkernel'
            );

            XDMoD.TrackEvent(
                'App Kernels',
                'Loaded AK Data',
                n.getPath('text')
            );

            // These tools are only displayed if multiple charts are
            // being displayed at the same time.
            var toolNames = [
                'restore',
                'plus',
                'minus'
            ];

            Ext.each(toolNames, function (toolName) {
                var tool = this.images.getTool(toolName);

                if (tool) {
                    if (isMenu) {
                        tool.show();
                    } else {
                        tool.hide();
                    }
                }
            }, this);

            var viewer = CCR.xdmod.ui.Viewer.getViewer();

            if (viewer.el) {
                viewer.el.unmask();
            }

            chartStore.each(function (r) {
                var id = r.get('random_id');

                var task = new Ext.util.DelayedTask(function () {

                    // Calculate width and height depending on if
                    // multiple chart are displayed or just one.
                    var width =
                        isMenu ?
                        CCR.xdmod.ui.thumbWidth * this.chartThumbScale :
                        this.chartWidth * this.chartScale;

                    var height =
                        isMenu ?
                        CCR.xdmod.ui.thumbHeight * this.chartThumbScale :
                        this.chartHeight * this.chartScale;

                    var baseChartOptions = {
                        chart: {
                            renderTo: id,
                            width: width,
                            height: height,
                            animation: false,
                            events: {
                                load: function (e) {

                                    // Check if an empty data set was
                                    // returned.  If not, display the
                                    // "no data" image.
                                    this.checkSeries = function () {
                                        if (this.series.length === 0) {
                                            if (this.placeholder_element) {
                                                this.placeholder_element.destroy();
                                            }

                                            this.placeholder_element = this.renderer.image(
                                                'gui/images/report_thumbnail_no_data.png',
                                                isMenu ? 0 : (this.chartWidth - 400) / 2,
                                                isMenu ? 0 : (this.chartHeight - 300) / 2,
                                                isMenu ? this.chartWidth : 400,
                                                isMenu ? this.chartHeight : 300
                                            ).add();
                                        }
                                    };

                                    this.checkSeries();
                                },
                                redraw: function (e) {
                                    if (this.checkSeries) {
                                        this.checkSeries();
                                    }
                                }
                            }
                        },
                        plotOptions:{
                            series:{
                                animation: false,
                                point: {
                                    events: {
                                        click: function () {
                                            if (this.series.userOptions.rawNumProcUnits) {
                                                XDMoD.Module.AppKernels.AppKernelViewer.selectChildUnitsChart(this.series.userOptions.rawNumProcUnits);
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        loading: {
                            labelStyle: {
                                top: '45%'
                            }
                        },
                        exporting: {
                            enabled: false
                        },
                        credits: {
                            enabled: true
                        }
                    };

                    var chartOptions = r.get('hc_jsonstore');
                    jQuery.extend(true, chartOptions, baseChartOptions);

                    chartOptions.exporting.enabled = false;
                    chartOptions.credits.enabled = isChart;

                    if (isMenu) {
                        this.charts.push(XDMoD.utils.createChart(chartOptions));
                    } else {
                        this.chart = XDMoD.utils.createChart(chartOptions);
                    }
                }, this);

                task.delay(0);

                return true;
            }, this);

            this.view.on('resize', this.onViewResize, this);

            if (isMenu) {
                this.getExportMenu().disable();
            } else {
                this.getExportMenu().enable();
            }

            this.getDurationSelector().enable();
        }, this);

        // Show all the tools after the chart store loads.
        this.chartStore.on('load', function (chartStore) {
            var toolNames = [
                'restore',
                'plus',
                'minus',
                'print',
                'restore'
            ];

            Ext.each(toolNames, function (toolName) {
                var tool = this.images.getTool(toolName);

                if (tool) {
                    tool.show();
                }
            }, this);
        }, this, {
            single: true
        });

        // Hide all the tools when the chart store cache is cleared.
        this.chartStore.on('clear', function (chartStore) {
            var toolNames = [
                'restore',
                'plus',
                'minus',
                'print',
                'restore'
            ];

            Ext.each(toolNames, function (toolName) {
                var tool = this.images.getTool(toolName);

                if (tool) {
                    tool.hide();
                }
            }, this);
        }, this, {
            single: true
        });

        this.view = new Ext.DataView({
            loadingText: "Loading...",
            itemSelector: 'chart_thumb-wrap',
            style: 'overflow:auto',
            multiSelect: true,
            store: this.chartStore,
            autoScroll: true,
            tpl: this.largeChartTemplate
        });

        var viewPanel = new Ext.Panel({
            layout: 'fit',
            region: 'center',
            items: this.view,
            border: true
        });

        this.commentsTemplateWithResource = new Ext.XTemplate(this.commentsTemplateWithResourceTemplate);
        this.commentsTemplateWithoutResource = new Ext.XTemplate(this.commentsTemplateWithoutResourceTemplate);

        this.commentsPanel = new Ext.Panel({
            region: 'south',
            autoScroll: true,
            border: true,
            collapsible: true,
            split: true,
            title: 'Description',
            height: 130
        });

        // Update the selected tree node when the panel is activated.
        this.on('activate', function (panel) {
            var token = CCR.tokenize(document.location.hash);

            // If we've received the activate event but the token does not specify
            // us as the subtab then exit.
            if (token.subtab && token.subtab !== this.id) {
                return;
            }
            // If the tree is already loading, replace the "load"
            // handler.  Otherwise, the node can be selected
            // immediately.
            if (this.tree.getLoader().isLoading()) {

                this.tree.loader.un('load', this.selectFirstNode, this);

                this.tree.loader.on('load', function () {
                    this.selectAppKernelFromUrl(panel);
                }, this, { single: true });

            } else {
                this.selectAppKernelFromUrl(panel);
            }

            // SET: the default panel.
            CCR.xdmod.ui.activeTab = panel;
        }, this);

        // Update enabled/disabled nodes in the tree after the duration
        // is changes.
        this.on('duration_change', function (d) {
            this.reloadChartStore();
        }, this);

        this.on('export_option_selected', function (opts) {
            var selectedNode = this.tree.getSelectionModel().getSelectedNode();

            if (selectedNode !== null) {
                var parameters = this.getParameters(selectedNode);

                parameters.inline = 'n';

                Ext.apply(parameters, opts);

                var imageTypes = ['png', 'svg', 'pdf', 'eps'];
                var urlEndComponent = imageTypes.includes(parameters.format) ? 'plots' : 'datasets';
                XDMoD.REST.download({
                    url: 'app_kernels/' + urlEndComponent,
                    method: 'GET',
                    params: parameters
                });
            }
        }, this);

        this.images = new Ext.Panel({
            title: 'Viewer',
            region: 'center',
            margins: '2 2 2 0',
            layout: 'border',
            split: true,
            items: [viewPanel, this.commentsPanel],
            tools: [
                {
                    id: 'restore',
                    qtip: 'Restore Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {
                        var model = this.tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node !== null) {
                            if (node.attributes.metric_id !== null) {
                                this.chartScale = 1.0;
                            } else {
                                this.chartThumbScale = CCR.xdmod.ui.thumbChartScale;
                            }

                            this.onSelectNode(model, node);
                        }
                    }
                },
                {
                    id: 'minus',
                    qtip: 'Reduce Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {
                        var model = this.tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node !== null) {
                            if ((this.chartThumbScale - CCR.xdmod.ui.deltaThumbChartScale) > CCR.xdmod.ui.minChartScale) {
                                this.chartThumbScale -= CCR.xdmod.ui.deltaThumbChartScale;

                                this.onSelectNode(model, node);
                            }
                        }
                    }
                },
                {
                    id: 'plus',
                    qtip: 'Increase Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {
                        var model = this.tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node !== null) {
                            if ((this.chartThumbScale + CCR.xdmod.ui.deltaThumbChartScale) < CCR.xdmod.ui.maxChartScale) {
                                this.chartThumbScale += CCR.xdmod.ui.deltaThumbChartScale;

                                this.onSelectNode(model, node);
                            }
                        }
                    }
                }
            ]
        });

        /**
         * Handle this change event in order to restore the UI to the
         * appropriate history state.
         */
        this.on('print_clicked', function () {
            var model = this.tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node !== null) {
                Ext.ux.Printer.print(this.view);
            }
        }, this);

        this.toggleChangeIndicator = new Ext.Button({
            text: 'Change Indicators',
            enableToggle: true,
            scope: this,
            iconCls: 'exclamation',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            pressed: false,
            tooltip: 'For each app kernel plot, show an exclamation point icon whenever a change has occurred in the execution environment (library version, compiler version, etc).'
        });

        this.toggleRunningAverages = new Ext.Button({
            text: 'Running Averages',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            pressed: true,
            tooltip: 'Show the running average values as a dashed line on the chart. The running average is the linear average of the last five values.'
        });

        this.toggleControlInterval = new Ext.Button({
            text: 'Control Band',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            pressed: true,
            tooltip: 'Show a band on the chart representing the values of the running average considered "In Control" at any given time. <br>A control region is picked to be first few points in a dataset and updated whenever an execution environment change is detected by the app kernel system. The control band then is calculated by clustering the control region into two sets based on the median and then finding the average of each set. The two averages define the control band.'
        });

        this.toggleControlZones = new Ext.Button({
            text: 'Control Zones',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            pressed: true,
            tooltip: 'Show a red interval on the plot when the control value falls below -0.5, indicating an out of control (worse than expected) running average, and a green interval when the control value is greater than 0, indicating a better than control (better than expected) running average. Other running average values are considered "In Control"'
        });

        this.toggleControlPlot = new Ext.Button({
            text: 'Control Plot',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            pressed: false,
            listeners: {
                scope: this,
                show: function () {
                    if (this.pressed) {
                        this.toggleDiscreteControls.show();
                    } else {
                        this.toggleDiscreteControls.hide();
                    }

                    this.toggleControlZones.show();
                    this.toggleRunningAverages.show();
                    this.toggleControlInterval.show();

                },
                hide: function () {
                    this.toggleDiscreteControls.hide();
                    this.toggleControlZones.hide();
                    this.toggleRunningAverages.hide();
                    this.toggleControlInterval.hide();
                },
                toggle: function (t, pressed) {
                    if (!pressed) {
                        this.toggleDiscreteControls.hide();
                    } else {
                        this.toggleDiscreteControls.show();
                    }
                }
            },
            tooltip: 'Plot the value of the control on the chart as a dotted line. The control is calculated as the distance of the running average to the nearest boundary of the control band, normalized over the range of the control band.'
        });

        this.toggleDiscreteControls = new Ext.Button({
            text: 'Discrete Controls',
            enableToggle: true,
            scope: this,
            iconCls: '',
            toggleHandler: function (b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               this.reloadChartStore();
            },
            hidden: true,
            pressed: false,
            tooltip: 'Convert the control values from real numbers to discrete values of [-1, 0, 1]. Values less than zero become -1 and values greater than zero become 1.'
        });

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
                        this.reloadChartStore();
                    }
                },
                specialkey: function (t, e) {
                    if (t.isValid(false) && e.getKey() == e.ENTER) {
                        XDMoD.TrackEvent('App Kernels', 'Updated title', t.getValue());
                        this.reloadChartStore();
                    }
                }
            }
        });

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
            }),
            disabled: false,
            value: this.legend_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            listeners: {
                scope: this,
                select: function (combo, record, index) {
                    XDMoD.TrackEvent('App Kernels', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));

                    this.legend_type = record.get('id');
                    this.reloadChartStore(2000);
                }
            }
        });

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
                change: function (t, n, o) {
                    XDMoD.TrackEvent('App Kernels', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    this.reloadChartStore(2000);
                }
            }
        });

        var leftPanel = new Ext.Panel({
            split: true,
            bodyStyle: 'padding:5px 5px ;',
            collapsible: true,
            header: true,
            title: 'Query Options',
            autoScroll: true,
            width: this.leftPanelWidth,
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
                    height: 74,
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
                            this.legendTypeComboBox,
                            this.fontSizeSlider
                        ]
                    }]
                },
                this.tree
            ]
        });

        Ext.apply(this, {
            customOrder: [
                XDMoD.ToolbarItem.DURATION_SELECTOR,
                XDMoD.ToolbarItem.EXPORT_MENU,
                XDMoD.ToolbarItem.PRINT_BUTTON,
                {
                    item: this.toggleChangeIndicator,
                    separator: true
                },
                {
                    item: this.toggleRunningAverages,
                    separator: false
                },
                {
                    item: this.toggleControlInterval,
                    separator: false
                },
                {
                    item: this.toggleControlZones,
                    separator: false
                },
                {
                    item: this.toggleControlPlot,
                    separator: false
                },
                {
                    item: this.toggleDiscreteControls,
                    separator: false
                }
            ],
            items: [leftPanel, this.images]
        });

        this.view.on('render', function () {
            var viewer = CCR.xdmod.ui.Viewer.getViewer();

            if (viewer.el) {
                viewer.el.mask('Loading...');
            }

            var thumbWidth = CCR.xdmod.ui.thumbWidth * this.chartThumbScale;

            var portalWidth = viewer.getWidth()-this.leftPanelWidth;

            portalWidth = portalWidth - (CCR.xdmod.ui.scrollBarWidth - CCR.xdmod.ui.thumbPadding / 2);

            if(portalWidth<50.0){
                portalWidth=this.chartWidth;
            }

            var portalColumnsCount = Math.max(1, Math.round(portalWidth / thumbWidth) );

            thumbWidth = portalWidth / portalColumnsCount;
            thumbWidth -= CCR.xdmod.ui.thumbPadding;

            this.chartThumbScale = thumbWidth / (this.chartThumbScale * CCR.xdmod.ui.thumbWidth);

            this.getDurationSelector().disable();

            this.tree.getSelectionModel().on('selectionchange', this.onSelectNode, this);
        }, this, {
            single: true
        });

        XDMoD.Module.AppKernels.AppKernelViewer.superclass.initComponent.apply(this, arguments);
    },

    /**
     * Select a node in the application kernel tree.
     *
     * @param {String} kernel_id The application ak_id, resource_id,
     *     metric_id and procunit_id of the tree node to select.  These
     *     id values are concatentated together with underscores between
     *     each value.
     *     (e.g. kernel_id = 23_1000 => ak_id = 23, resource_id = 1000)
     */
    selectAppKernel: function (kernel_id) {
        var node_ids = kernel_id.split('_');

        if (node_ids.length === 0) {
            return;
        }

        // Build an array of all the paths up to the desired node.
        var path = '/app_kernels';
        var paths = [path];

        var tmp_id = '';

        for (var i = 0; i < node_ids.length; i++) {
            if (tmp_id !== '') {
                tmp_id += '_';
            }

            tmp_id += node_ids[i];

            path += '/' + tmp_id;
            paths.push(path);
        }

        // The expandPath function doesn't allow a scope to be
        // specified, so store a reference to the tree in a separate
        // variable.
        var tree = this.tree;

        // Traverse the path expanding each node.
        var expander = function (success, node) {
            if (!success) {
                CCR.xdmod.ui.Viewer.getViewer().el.unmask();
                return;
            }

            // If there are no more paths, the node has been found.
            if (!paths.length) {
                tree.getSelectionModel().select(node);
                return;
            }

            path = paths.shift();

            if (node.childNodes.length > 0) {
                tree.expandPath(path, null, expander);
            } else {

                // Delay the expansion so the child nodes are ready.
                new Ext.util.DelayedTask(function () {
                    tree.expandPath(path, null, expander);
                }).delay(1);
            }
        };

        expander(true, this.tree.root);
    },

    /**
     * Get the task that will reload the chart store.
     *
     * This method will always return the same object so that the delay
     * method can be used.
     *
     * @return {Ext.util.DelayedTask}
     */
    getReloadChartStoreTask: function () {
        if (!this.reloadChartStoreTask) {
            this.reloadChartStoreTask = new Ext.util.DelayedTask(function () {
                var node = this.selectedNode;

                if (node !== null) {
                    this.tree.getSelectionModel().unselect(node, true);
                    this.tree.getSelectionModel().select(node);
                }
            }, this);
        }

        return this.reloadChartStoreTask;
    },

    /**
     * Reload the chart store.
     *
     * @param {Number} delay Wait this many milliseconds before reloading.
     */
    reloadChartStore: function (delay) {
        this.getReloadChartStoreTask().delay(delay || 0);
    },

    /**
     * Maximize chart width and height.
     */
    maximizeScale: function () {
        this.chartWidth = this.view.getWidth();
        this.chartHeight = this.view.getHeight() -
            (this.images.tbar ? this.images.tbar.getHeight() : 0);
    },

    /**
     * Resize chart.
     */
    onViewResize: function (t, adjWidth, adjHeight, rawWidth, rawHeight) {
        this.maximizeScale();

        if (this.chart) {
            this.chart.setSize(adjWidth, adjHeight);
        }
    },

    /**
     * Select the first node in the tree if none are selected.
     */
    selectFirstNode: function () {

        var token = CCR.tokenize(document.location.hash);

        // If we've received the activate event but the token does not specify
        // us as the subtab then exit.
        if (token.subtab && token.subtab !== this.id) {
            return;
        }

        var sm = this.tree.getSelectionModel();
        var node = sm.getSelectedNode();

        // If a node is already selected, don't do anything.
        if (node) {
            return;
        }

        var root = this.tree.getRootNode();

        if (root.hasChildNodes()) {
            var selected=false;
            for (var i = 0; i < root.childNodes.length; i++) {
                if(root.childNodes[i].disabled===false){
                    sm.select(root.childNodes[i]);
                    selected=true;
                    break;
                }
            }
            if(selected===false){
                sm.select(root.childNodes[0]);
            }
        } else {
            CCR.xdmod.ui.Viewer.getViewer().el.unmask();
        }
    },

    /**
     * Update the description.
     *
     * @param {Ext.data.Store} s Data store.
     * @param {Boolean} showResource True if the resource name should be
     *     included in the description.
     */
    updateDescriptionLarge: function (s, showResource) {
        var data = {
            comments: s.getAt(0).get('comments'),
            resource_name: s.getAt(0).get('resource_name'),
            resource_description: s.getAt(0).get('resource_description')
        };

        if (showResource) {
            this.commentsTemplateWithResource.overwrite(this.commentsPanel.body, data);
        } else {
            this.commentsTemplateWithoutResource.overwrite(this.commentsPanel.body, data);
        }
    },

    /**
     * Get parameters to use when reloading the chart store.
     *
     * @param {Ext.tree.TreeNode} n The selected tree node.
     */
    getParameters: function (n) {

        var parameters = {
            show_change_indicator: this.toggleChangeIndicator.pressed ? 'y' : 'n',
            collected: n.attributes.collected,
            start_time: this.getDurationSelector().getStartDate() / 1000.0,
            end_time: this.getDurationSelector().getEndDate() / 1000.0,
            timeframe_label: this.getDurationSelector().getDurationLabel(),
            legend_type: this.legendTypeComboBox.getValue(),
            font_size: this.fontSizeSlider.getValue(),
            swap_xy: this.swap_xy
        };

        if (n.attributes.type == 'units') {
            parameters.num_proc_units = n.attributes.num_proc_units;
            parameters.metric = n.attributes.metric_id;
            parameters.resource = n.attributes.resource_id;
            parameters.ak = n.attributes.ak_id;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.show_title = 'y';
            parameters.width = this.chartWidth * this.chartScale;
            parameters.height = this.chartHeight * this.chartScale;
            parameters.show_control_plot = this.toggleControlPlot.pressed ? 'y' : 'n';
            parameters.discrete_controls = this.toggleDiscreteControls.pressed ? 'y' : 'n';
            parameters.show_control_zones = this.toggleControlZones.pressed ? 'y' : 'n';
            parameters.show_running_averages = this.toggleRunningAverages.pressed ? 'y' : 'n';
            parameters.show_control_interval = this.toggleControlInterval.pressed ? 'y' : 'n';
        } else if (n.attributes.type == 'metric') {
            parameters.metric = n.attributes.metric_id;
            parameters.resource = n.attributes.resource_id;
            parameters.ak = n.attributes.ak_id;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.show_title = 'y';
            parameters.width = this.chartWidth * this.chartScale;
            parameters.height = this.chartHeight * this.chartScale;
        } else if (n.attributes.type == 'resource') {
            parameters.resource = n.attributes.resource_id;
            parameters.ak = n.attributes.ak_id;
            parameters.width = CCR.xdmod.ui.thumbWidth * this.chartThumbScale;
            parameters.height = CCR.xdmod.ui.thumbHeight * this.chartThumbScale;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.thumbnail = 'y';
            parameters.show_guide_lines = 'n';
            parameters.font_size = parameters.font_size - 3;
        } else if (n.attributes.type == 'appkernel') {
            parameters.ak = n.attributes.ak_id;
            parameters.metric = 'Wall Clock Time';
            parameters.width = CCR.xdmod.ui.thumbWidth * this.chartThumbScale;
            parameters.height = CCR.xdmod.ui.thumbHeight * this.chartThumbScale;
            parameters.scale = 1;
            parameters.format = 'session_variable';
            parameters.thumbnail = 'y';
            parameters.show_guide_lines = 'n';
            parameters.font_size = parameters.font_size - 3;
        }

        return parameters;
    },

    /**
     * Handle node selection.
     *
     * @param {TreeSelectionModel} model The selection model for the tree.
     * @param {Ext.tree.TreeNode} n The selected tree node.
     */
    onSelectNode: function (model, n) {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        // If the node is disabled, use the previously selected node.
        // This will allow the user to selected a time period that
        // contains to data for the ak and then select a time period
        // where there is data for the ak.
        if (this.selectedNode&&(!n || n.disabled)) {
            n = this.selectedNode;
        } else {
            this.selectedNode = n;
        }

        if (!this.getDurationSelector().validate()) {
            if (viewer.el) {
                viewer.el.unmask();
            }

            return;
        }

        var duration = this.getDurationSelector();
        var start = duration.getStartDate().format('Y-m-d');
        var end = duration.getEndDate().format('Y-m-d');
        var token =
            this.layoutId +
            '?kernel=' + n.id +
            '&start=' + start +
            '&end=' + end;
        this.current_hash=token
        Ext.History.add(token, true);

        this.images.setTitle(n.getPath('text'));

        if (n.attributes.type == 'units') {
            this.toggleControlPlot.show();
        } else {
            this.toggleControlPlot.hide();
        }

        var isChart =
            n.attributes.type == 'units' ||
            n.attributes.type == 'metric';

        var isMenu =
            n.attributes.type == 'resource' ||
            n.attributes.type == 'appkernel';

        XDMoD.TrackEvent(
            'App Kernels',
            'Selected ' + n.attributes.type + ' From Tree',
            n.getPath('text')
        );

        if (isChart || isMenu) {
            if (viewer.el) {
                viewer.el.mask('Loading...');
            }

            this.getDurationSelector().disable();

            if (isChart) {
                this.view.tpl = this.largeChartTemplate;
            } else if (isMenu) {
                this.view.tpl = this.thumbnailChartTemplate;
            }

            var parameters = this.getParameters(n);

            if (isChart) {
                this.view.tpl = this.largeChartTemplate;
            } else if (isMenu) {
                this.view.tpl = this.thumbnailChartTemplate;
            }

            this.chartStore.load({ params: parameters });
        } else {
            if (viewer.el) {
                viewer.el.unmask();
            }
        }
        this.updateEnabledNodes();
    },

    /**
     * Select the app kernel specified in the URL, but only if the token
     * in the URL matches the current panel.  This indicates that the
     * page was loaded directly from a URL that specified this tab.
     *
     * @param {Ext.Panel} panel The current panel.
     */
    selectAppKernelFromUrl: function (panel) {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();
        var token = CCR.tokenize(document.location.hash);
        var kernel_id = viewer.getParameterByName('kernel', token.content);
        var start = viewer.getParameterByName('start', token.content);
        var end = viewer.getParameterByName('end', token.content);

        if (start !== "" && end !== "") {
            this.getDurationSelector().setValues(start, end);
        }

        if (token.subtab === panel.id  && kernel_id) {
            this.selectAppKernel(kernel_id);
        } else {
            this.selectFirstNode();
        }
    },

    /**
     * Update which tree nodes are enabled and disabled based on the
     * currently selected duration.
     */
    updateEnabledNodes: function () {
        var start_time = this.getDurationSelector().getStartDate() / 1000.0;
        var end_time = this.getDurationSelector().getEndDate() / 1000.0;

        this.tree.getRootNode().cascade(function (n) {
            var attr = n.attributes;
            var enabled =
                (start_time <= attr.end_ts && attr.end_ts <= end_time) ||
                (attr.start_ts <= end_time && end_time <= attr.end_ts);

            if (enabled) {
                n.enable();
            } else {
                n.disable();
            }

            return true;
        });
    }
});
