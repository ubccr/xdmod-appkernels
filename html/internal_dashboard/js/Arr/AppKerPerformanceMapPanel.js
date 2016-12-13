/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */


Ext.namespace('XDMoD', 'XDMoD.Arr','CCR', 'CCR.xdmod', 'CCR.xdmod.ui','Ext.ux.grid');
Ext.QuickTips.init();  // enable tooltips

XDMoD.Arr.AppKerPerformanceMapStore = Ext.extend(Ext.data.JsonStore, {
    restful:true,
    
    proxy: XDMoD.REST.createHttpProxy({
                url: 'app_kernels/performance_map',
                method: 'GET'
            }),

    listeners : {
        exception : function(misc) {
            console.log(misc);
        }
    },

    constructor : function(config) {
        config = config || {};

        var nowEpoch = Date.now();

        Ext.apply(config, {
            baseParams : {
            }
        });

        XDMoD.Arr.AppKerPerformanceMapStore.superclass.constructor.call(this, config);
    }
});



XDMoD.Arr.AppKerPerformanceMapGrid = Ext.extend(Ext.grid.GridPanel, {
    id:'ak_perfmap',
    loadMask : true,
    listeners : {
        viewready : function() {
            this.store.load();
            console.log('viewready');
        }
    },
    colorStyles:{'W':'style="background-color:white;"',
                'F':'style="background-color:#FFB0C4;"',
                'U':'style="background-color:#F7FE2E;"',
                'O':'style="background-color:#FE9A2E;"',
                'C':'style="background-color:#81BEF7;"',
                'N':'style="background-color:#B0FFC5;"',
                'R':'style="background-color:#F781F3;"'},
    rendererForCell : function(value, metaData, record, rowIndex, colIndex, store) {
        if (value != ' ' && value != '') {
            var v = value.split('/');
            for (var i = 1; i < v.length; i++)
                v[i] = parseInt(v[i], 10);
            if(v[0] in this.colorStyles){
                return '<div class="x-grid3-cell-inner" '+this.colorStyles[v[0]]+'"><span style="color:black;">' + value + '</span></div>';
            }
            else{
//                return '<div class="x-grid3-cell-inner" '+this.colorStyles['W']+'"><span style="color:black;">' + value + '</span></div>';
            }
        }
        return value || 0;
    },
    metaDataChanged : function(store) {
        console.log('metaDataChanged');
        var newColumns = [{
            header : 'Resource',
            dataIndex : 'resource',
            //align: 'right',
            width : 80
        }, {
            header : 'App Kernel',
            dataIndex : 'appKer',
            //align: 'right',
            width : 90

        }, {
            header : 'Nodes',
            dataIndex : 'problemSize',
            align : 'right',
            width : 50
        }];
        var nLocked = newColumns.length;
        for (var i = nLocked; i < this.store.fields.getCount(); i++) {
            var value = this.store.fields.itemAt(i).name;
            var m_date = value.split('/');
            if (value.indexOf('Failed') >= 0)
                continue;
            if (value.indexOf('InControl') >= 0)
                continue;
            if (value.indexOf('OutOfControl') >= 0)
                continue;
            if (value.indexOf('IDs') >= 0)
                continue;

            var day = m_date[2];

            newColumns.push({
                header : day,
                dataIndex : value,
                align : 'center',
                renderer : {
                    fn : this.rendererForCell,
                    scope : this
                },
                width : 40
            });
        }
        var newColModel = new Ext.grid.ColumnModel({
            defaults : {
                sortable : false
            },
            columns : newColumns
        });

        //var i;
        //for(i=3;i<this.store.)
        console.log('metaDataChanged');

        //mounth header
        this.dateGroup[0].length = 1;
        //this.dateGroup[1].length = 1;
        var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        for (var i = nLocked; i < this.store.fields.getCount(); i++) {
            var value = this.store.fields.itemAt(i).name;
            var m_date = value.split('/');
            if (value.indexOf('IDs') >= 0)continue;

            var monthYear = monthNames[parseInt(m_date[1]) - 1] + ", " + m_date[0];

            if (this.dateGroup[0][this.dateGroup[0].length - 1].header == monthYear)
                this.dateGroup[0][this.dateGroup[0].length - 1].colspan++;
            else
                this.dateGroup[0].push({
                    header : monthYear,
                    colspan : 1,
                    align : 'center'
                });
        }
        newColModel.rows = this.dateGroup;

        this.reconfigure(this.store, newColModel);
    },

    constructor : function(config) {
        config = config || {};

        Ext.applyIf(config, {
            //title: 'AppKer Success Rate'
        });

        //this.store = new XDMoD.Arr.AppKerPerformanceMapStore();
        this.store = new Ext.data.JsonStore({
            //url : 'controllers/arr_controller.php',
            baseParams : {
                //operation : 'get_performance_map'
            },
            proxy: XDMoD.REST.createHttpProxy({
                url: 'app_kernels/performance_map',
                method: 'GET'
            })
        });

        this.store.on("metadatachanged", this.metaDataChanged, this);
        this.store.on("datachanged", this.metaDataChanged, this);
        this.store.on("reconfigure", this.metaDataChanged, this);

        this.dateGroup = [[{
            header : "",
            colspan : 3,
            align : 'center'
        }]/*,[{header:"",colspan:3,align:'center'}]*/];

        var dateHeader = new Ext.ux.grid.ColumnHeaderGroup({
            rows : this.dateGroup
        });
        this.plugins = [dateHeader];

        Ext.apply(config, {
            id : 'appKerPerformanceMapGrid',
            cls : 'appKerPerfMap_grid',
            columnLines : true,
            enableColumnMove : false,
            enableColumnHide : false,
            colModel : new Ext.ux.grid.LockingColumnModel({

                defaults : {
                    sortable : false
                },
                columns : [{
                    header : 'Resource',
                    dataIndex : 'resource'
                    //align: 'right',
                    //width: 100
                }, {
                    header : 'App Kernel',
                    dataIndex : 'appKer',
                    //align: 'right',
                    width : 100
                }, {
                    header : 'Nodes',
                    dataIndex : 'problemSize',
                    align : 'right',
                    width : 60
                }]
            }),
            plugins : dateHeader,
            selModel : new Ext.grid.CellSelectionModel({
                singleSelect : true
            })
        });

        XDMoD.Arr.AppKerPerformanceMapGrid.superclass.constructor.call(this, config);
    }
});

XDMoD.Arr.AppKerPerformanceMapPanel = function(config) {
    XDMoD.Arr.AppKerPerformanceMapPanel.superclass.constructor.call(this, config);
};

Ext.apply(XDMoD.Arr.AppKerPerformanceMapPanel, {
    //static stuff

}); 



Ext.extend(XDMoD.Arr.AppKerPerformanceMapPanel, Ext.Panel, {
    title : 'Performance Map',
    resourcesList : ["blacklight", "edge", "edge12core", "lonestar4", "kraken", "trestles", "gordon", "stampede"],
    problemSizeList : [1, 2, 4, 8, 16],
    appKerList : ["xdmod.app.astro.enzo", "xdmod.app.chem.gamess", "xdmod.app.chem.nwchem", "xdmod.app.md.namd", "xdmod.benchmark.hpcc", "xdmod.benchmark.io.ior", "xdmod.benchmark.io.mpi-tile-io", "xdmod.benchmark.mpi.imb", "xdmod.benchmark.graph.graph500", "xdmod.bundle"],
    legend:
    'Each day summarized in table cell as pair of a symbol and a number.'+
    'Symbol represent the status of last application kernel execution on that day and number'+
    ' shows total number of runs. Each cell is colored according to the statu'+
    's of last application kernel run. Below is the codes description: <br/><'+
    'br/><table border="1" cellspacing="0"><tr><td>Code</td><t'+
    'd>Description</td></tr><tr><td style="background-color:#B0FFC5;">N</td>'+
    '<td>Application kernel was executed within control interval</td></tr><tr>'+
    '<td style="background-color:#F7FE2E;">U</td><td>Application kernel w'+
    'as under-performing</td></tr><tr><td style="background-color:#FE9A2E;"'+
    '>O</td><td>Application kernel was over-performing</td></tr><tr><td style'+
    '="background-color:#FFB0C4;">F</td><td>Application kernel failed to ru'+
    'n</td></tr><tr><td style="background-color:#81BEF7;">C</td><td>This ru'+
    'n was used to calculate control region</td></tr><tr><td style="backgro'+
    'und-color:#F781F3;">R</td><td>Application kernel have run, but control i'+
    'nformation is not available</td></tr><tr><td style="background-color:white;">'+
    '</td><td>There was no application kernel runs</td></tr></table>'+
    'Select cell for more details',
    getSelectedResources : function() {
        /*var resources = [];
        var selNodes = this.resourcesTree.getChecked();
        Ext.each(selNodes, function(node) {
            //if(!node.disabled)
            resources.push(node.text);
        });
        return resources;*/
        return null;
    },
    getSelectedProblemSizes : function() {
        /*var problemSize = [];
        var selNodes = this.problemSizesTree.getChecked();
        Ext.each(selNodes, function(node) {
            //if(!node.disabled)
            problemSize.push(node.text);
        });
        return problemSize;*/
        return null;
    },
    getSelectedAppKers : function() {
        /*var appKers = [];
        var selNodes = this.appKerTree.getChecked();
        Ext.each(selNodes, function(node) {
            //if(!node.disabled)
            appKers.push(node.text);
        });
        return appKers;*/
        return null;
    },
    initComponent : function() {
        var appKerPerformanceMapGrid = new XDMoD.Arr.AppKerPerformanceMapGrid({
            scope : this,
            region : "center"
        });

        this.appKerPerformanceMapGrid = appKerPerformanceMapGrid;

        var resourceChildren = [];
        for (var i = 0; i < this.resourcesList.length; i++) {
            var resource = this.resourcesList[i];
            resourceChildren.push({
                text : resource,
                nick : resource,
                type : "resource",
                checked : true,
                iconCls : "resource",
                leaf : true
            })
        }

        this.resourcesTree = new Ext.tree.TreePanel({
            title : 'Resources',
            id : 'tree_resources_' + this.id,
            useArrows : true,
            autoScroll : true,
            animate : false,
            enableDD : false,
            region : 'north',
            //height: 200,
            root : new Ext.tree.AsyncTreeNode({
                nodeType : 'async',
                text : 'Resources',
                draggable : false,
                id : 'resources',
                expanded : true,
                children : resourceChildren
            }),
            rootVisible : false,
            containerScroll : true,
            tools : [{
                id : 'unselect',
                qtip : 'De-select all selected resources.',
                scope : this,
                handler : function() {
                    this.resourcesTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.resourcesTree.getRootNode().cascade(function(n) {
                        var ui = n.getUI();
                        if (ui.isChecked())
                            selectAll = false;
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.resourcesTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (!ui.isChecked())
                                ui.toggleCheck(true);
                            lastNode = n;
                        });
                    }
                    else {
                        this.resourcesTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (ui.isChecked())
                                ui.toggleCheck(false);
                            lastNode = n;
                        });
                    }
                    if (lastNode)
                        reloadAll.call(this);
                    this.resourcesTree.on('checkchange', reloadAll, this);
                }
            }, {
                id : 'refresh',
                qtip : 'Refresh',
                hidden : true,
                scope : this,
                handler : reloadAll
            }],
            margins : '0 0 0 0',
            border : false,
            split : true,
            flex : 4
        });

        var problemSizeChildren = [];
        for (var i = 0; i < this.problemSizeList.length; i++) {
            var nodesSize = this.problemSizeList[i];
            problemSizeChildren.push({
                text : String(nodesSize),
                qtip : (nodesSize == 1) ? nodesSize + "node" : nodesSize + "nodes",
                type : "node",
                checked : true,
                iconCls : "node",
                leaf : true
            });
        }

        this.problemSizesTree = new Ext.tree.TreePanel({
            flex : 0.5,
            title : "Problem Size (Cores or Nodes)",
            id : 'tree_nodes_' + this.id,
            useArrows : true,
            autoScroll : true,
            animate : false,
            enableDD : false,
            // loader: nodesTreeLoader,

            root : new Ext.tree.AsyncTreeNode({
                nodeType : 'async',
                text : 'Resources',
                draggable : false,
                id : 'resources',
                expanded : true,
                children : problemSizeChildren
            }),
            tools : [{
                id : 'unselect',
                qtip : 'De-select all selected resources.',
                scope : this,
                handler : function() {
                    this.problemSizesTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.problemSizesTree.getRootNode().cascade(function(n) {
                        var ui = n.getUI();
                        if (ui.isChecked())
                            selectAll = false;
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.problemSizesTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (!ui.isChecked())
                                ui.toggleCheck(true);
                            lastNode = n;
                        });
                    }
                    else {
                        this.problemSizesTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (ui.isChecked())
                                ui.toggleCheck(false);
                            lastNode = n;
                        });
                    }
                    if (lastNode)
                        reloadAll.call(this);
                    this.problemSizeTree.on('checkchange', reloadAll, this);
                }
            }, {
                id : 'refresh',
                qtip : 'Refresh',
                hidden : true,
                scope : this,
                handler : reloadAll
            }],
            rootVisible : false,
            containerScroll : true,
            margins : '0 0 0 0',
            border : false,
            flex : 2
        });

        var appKerChildren = [];
        for (var i = 0; i < this.appKerList.length; i++) {
            var appker = this.appKerList[i];
            appKerChildren.push({
                        text : appker,
                        nick : appker,
                        type : "app_kernel",
                        checked : true,
                        iconCls : "appkernel",
                        leaf : true
                    });
        }

        this.appKerTree = new Ext.tree.TreePanel({
            title : 'App Kernels',
            id : 'tree_appker_' + this.id,
            useArrows : true,
            autoScroll : true,
            animate : false,
            enableDD : false,
            region : 'north',
            //height: 200,
            root : new Ext.tree.AsyncTreeNode({
                nodeType : 'async',
                text : 'App Kernels',
                draggable : false,
                id : 'appker',
                expanded : true,
                children : appKerChildren
            }),
            tools : [{
                id : 'unselect',
                qtip : 'De-select all selected resources.',
                scope : this,
                handler : function() {
                    this.appKerTree.un('checkchange', reloadAll, this);
                    var lastNode = null;
                    var selectAll = true;

                    this.appKerTree.getRootNode().cascade(function(n) {
                        var ui = n.getUI();
                        if (ui.isChecked())
                            selectAll = false;
                        lastNode = n;
                    });

                    if (selectAll) {
                        this.appKerTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (!ui.isChecked())
                                ui.toggleCheck(true);
                            lastNode = n;
                        });
                    }
                    else {
                        this.appKerTree.getRootNode().cascade(function(n) {
                            var ui = n.getUI();
                            if (ui.isChecked())
                                ui.toggleCheck(false);
                            lastNode = n;
                        });
                    }
                    if (lastNode)
                        reloadAll.call(this);
                    this.appKerTree.on('checkchange', reloadAll, this);
                }
            }, {
                id : 'refresh',
                qtip : 'Refresh',
                hidden : true,
                scope : this,
                handler : reloadAll
            }],
            rootVisible : false,
            containerScroll : true,
            margins : '0 0 0 0',
            border : false,
            split : true,
            flex : 4
        });
        var leftPanel = new Ext.Panel({
            split : true,
            collapsible : true,
            title : 'App Kernel/Resource Query',
            //collapseMode: 'mini',
            //header: false,
            width : 325,
            layout : {
                type : 'vbox',
                align : 'stretch'
            },
            region : 'west',
            margins : '2 0 2 2',
            border : true,
            plugins : new Ext.ux.collapsedPanelTitlePlugin(),
            items : [this.resourcesTree, this.problemSizesTree, this.appKerTree]
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
            id : 'commentsPanel',
            region : 'south',
            autoScroll : true,
            border : true,
            collapsible : true,
            split : true,
            title : 'Description',
            height : 130,
            html : this.legend
        });
        //commentsPanel
        this.appKerPerformanceMapGrid.getSelectionModel().on('cellselect', function(sm, rowIdx, colIndex) {
            /*populate detailed view pannele with arr job ids*/
            var i;
            var j;

            var selCell = sm.getSelectedCell();

            var detailPanel = Ext.getCmp('commentsPanel');
            var dataIndex = sm.grid.getColumnModel().getDataIndex(colIndex);
            var record = sm.grid.getStore().getAt(rowIdx);

            var failedRunsStr = '';
            var outOfControlRunsStr = '';
            var inControlRunsStr = '';
            var dataIndexAll = [dataIndex];
            //if columns with resource or appkernel name is selected show all jobs for the queried period
            if (colIndex <= 2) {
                dataIndexAll = [];
                for ( j = 3; j < record.fields.getCount(); j++) {
                    var key = record.fields.getKey(record.fields.itemAt(j));
                    if (key.indexOf('Failed') >= 0)
                        continue;
                    if (key.indexOf('InControl') >= 0)
                        continue;
                    if (key.indexOf('OutOfControl') >= 0)
                        continue;
                    dataIndexAll.push(key);
                }
            }
            //pack jobs
            var iStatus;
            var statuses=['F','U','N','O','C','R'];
            var ref={'F':'failedJobs',
                'U':'underPerformingJobs',
                'N':'inControlJobs',
                'O':'overPerformingJobs',
                'C':'controlJobs',
                'R':'noControlInfoJobs'};
            var jobsIDs={'F':'',
                'U':'',
                'N':'',
                'O':'',
                'C':'',
                'R':''};
            
            for ( j = dataIndexAll.length - 1; j >= 0; j--) {
                var dataIndexRun = dataIndexAll[j];
                var s;
                for(iStatus=0;iStatus<statuses.length;iStatus++){
                    s = record.get(dataIndexRun + '-IDs-'+statuses[iStatus]);
                    if ( typeof s !== 'undefined' && s !== '' && s !== ' ') {
                        var runs = s.split(',');
                        for ( i = 0; i < runs.length; i++) {
                            runs[i] = parseInt(runs[i], 10);
                        };
                        runs.sort(function(a, b) {
                            return b - a;
                        });
                        for ( i = 0; i < runs.length; i++) {
                            if (jobsIDs[statuses[iStatus]] !== '')
                                jobsIDs[statuses[iStatus]] += ', ';
                            jobsIDs[statuses[iStatus]] += '<a href="#" onclick="javascript:var iw=new XDMoD.AppKernel.InstanceWindow({instanceId:' + runs[i] + '});iw.show()">' + runs[i] + '</a>';
                        }
                    }
                }
            }
            var dataValue = record.get(dataIndex);
            var values = {
                appKer : record.get('appKer'),
                resource : record.get('resource'),
                rowIdx : rowIdx,
                colIndex : colIndex,
                dataIndex : dataIndex,
                dataValue : dataValue
            };
            for(iStatus=0;iStatus<statuses.length;iStatus++){
                values[ref[statuses[iStatus]]]=jobsIDs[statuses[iStatus]];
            }
            commentsTemplate.overwrite(detailPanel.body, values);
        });

        var viewPanel = new Ext.Panel({
            layout : 'border',
            region : 'center',
            items : [this.appKerPerformanceMapGrid, this.commentsPanel],
            border : true
        });
        //viewPanel

        this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
            id : 'duration_selector_' + this.id,
            alignRight : false,
            showRefresh : true,
            showAggregationUnit : false,
            handler : function() {
                reloadAll.call(this);
            },
            //handler:  this.reloadAll,
            scope : this //also scope of handle
        });

        this.durationToolbar.dateSlider.region = 'south';

        function exportFunction(format, showTitle, scale, width, height) {
            var parameters = appKerPerformanceMapGrid.store.baseParams;
            
            parameters['format'] = format;

            CCR.invokePost("controllers/arr_controller.php", parameters, {
                checkDashboardUser: true
            });
        };
        var exportButton = new Ext.Button({
            id : 'export_button_' + this.id,
            text : 'Export',
            iconCls : 'export',
            tooltip : 'Export chart data',
            menu : [{
                text : 'CSV - comma Separated Values',
                iconCls : 'csv',
                handler : function() {
                    exportFunction('csv', false);
                }
            }]
        });
        this.durationToolbar.addItem('-');
        this.durationToolbar.addItem(exportButton);

        var getBaseParams = function() {
            var selectedResources = this.getSelectedResources();
            var selectedProblemSizes = this.getSelectedProblemSizes();
            var selectedAppKers = this.getSelectedAppKers();

            var baseParams = {};
            baseParams.start_date = this.durationToolbar.getStartDate().format('Y-m-d');
            baseParams.end_date = this.durationToolbar.getEndDate().format('Y-m-d');
            if(selectedResources!==null)
                baseParams.resources = selectedResources.join(';');
            if(selectedProblemSizes!==null)
                baseParams.problemSizes = selectedProblemSizes.join(';');
            if(selectedAppKers!==null)
                baseParams.appKers = selectedAppKers.join(';');
            /*baseParams.showAppKer=this.showAppKerCheckbox.getValue();
             baseParams.showAppKerTotal=this.showAppKerTotalCheckbox.getValue();
             baseParams.showResourceTotal=this.showResourceTotalCheckbox.getValue();
             baseParams.showUnsuccessfulTasksDetails=this.showUnsuccessfulTasksDetailsCheckbox.getValue();
             baseParams.showSuccessfulTasksDetails=this.showSuccessfulTasksDetailsCheckbox.getValue();
             baseParams.showInternalFailureTasks=this.showInternalFailureTasksCheckbox.getValue();*/

            baseParams.format = 'json';
            return baseParams;
        };

        this.appKerPerformanceMapGrid.store.on('beforeload', function() {
            if (! this.durationToolbar.validate())
                return;

            var baseParams = {};
            Ext.apply(baseParams, getBaseParams.call(this));

            //baseParams.operation = 'get_performance_map';

            this.appKerPerformanceMapGrid.store.baseParams = baseParams;

        }, this);

        function reloadAll() {
            this.appKerPerformanceMapGrid.store.load();
        }


        Ext.apply(this, {
            layout : 'border',
            tbar : this.durationToolbar,
            items : [viewPanel]//, leftPanel]
            /*{items: [new Ext.Button({text: 'Refresh',
             handler: function() {
             CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);
             }
             })]
             }*/
        });
        //Ext.apply

        XDMoD.Arr.AppKerPerformanceMapPanel.superclass.initComponent.apply(this, arguments);
    }//initComponent
});
//XDMoD.Arr.AppKerPerformanceMapPanel

