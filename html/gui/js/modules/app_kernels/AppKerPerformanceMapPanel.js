/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */
Ext.namespace('XDMoD', 'XDMoD.Arr', 'CCR', 'CCR.xdmod', 'CCR.xdmod.ui', 'Ext.ux.grid');
Ext.QuickTips.init();  // enable tooltips

XDMoD.Arr.AppKerPerformanceMapStore = Ext.extend(Ext.data.JsonStore, {
    restful: true,

    proxy: XDMoD.REST.createHttpProxy({
        url: 'app_kernels/performance_map',
        method: 'GET'
    }),
    constructor: function (config) {
        // eslint-disable-next-line no-param-reassign
        config = config || {};

        Ext.apply(config, {
            baseParams: {}
        });

        XDMoD.Arr.AppKerPerformanceMapStore.superclass.constructor.call(this, config);
    }
});

XDMoD.Arr.AppKerPerformanceMapGrid = Ext.extend(Ext.grid.GridPanel, {
    id: 'ak_perfmap',
    loadMask: true,
    listeners: {
        viewready: function () {
            this.store.load();
        }
    },
    colorStyles: {
        W: 'style="background-color:white;"',
        F: 'style="background-color:#FFB0C4;"',
        U: 'style="background-color:#FFB336;"',
        O: 'style="background-color:#81BEF7;"',
        C: 'style="background-color:#FFF8DC;"',
        N: 'style="background-color:#B0FFC5;"',
        R: 'style="background-color:#DCDCDC;"'
    },
    rendererForCell: function (value) {
        if (value !== ' ' && value !== '') {
            var v = value.split('/');
            for (var i = 1; i < v.length; i++) {
                v[i] = parseInt(v[i], 10);
            }
            if (v[0] in this.colorStyles) {
                return '<div class="x-grid3-cell-inner" ' + this.colorStyles[v[0]] + '"><span style="color:black;">' + value + '</span></div>';
            }
        }
        return value || 0;
    },
    metaDataChanged: function () {
        var newColumns = [{
            header: 'Resource',
            dataIndex: 'resource',
            width: 80
        }, {
            header: 'App Kernel',
            dataIndex: 'appKer',
            width: 90

        }, {
            header: 'Nodes',
            dataIndex: 'problemSize',
            align: 'right',
            width: 50
        }];
        var nLocked = newColumns.length;
        /* eslint-disable block-scoped-var */
        for (var i = nLocked; i < this.store.fields.getCount(); i++) {
            var value = this.store.fields.itemAt(i).name;
            var m_date = value.split('/');
            if (value.indexOf('Failed') >= 0) {
                continue;
            }

            if (value.indexOf('InControl') >= 0) {
                continue;
            }
            if (value.indexOf('OutOfControl') >= 0) {
                continue;
            }
            if (value.indexOf('IDs') >= 0) {
                continue;
            }

            var day = m_date[2];

            newColumns.push({
                header: day,
                dataIndex: value,
                align: 'center',
                renderer: {
                    fn: this.rendererForCell,
                    scope: this
                },
                width: 40
            });
        }
        /* eslint-enable block-scoped-var */
        var newColModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false
            },
            columns: newColumns
        });

        this.dateGroup[0].length = 1;

        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        /* eslint-disable block-scoped-var, no-redeclare */
        for (var i = nLocked; i < this.store.fields.getCount(); i++) {
            var value = this.store.fields.itemAt(i).name;
            var m_date = value.split('/');
            if (value.indexOf('IDs') >= 0) {
                continue;
            }

            var monthYear = monthNames[parseInt(m_date[1], 10) - 1] + ', ' + m_date[0];

            if (this.dateGroup[0][this.dateGroup[0].length - 1].header === monthYear) {
                this.dateGroup[0][this.dateGroup[0].length - 1].colspan++;
            } else {
                this.dateGroup[0].push({
                    header: monthYear,
                    colspan: 1,
                    align: 'center'
                });
            }
        }
        /* eslint-enable block-scoped-var, no-redeclare */
        newColModel.rows = this.dateGroup;

        this.reconfigure(this.store, newColModel);
    },

    constructor: function (config) {
        // eslint-disable-next-line no-param-reassign
        config = config || {};

        this.store = new Ext.data.JsonStore({
            baseParams: {},
            proxy: XDMoD.REST.createHttpProxy({
                url: 'app_kernels/performance_map',
                method: 'GET'
            })
        });

        this.store.on('metadatachanged', this.metaDataChanged, this);
        this.store.on('datachanged', this.metaDataChanged, this);
        this.store.on('reconfigure', this.metaDataChanged, this);

        this.dateGroup = [[{
            header: '',
            colspan: 3,
            align: 'center'
        }]];

        var dateHeader = new Ext.ux.grid.ColumnHeaderGroup({
            rows: this.dateGroup
        });
        this.plugins = [dateHeader];

        Ext.apply(config, {
            id: 'appKerPerformanceMapGrid',
            cls: 'appKerPerfMap_grid',
            columnLines: true,
            enableColumnMove: false,
            enableColumnHide: false,
            colModel: new Ext.ux.grid.LockingColumnModel({

                defaults: {
                    sortable: false
                },
                columns: [{
                    header: 'Resource',
                    dataIndex: 'resource'
                }, {
                    header: 'App Kernel',
                    dataIndex: 'appKer',
                    width: 100
                }, {
                    header: 'Nodes',
                    dataIndex: 'problemSize',
                    align: 'right',
                    width: 60
                }]
            }),
            plugins: dateHeader,
            selModel: new Ext.grid.CellSelectionModel({
                singleSelect: true
            })
        });

        XDMoD.Arr.AppKerPerformanceMapGrid.superclass.constructor.call(this, config);
    }
});

XDMoD.Arr.AppKerPerformanceMapPanel = function (config) {
    XDMoD.Arr.AppKerPerformanceMapPanel.superclass.constructor.call(this, config);
};

Ext.extend(XDMoD.Arr.AppKerPerformanceMapPanel, Ext.Panel, {
    title: 'Performance Map',
    resourcesList: ['blacklight', 'edge', 'edge12core', 'lonestar4', 'kraken', 'trestles', 'gordon', 'stampede'],
    problemSizeList: [1, 2, 4, 8, 16],
    appKerList: ['xdmod.app.astro.enzo', 'xdmod.app.chem.gamess', 'xdmod.app.chem.nwchem', 'xdmod.app.md.namd', 'xdmod.benchmark.hpcc', 'xdmod.benchmark.io.ior', 'xdmod.benchmark.io.mpi-tile-io', 'xdmod.benchmark.mpi.imb', 'xdmod.benchmark.graph.graph500', 'xdmod.bundle'],
    legend:
        '<b>KEY</b>: Each day is summarized in a table cell as pair of a symbol and a number. ' +
        'The symbol represents the status of last application kernel execution on that day ' +
        'and the number shows the total number of runs. Each cell is colored according to ' +
        'the status of last application kernel run. The description of the codes are: <br/><br/>' +
        '<table border="1" cellspacing="0" style="">' +
        '<tr><td>Code</td><td>Description</td></tr>' +
        '<tr><td style="background-color:#B0FFC5;">N</td>' +
        '<td>Application kernel was executed within control interval</td></tr>' +
        '<tr><td style="background-color:#FFB336;">U</td>' +
        '<td>Application kernel was under-performing</td></tr>' +
        '<tr><td style="background-color:#81BEF7;">O</td>' +
        '<td>Application kernel was over-performing</td></tr>' +
        '<tr><td style="background-color:#FFB0C4;">F</td>' +
        '<td>Application kernel failed to run</td></tr>' +
        '<tr><td style="background-color:#FFF8DC;">C</td>' +
        '<td>This run was used to calculate control region</td></tr>' +
        '<tr><td style="background-color:#DCDCDC;">R</td>' +
        '<td>Application kernel ran, but control information is not available</td></tr>' +
        '<tr><td style="background-color:white;"> </td>' +
        '<td>There was no application kernel runs</td></tr>' +
        '</table>' +
        'Select cell for more details',
    initComponent: function () {
        var appKerPerformanceMapGrid = new XDMoD.Arr.AppKerPerformanceMapGrid({
            scope: this,
            region: 'center'
        });

        this.appKerPerformanceMapGrid = appKerPerformanceMapGrid;

        var resourceChildren = [];
        /* eslint-disable block-scoped-var */
        for (var i = 0; i < this.resourcesList.length; i++) {
            var resource = this.resourcesList[i];
            /* eslint-enable block-scoped-var */
            resourceChildren.push({
                text: resource,
                nick: resource,
                type: 'resource',
                checked: true,
                iconCls: 'resource',
                leaf: true
            });
        }

        this.resourcesTree = new Ext.tree.TreePanel({
            title: 'Resources',
            id: 'tree_resources_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,
            region: 'north',
            root: new Ext.tree.AsyncTreeNode({
                nodeType: 'async',
                text: 'Resources',
                draggable: false,
                id: 'resources',
                expanded: true,
                children: resourceChildren
            }),
            rootVisible: false,
            containerScroll: true,
            tools: [{
                id: 'unselect',
                qtip: 'De-select all selected resources.',
                scope: this,
                handler: function () {
                    // eslint-disable-next-line no-use-before-define
                    this.resourcesTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.resourcesTree.getRootNode().cascade(function (n) {
                        var ui = n.getUI();
                        if (ui.isChecked()) {
                            selectAll = false;
                        }
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.resourcesTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (!ui.isChecked()) {
                                ui.toggleCheck(true);
                            }
                            lastNode = n;
                        });
                    } else {
                        this.resourcesTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (ui.isChecked()) {
                                ui.toggleCheck(false);
                            }
                            lastNode = n;
                        });
                    }
                    if (lastNode) {
                        // eslint-disable-next-line no-use-before-define
                        reloadAll.call(this);
                    }
                    // eslint-disable-next-line no-use-before-define
                    this.resourcesTree.on('checkchange', reloadAll, this);
                }
            }, {
                id: 'refresh',
                qtip: 'Refresh',
                hidden: true,
                scope: this,
                // eslint-disable-next-line no-use-before-define
                handler: reloadAll
            }],
            margins: '0 0 0 0',
            border: false,
            split: true,
            flex: 4
        });

        var problemSizeChildren = [];
        /* eslint-disable block-scoped-var, no-redeclare */
        for (var i = 0; i < this.problemSizeList.length; i++) {
            var nodesSize = this.problemSizeList[i];
            /* eslint-enable block-scoped-var */
            problemSizeChildren.push({
                text: String(nodesSize),
                qtip: (nodesSize === 1) ? nodesSize + 'node' : nodesSize + 'nodes',
                type: 'node',
                checked: true,
                iconCls: 'node',
                leaf: true
            });
        }

        this.problemSizesTree = new Ext.tree.TreePanel({
            flex: 0.5,
            title: 'Problem Size (Cores or Nodes)',
            id: 'tree_nodes_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,

            root: new Ext.tree.AsyncTreeNode({
                nodeType: 'async',
                text: 'Resources',
                draggable: false,
                id: 'resources',
                expanded: true,
                children: problemSizeChildren
            }),
            tools: [{
                id: 'unselect',
                qtip: 'De-select all selected resources.',
                scope: this,
                handler: function () {
                    // eslint-disable-next-line no-use-before-define
                    this.problemSizesTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.problemSizesTree.getRootNode().cascade(function (n) {
                        var ui = n.getUI();
                        if (ui.isChecked()) {
                            selectAll = false;
                        }
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.problemSizesTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (!ui.isChecked()) {
                                ui.toggleCheck(true);
                            }
                            lastNode = n;
                        });
                    } else {
                        this.problemSizesTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (ui.isChecked()) {
                                ui.toggleCheck(false);
                            }
                            lastNode = n;
                        });
                    }

                    if (lastNode) {
                        // eslint-disable-next-line no-use-before-define
                        reloadAll.call(this);
                    }
                    // eslint-disable-next-line no-use-before-define
                    this.problemSizeTree.on('checkchange', reloadAll, this);
                }
            }, {
                id: 'refresh',
                qtip: 'Refresh',
                hidden: true,
                scope: this,
                // eslint-disable-next-line no-use-before-define
                handler: reloadAll
            }],
            rootVisible: false,
            containerScroll: true,
            margins: '0 0 0 0',
            border: false
        });

        var appKerChildren = [];
        /* eslint-disable block-scoped-var, no-redeclare */
        for (var i = 0; i < this.appKerList.length; i++) {
            var appker = this.appKerList[i];
            /* eslint-enable block-scoped-var */
            appKerChildren.push({
                text: appker,
                nick: appker,
                type: 'app_kernel',
                checked: true,
                iconCls: 'appkernel',
                leaf: true
            });
        }

        this.appKerTree = new Ext.tree.TreePanel({
            title: 'App Kernels',
            id: 'tree_appker_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,
            region: 'north',
            root: new Ext.tree.AsyncTreeNode({
                nodeType: 'async',
                text: 'App Kernels',
                draggable: false,
                id: 'appker',
                expanded: true,
                children: appKerChildren
            }),
            tools: [{
                id: 'unselect',
                qtip: 'De-select all selected resources.',
                scope: this,
                handler: function () {
                    // eslint-disable-next-line no-use-before-define
                    this.appKerTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.appKerTree.getRootNode().cascade(function (n) {
                        var ui = n.getUI();
                        if (ui.isChecked()) {
                            selectAll = false;
                        }
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.appKerTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (!ui.isChecked()) {
                                ui.toggleCheck(true);
                            }
                            lastNode = n;
                        });
                    } else {
                        this.appKerTree.getRootNode().cascade(function (n) {
                            var ui = n.getUI();
                            if (ui.isChecked()) {
                                ui.toggleCheck(false);
                            }
                            lastNode = n;
                        });
                    }

                    if (lastNode) {
                        // eslint-disable-next-line no-use-before-define
                        reloadAll.call(this);
                    }
                    // eslint-disable-next-line no-use-before-define
                    this.appKerTree.on('checkchange', reloadAll, this);
                }
            }, {
                id: 'refresh',
                qtip: 'Refresh',
                hidden: true,
                scope: this,
                // eslint-disable-next-line no-use-before-define
                handler: reloadAll
            }],
            rootVisible: false,
            containerScroll: true,
            margins: '0 0 0 0',
            border: false,
            split: true,
            flex: 4
        });

        var commentsTemplate = new Ext.XTemplate(
            '<table class="xd-table">',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description_label">Resource:</span> {resource}<br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description_label">Application Kernel:</span> {appKer} <br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> Failed runs: {failedJobs} </span><br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> Under-performing runs: {underPerformingJobs} </span><br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> Over-performing runs: {overPerformingJobs} </span><br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> In control runs: {inControlJobs} </span><br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> Runs used for control calculations: {controlJobs} </span><br/>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description"> Runs without control information: {noControlInfoJobs} </span><br/>',
            '</td>',
            '</tr>',
            '</table><br/>',
            this.legend
        );
        this.commentsPanel = new Ext.Panel({
            id: 'commentsPanel',
            region: 'south',
            autoScroll: true,
            border: true,
            collapsible: true,
            split: true,
            title: 'Description',
            height: 130,
            html: this.legend
        }); // commentsPanel

        this.appKerPerformanceMapGrid.getSelectionModel().on('cellselect', function (sm, rowIdx, colIndex) {
            /* populate detailed view pannele with arr job ids */
            // eslint-disable-next-line no-shadow
            var i;
            var j;

            var detailPanel = Ext.getCmp('commentsPanel');
            var dataIndex = sm.grid.getColumnModel().getDataIndex(colIndex);
            var record = sm.grid.getStore().getAt(rowIdx);

            var dataIndexAll = [dataIndex];
            // if columns with resource or appkernel name is selected show all jobs for the queried period
            if (colIndex <= 2) {
                dataIndexAll = [];
                for (j = 3; j < record.fields.getCount(); j++) {
                    var key = record.fields.getKey(record.fields.itemAt(j));
                    if (key.indexOf('Failed') >= 0) {
                        continue;
                    }
                    if (key.indexOf('InControl') >= 0) {
                        continue;
                    }
                    if (key.indexOf('OutOfControl') >= 0) {
                        continue;
                    }
                    dataIndexAll.push(key);
                }
            }
            // pack jobs
            var iStatus;
            var statuses = ['F', 'U', 'N', 'O', 'C', 'R'];
            var ref = {
                F: 'failedJobs',
                U: 'underPerformingJobs',
                N: 'inControlJobs',
                O: 'overPerformingJobs',
                C: 'controlJobs',
                R: 'noControlInfoJobs'
            };
            var jobsIDs = {
                F: '',
                U: '',
                N: '',
                O: '',
                C: '',
                R: ''
            };

            for (j = dataIndexAll.length - 1; j >= 0; j--) {
                var dataIndexRun = dataIndexAll[j];
                var s;
                for (iStatus = 0; iStatus < statuses.length; iStatus++) {
                    s = record.get(dataIndexRun + '-IDs-' + statuses[iStatus]);
                    if (typeof s !== 'undefined' && s !== '' && s !== ' ') {
                        var runs = s.split(',');
                        for (i = 0; i < runs.length; i++) {
                            runs[i] = parseInt(runs[i], 10);
                        }
                        runs.sort(function (a, b) {
                            return b - a;
                        });
                        for (i = 0; i < runs.length; i++) {
                            if (jobsIDs[statuses[iStatus]] !== '') {
                                jobsIDs[statuses[iStatus]] += ', ';
                            }
                            jobsIDs[statuses[iStatus]] += '<a href="#" onclick="javascript:var iw=new XDMoD.AppKernel.InstanceWindow({instanceId:' + runs[i] + '});iw.show()">' + runs[i] + '</a>';
                        }
                    }
                }
            }
            var dataValue = record.get(dataIndex);
            var values = {
                appKer: record.get('appKer'),
                resource: record.get('resource'),
                rowIdx: rowIdx,
                colIndex: colIndex,
                dataIndex: dataIndex,
                dataValue: dataValue
            };
            for (iStatus = 0; iStatus < statuses.length; iStatus++) {
                values[ref[statuses[iStatus]]] = jobsIDs[statuses[iStatus]];
            }
            commentsTemplate.overwrite(detailPanel.body, values);
        });

        var viewPanel = new Ext.Panel({
            layout: 'border',
            region: 'center',
            items: [this.appKerPerformanceMapGrid, this.commentsPanel],
            border: true
        }); // viewPanel

        this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
            id: 'duration_selector_' + this.id,
            alignRight: false,
            showRefresh: true,
            showAggregationUnit: false,
            handler: function () {
                // eslint-disable-next-line no-use-before-define
                reloadAll.call(this);
            },
            scope: this // also scope of handle
        });

        this.durationToolbar.dateSlider.region = 'south';

        function exportFunction(format) {
            var parameters = appKerPerformanceMapGrid.store.baseParams;

            parameters.format = format;

            CCR.invokePost('controllers/arr_controller.php', parameters, {
                checkDashboardUser: true
            });
        }
        var exportButton = new Ext.Button({
            id: 'export_button_' + this.id,
            text: 'Export',
            iconCls: 'export',
            tooltip: 'Export chart data',
            menu: [{
                text: 'CSV - comma Separated Values',
                iconCls: 'csv',
                handler: function () {
                    exportFunction('csv', false);
                }
            }]
        });
        this.durationToolbar.addItem('-');
        this.durationToolbar.addItem(exportButton);

        var getBaseParams = function () {
            return {
                start_date: this.durationToolbar.getStartDate().format('Y-m-d'),
                end_date: this.durationToolbar.getEndDate().format('Y-m-d'),
                format: 'json'
            };
        };

        this.appKerPerformanceMapGrid.store.on('beforeload', function () {
            if (!this.durationToolbar.validate()) {
                return;
            }

            var baseParams = {};
            Ext.apply(baseParams, getBaseParams.call(this));

            this.appKerPerformanceMapGrid.store.baseParams = baseParams;
        }, this);

        this.appKerPerformanceMapGrid.store.on('load', function () {
            if (this.resource && this.app_kernel) {
                var index = this.appKerPerformanceMapGrid.store.findBy(function (record) {
                    return record.get('resource') === this.resource && this.app_kernel.indexOf(record.get('appKer').toLowerCase()) !== -1;
                }, this);

                if (index >= 0) {
                    this.appKerPerformanceMapGrid.getSelectionModel().select(index, 0);

                    this.resource = undefined;
                    this.app_kernel = undefined;
                }
            }

            // Ensure that we unmask the main interface once we're done loading.
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) {
                viewer.el.unmask();
            }
        }, this);

        function reloadAll() {
            this.appKerPerformanceMapGrid.store.load();
        }

        Ext.apply(this, {
            layout: 'border',
            tbar: this.durationToolbar,
            items: [viewPanel],
            listeners: {
                activate: function () {
                    var token = CCR.tokenize(document.location.hash);
                    var params = Ext.urlDecode(token.params);

                    if (params.ak) {
                        var info = Ext.decode(window.atob(params.ak));

                        if (info.resource) {
                            this.resource = info.resource;
                        }

                        if (info.app_kernel) {
                            this.app_kernel = info.app_kernel;
                        }

                        var refresh = false;
                        if (info.start_date) {
                            this.durationToolbar.startDateField.setValue(info.start_date);
                            refresh = true;
                        }
                        if (info.end_date) {
                            this.durationToolbar.endDateField.setValue(info.end_date);
                            refresh = true;
                        }

                        if (refresh) {
                            this.durationToolbar.onHandle(true);
                        }
                    }
                }
            }
        }); // Ext.apply

        XDMoD.Arr.AppKerPerformanceMapPanel.superclass.initComponent.apply(this, arguments);
    } // initComponent
});
// XDMoD.Arr.AppKerPerformanceMapPanel
