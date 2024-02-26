/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */


Ext.namespace('XDMoD', 'XDMoD.Arr','CCR', 'CCR.xdmod', 'CCR.xdmod.ui');
Ext.QuickTips.init();  // enable tooltips

if (!('map' in Array.prototype)) {
   Array.prototype.map= function(mapper, that /*opt*/) {
       var other= new Array(this.length);
       for (var i= 0, n= this.length; i<n; i++)
           if (i in this)
               other[i]= mapper.call(that, this[i], i, this);
       return other;
   };
}

XDMoD.Arr.AppKerSuccessRatePlotPanel = function (config)
{
   XDMoD.Arr.AppKerSuccessRatePlotPanel.superclass.constructor.call(this, config);
}; 

Ext.apply(XDMoD.Arr.AppKerSuccessRatePlotPanel,
{
    //static stuff
   
});

Ext.extend(XDMoD.Arr.AppKerSuccessRatePlotPanel, Ext.Panel, {
   chartStore: false,

   mask: function(message)
   {
     var viewer = CCR.xdmod.ui.Viewer.getViewer();
     if (viewer.el) viewer.el.mask();
   },

   unmask: function(message)
   {
     var viewer = CCR.xdmod.ui.Viewer.getViewer();
     if (viewer.el) viewer.el.unmask();
   },

   // --------------------------------------------------------------------------------
   // Load the query.
   // --------------------------------------------------------------------------------

   reloadChart: function()
   {
     this.chartStore.load();
   },
   
   title: 'App Kernel Success Rates Plot',
   resourcesList:["alamo",
   "blacklight",
   "edge",
   "edge12core",
   "edgegpu",
   "gordon",
   "hotel",
   "india",
   "keeneland",
   "kraken",
   "lonestar4",
   "ranger",
   "sierra",
   "trestles",
   "xray"],
   nodesSizeList:[1,2,4,8,16],
   coresSizeList:[8,16,32,64,128],
   appKerList:[
      "xdmod.app.chem.gamess",
      "xdmod.app.chem.nwchem",
      "xdmod.app.climate.cesm",
      "xdmod.app.climate.wrf",
      "xdmod.app.md.amber",
      "xdmod.app.md.charmm",
      "xdmod.app.md.cpmd",
      "xdmod.app.md.lammps",
      "xdmod.app.md.namd",
      "xdmod.app.md.namd-gpu",
      "xdmod.app.phys.quantum_espresso",
      "xdmod.benchmark.gpu.shoc",
      "xdmod.benchmark.graph.graph500",
      "xdmod.benchmark.hpcc",
      "xdmod.benchmark.io.ior",
      "xdmod.benchmark.io.mpi-tile-io",
      "xdmod.benchmark.mpi.imb",
      "xdmod.benchmark.mpi.omb",
      "xdmod.benchmark.npb",
      "xdmod.benchmark.osjitter"

   ],
   getSelectedResources: function()
   {
      var resources = [];
      var selNodes = this.resourcesTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         resources.push(node.text);
      });
      return resources;
   },
   getSelectedNodeCounts: function()
   {
      var nodes = [];
      var selNodes = this.nodesTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         nodes.push(node.text);
      });
      return nodes;
   },
   getSelectedCoreCounts: function()
   {
      var cores = [];
      var selNodes = this.coresTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         cores.push(node.text);
      });
      return cores;
   },
   getSelectedAppKers: function()
   {
      var appKers = [];
      var selNodes = this.appKerTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         appKers.push(node.text);
      });
      return appKers;
   },
   //region:'center',
   initComponent: function(){
      var chartScale = 1;
      var chartWidth = 757;
      var chartHeight = 400;
      
      // Interrogate various components for parameters to send to the chart controller

      var getBaseParams = function ()
      {
        var baseParams = {};
        baseParams.title = "title";// titleField.getValue();
        baseParams.subtitle ="subtitle";//  subtitleField.getValue();
        baseParams.query = "query";// queryComboBox.getValue();
        baseParams.limit = "limit";// numberField.getValue();
        baseParams.start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
        baseParams.end_date =  this.durationToolbar.getEndDate().format('Y-m-d');
        baseParams.timeseries = 'n';
        baseParams.aggregation_unit = 'Auto';
        
        var selResources = this.getSelectedResources();
        var selNodes =  this.getSelectedNodeCounts();
        var selCores =  this.getSelectedCoreCounts();
        var selAppKers =  this.getSelectedAppKers();
        
        if(selResources.length>0)
           baseParams.resource=selResources[0];
        else
           baseParams.resource="None";
        if(selCores.length>0)
           baseParams.ncpus=selCores[0];
        else
           baseParams.ncpus="8";
        
        if(selAppKers.length>0)
           baseParams.appKer=selAppKers[0];
        else
           baseParams.appKer="None";
        
        return baseParams;
      };
      
      
      var chartStore = new CCR.xdmod.CustomJsonStore({
        storeId: 'hchart_store_' + this.id,
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
        baseParams:
        {
          operation: 'get_arr_ak_status'
        },
        proxy: new Ext.data.HttpProxy(
          {
            method: 'POST',
            url: 'controllers/controller.php'
          })
      });

      this.chartStore = chartStore;

      chartStore.on('beforeload', function() {
        if ( ! this.durationToolbar.validate() ) return;

        this.mask('Loading...');
        plotlyPanel.un('resize', onResize, this); 

        chartStore.baseParams = {};
        Ext.apply(this.chartStore.baseParams, getBaseParams.call(this));
        
        maximizeScale.call(this);
           
        chartStore.baseParams.timeframe_label = this.durationToolbar.getDurationLabel(),
        chartStore.baseParams.operation = 'get_data';
        chartStore.baseParams.scale = 1;
        chartStore.baseParams.format = 'hc_jsonstore';
        chartStore.baseParams.width = chartWidth*chartScale;
        chartStore.baseParams.height = chartHeight*chartScale;
        
      }, this);
      
      chartStore.on('load', function(chartStore) {
         this.firstChange = true;
         if (chartStore.getCount() != 1) 
         {
           this.unmask();
           return;
         }

         /*
         var reportGeneratorMeta = chartStore.getAt(0).get('reportGeneratorMeta');
         cbAvailableForReport.storeChartArguments(reportGeneratorMeta.chart_args,
                                                  reportGeneratorMeta.title,
                                                  reportGeneratorMeta.params_title,
                                                  reportGeneratorMeta.start_date,
                                                  reportGeneratorMeta.end_date,
                                                  reportGeneratorMeta.included_in_report);
         */                                        

         plotlyPanel.on('resize', onResize, this); //re-register this after loading/its unregistered beforeload
         this.unmask();
       },this);

       // Duration toolbar contains the preset and custom date fields by default.  Also add the export
       // and print buttons.

       var printButton = new Ext.Button({
         id: 'print_button_'+this.id,
         text: 'Print',
         iconCls: 'print',
         tooltip: 'Print chart',
         scope: this,
         handler: function()
         {
           var parameters = chartStore.baseParams;
           
             parameters['operation'] = 'get_data';
             parameters['scale'] = CCR.xdmod.ui.hd1280Scale;
             parameters['format'] = 'png';
           // parameters['start'] = this.chartPagingToolbar.cursor;
           // parameters['limit'] = this.chartPagingToolbar.pageSize;
             parameters['width'] = 757;
             parameters['height'] = 400;                       
             
             var params = '';
             for(i in parameters)
             {
               params += i + '=' + parameters[i] +'&';
             }
             params = params.substring(0,params.length-1);
             Ext.ux.Printer.print(
               { 
                 getXTypes: function () { return 'html';}, 
                 html: '<img src="/controllers/custom_query.php?'+params+'" />'
               });
             
           }
         });

       function exportFunction(format, showTitle, scale, width, height)
       {
         var parameters = chartStore.baseParams;
         
         parameters['scale'] = scale || 1;
         parameters['show_title'] = showTitle;
         parameters['format'] = format;
         parameters['inline'] = 'n';
         parameters['width'] =  width || 757;
         parameters['height'] = height || 400;
         if(format == 'svg') parameters['font_size'] = 0;
         
         CCR.invokePost("controllers/custom_query.php", parameters, {
             checkDashboardUser: true,
         });                
       }

       var exportButton = new Ext.Button({
         id: 'export_button_' + this.id,
         text: 'Export',
         iconCls: 'export',
         tooltip: 'Export chart data',
         menu: [
           {
             text: 'PNG - Portable Network Graphics', iconCls: 'png',
             handler: function ()
             {
               exportFunction('png', false, 1,916,484);
             }
           }, {
             text: 'PNG - Portable Network Graphics - Small', iconCls: 'png',
             handler: function ()
             {
               exportFunction('png', false, 1, 640, 380);
             }
           }, {
             text: 'PNG - Portable Network Graphics - HD', iconCls: 'png',
             handler: function ()
             {
               exportFunction('png', false, 1, 1280, 720);
             }
           }, {
             text: 'PNG - Portable Network Graphics - Poster', iconCls: 'png',
             handler: function ()
             {
               exportFunction('png', false, 1, 1920, 1080);
             }
           }, {
             text: 'SVG - Scalable Vector Graphics', iconCls: 'png',
             handler: function ()
             {
               exportFunction('svg', false, 1,757,400);
             }
           }
         ]
       });
       this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
          id: 'duration_selector_' +  this.id,
          alignRight: false,
          showRefresh: true,
          showAggregationUnit: false,
          handler:  this.reloadChart,//function () {reloadAll.call(this);},
         
          scope: this //also scope of handle
       });
       
       this.durationToolbar.dateSlider.region = 'south';
       this.durationToolbar.addItem('-');
       this.durationToolbar.addItem(exportButton);
       this.durationToolbar.addItem('-');
       this.durationToolbar.addItem(printButton);
       
      this.resourcesTree = new Ext.tree.TreePanel(
      {
         title: 'Resources',
         id: 'tree_resources_' + this.id,
         useArrows: true,
         autoScroll: true,
         animate: false,
         enableDD: false,
         region: 'north',
         //height: 200,
         root: new Ext.tree.AsyncTreeNode(
         {
            nodeType: 'async',
            text: 'Resources',
            draggable: false,
            id: 'resources',
            expanded: true,
            children: this.resourcesList.map(function (resource){
               return {
                  text:resource,
                  nick:resource,
                  type:"resource",
                  checked:false,
                  iconCls:"resource",
                  leaf: true
                  };
            })
         }),
         rootVisible: false,
         containerScroll: true,
         tools: [
         {
            id: 'unselect',
            qtip: 'De-select all selected resources.',
            scope: this,
            handler: function()
            {
              this.resourcesTree.un('checkchange',reloadAll,this);
              var lastNode = null;
              this.resourcesTree.getRootNode().cascade(function(n) {
                 var ui = n.getUI();
                 if(ui.isChecked()) ui.toggleCheck(false);
                 lastNode = n;
              });
              if(lastNode) reloadAll.call(this);
              this.resourcesTree.on('checkchange',reloadAll,this);

           }
        },
        {
           id: 'refresh',
           qtip: 'Refresh',
           hidden: true,
           scope: this,
           handler: reloadAll
        }
        ],
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
      this.resourcesTree.on('checkchange',reloadAll,this);
      this.nodesTree = new Ext.tree.TreePanel({
         flex: 0.5,
         title: "Nodes",
          id: 'tree_nodes_' + this.id,
          useArrows: true,
          autoScroll: true,
          animate: false,
          enableDD: false,
         // loader: nodesTreeLoader,

          root:new Ext.tree.AsyncTreeNode(
             {
                nodeType: 'async',
                text: 'Resources',
                draggable: false,
                id: 'resources',
                expanded: true,
                children: this.nodesSizeList.map(function (nodesSize){
                   return {
                      text:String(nodesSize),
                      qtip:(nodesSize==1)?nodesSize+"node":nodesSize+"nodes",
                      type:"node",
                      checked:false,
                      iconCls:"node",
                      leaf: true
                      };
                })
             }),
          rootVisible: false,
          containerScroll: true,
          margins: '0 0 0 0',
          border: false
      });
      this.coresTree = new Ext.tree.TreePanel({
         flex: 0.5,
         title: "Cores",
          id: 'tree_cores_' + this.id,
          useArrows: true,
          autoScroll: true,
          animate: false,
          enableDD: false,
         // loader: nodesTreeLoader,

          root:new Ext.tree.AsyncTreeNode(
             {
                nodeType: 'async',
                text: 'Cores',
                draggable: false,
                id: 'cores',
                expanded: true,
                children: this.coresSizeList.map(function (coresSize){
                   return {
                      text:String(coresSize),
                      qtip:coresSize+" cores",
                      type:"core",
                      checked:false,
                      iconCls:"units",
                      leaf: true
                      };
                })
             }),
          rootVisible: false,
          containerScroll: true,
          margins: '0 0 0 0',
          border: false
      });
      
      var processingUnitsPanel = new Ext.Panel({
         //region: 'north',
         //height: 140,
         layout:'hbox',
         margins: '0 0 0 0',
          border: false,
         layoutConfig: {
            align: 'stretch'
         },
         items: [this.nodesTree, this.coresTree],
         flex: 2
      });
      this.appKerTree = new Ext.tree.TreePanel(
      {
         title: 'App Kernels',
         id: 'tree_appker_' + this.id,
         useArrows: true,
         autoScroll: true,
         animate: false,
         enableDD: false,
         region: 'north',
         //height: 200,
         root: new Ext.tree.AsyncTreeNode(
         {
            nodeType: 'async',
            text: 'App Kernels',
            draggable: false,
            id: 'appker',
            expanded: true,
            children: this.appKerList.map(function (appker){
               return {
                  text:appker,
                  nick:appker,
                  type:"app_kernel",
                  checked:false,
                  iconCls:"appkernel",
                  leaf: true
                  };
            })
         }),
         rootVisible: false,
         containerScroll: true,
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
     var leftPanel = new Ext.Panel({
         split: true,
         collapsible: true,
         title: 'App Kernel/Resource Query',
         //collapseMode: 'mini',
         //header: false,
         width: 325,
         layout:{
            type:'vbox',
            align:'stretch'
         },
         region: 'west',
         margins: '2 0 2 2',
         border: true,
         plugins: new Ext.ux.collapsedPanelTitlePlugin(),
         items: [this.resourcesTree,processingUnitsPanel,this.appKerTree]
      });
     
     
  // Plotly panel to render from the chart store

     var plotlyPanel = new CCR.xdmod.ui.PlotlyPanel({
       id: `plotly-panel${this.id}`,
       store: chartStore
     });

     // Needed to allow resize
     
     var chartPanel = new Ext.Panel({
       region: 'center',
       layout: 'fit',
       header: false,
       tools: [
       ],
       border: false,
       items: [plotlyPanel]
     });
     
     
      
      var viewPanel = new Ext.Panel({
         frame: false,
         border: false,
         //layout: 'border',
         activeItem: 0, // make sure the active item is set on the container config!
         defaults: {
             // applied to each contained panel
             border: false
         },
         region: 'center',
         items: [chartPanel]
     });
       var commentsTemplate = new Ext.XTemplate(
         '<table class="xd-table">',
         '<tr>',
         '<td width="100%">',
            '<span class="comments_subnotes">{subnotes}</span>',
         '</td>',
         '</tr>',
         '<tr>',
            '<td width="100%">',
            '<span class="comments_description">{comments}</span>',
            '</td>',
         '</tr>',
         '</table>'
       );
     var descriptionPanel = new Ext.Panel(
         {
             region: 'south',
             autoScroll: true,
             collapsible: true,
             split: true,
             title: 'Description',
             height: 120,
            plugins: [new Ext.ux.collapsedPanelTitlePlugin()]
         });
      function updateDescription(comments, legend, legendTitle, showLegend, subNotes)
      {
       commentsTemplate.overwrite(descriptionPanel.body, { 'comments': comments, 'subnotes': subNotes});
      }
      //
      var images = new Ext.Panel(
         {
             title: 'Viewer',
             region: 'center',
             margins: '2 1 2 0',
             layout: 'border',
             scope:  this,
             items: [viewPanel, descriptionPanel]
         }
         );
      
      
      function reloadAll()
      {
         var selResources = this.getSelectedResources();
         var selNodes =  this.getSelectedNodeCounts();
         var selCores =  this.getSelectedCoreCounts();
         var selAppKers =  this.getSelectedAppKers();
         
         if(selResources.length==0)return;
         if(selAppKers.length==0)return;
         if(!(selCores.length>0 || selNodes.length>0))return;
         
         this.chartStore.load();
         
         /*reloadResources.call(this);
         reloadCores.call(this);
         reloadNodes.call(this);
         reloadMetrics.call(this);
         reloadChart.call(this,150);      */
      }
      
      this.on('render',reloadAll,this, {single: true});
      
      // Determine the max scale for the chart (needed to handle window resizes)

      function maximizeScale()
      {
        var vWidth = plotlyPanel.getWidth();
        var vHeight = plotlyPanel.getHeight() - (chartPanel.tbar? chartPanel.tbar.getHeight() : 0);
        
        chartScale =  ((vWidth / 757) + (vHeight / 400))/2;
        
        if (chartScale < CCR.xdmod.ui.minChartScale) 
        {
          chartScale = CCR.xdmod.ui.minChartScale;
        }
        var aspect = vWidth/vHeight;
           
        if (aspect < 0.5) //width is less than the height
        {
          chartWidth = plotlyPanel.getWidth()/chartScale;
          chartHeight = (plotlyPanel.getWidth()/0.5)/chartScale;
        }
        else if (aspect > 4) //width is more than 4 times of the height
        {
          chartWidth = plotlyPanel.getWidth()/chartScale;
          chartHeight = (plotlyPanel.getWidth()/4)/chartScale;
        }
        else
        {
          chartWidth = plotlyPanel.getWidth()/chartScale;
          chartHeight = plotlyPanel.getHeight()/chartScale;
        }     
      }  // maximizeScale()

      // Resize the chart view when the window is resized

      function onResize(t, adjWidth, adjHeight)
      {
        maximizeScale.call(this);
        const chartDiv = document.getElementById(`plotly-panel${this.id}`);
        if (chartDiv) {
            Plotly.relayout(`plotly-panel${this.id}`, { width: adjWidth, height: adjHeight });
            if (chartDiv._fullLayout.annotations.length !== 0) {
                const topCenter = topLegend(chartDiv._fullLayout);
                const subtitleLineCount = adjustTitles(chartDiv._fullLayout);
                const marginTop = Math.min(chartDiv._fullLayout.margin.t, chartDiv._fullLayout._size.t);
                const marginRight = chartDiv._fullLayout._size.r;
                const legendHeight = (topCenter && !(adjHeight <= 550)) ? chartDiv._fullLayout.legend._height : 0;
                const titleHeight = 31;
                const subtitleHeight = 15;
                const update = {
                    'annotations[0].yshift': (marginTop + legendHeight) - titleHeight,
                    'annotations[1].yshift': ((marginTop + legendHeight) - titleHeight) - (subtitleHeight * subtitleLineCount)
                };

                if (chartDiv._fullLayout.annotations.length >= 2) {
                    const marginBottom = chartDiv._fullLayout._size.b;
                    const plotAreaHeight = chartDiv._fullLayout._size.h;
                    let pieChartXShift = 0;
                    if (chartDiv._fullData.length !== 0 && chartDiv._fullData[0].type === 'pie') {
                        pieChartXShift = subtitleLineCount > 0 ? 2 : 1;
                    }
                    update['annotations[2].yshift'] = (plotAreaHeight + marginBottom) * -1;
                    update['annotations[2].xshift'] = marginRight - pieChartXShift;
                }

                Plotly.relayout(`plotly-panel${this.id}`, update);
            }
        }
      }  // onResize()
      
      viewPanel.on('resize', onResize, this); 
      
      Ext.apply(this, {
         layout: 'border',
         tbar:this.durationToolbar,
         items: [leftPanel,images]
         /*{items: [new Ext.Button({text: 'Refresh',
            handler: function() {
               CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);               
            }
         })]
         }*/
      });//Ext.apply
      
      XDMoD.Arr.AppKerSuccessRatePlotPanel.superclass.initComponent.apply(this, arguments);
   }//initComponent
});//XDMoD.Arr.AppKerSuccessRatePanel
