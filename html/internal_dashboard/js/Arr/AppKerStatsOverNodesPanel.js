/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */


Ext.namespace('XDMoD', 'XDMoD.Arr','CCR', 'CCR.xdmod', 'CCR.xdmod.ui');
Ext.QuickTips.init();  // enable tooltips


XDMoD.Arr.AppKerStatsOverNodesPanel = function (config)
{
   XDMoD.Arr.AppKerStatsOverNodesPanel.superclass.constructor.call(this, config);
}; 

Ext.apply(XDMoD.Arr.AppKerStatsOverNodesPanel,
{
    //static stuff
   
});

Ext.extend(XDMoD.Arr.AppKerStatsOverNodesPanel, Ext.Panel, {
   title: 'Stats Over Nodes',
   problemSizeList:[1,2,4,8,16,32,64,128],
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
   selectedNode:'NoNodeSelected',
   initComponent: function(){
      var appKerStatsOverNodesGrid=new XDMoD.Arr.AppKerStatsOverNodesGrid({
         scope:this,
         region:"center"
      });
      
      this.appKerStatsOverNodesGrid=appKerStatsOverNodesGrid;
      
      
      var appKerSuccessRateGrid=new XDMoD.Arr.AppKerSuccessRateGrid({
         scope:this
      });
      this.appKerSuccessRateGrid=appKerSuccessRateGrid;
      this.bottomPanel = new Ext.Panel(
      {
            region: 'south',
            autoScroll: true,
            collapsible: true,
            split: true,
            title: 'App Kernel Succcess Rate',
            height: 240,
            layout:'fit',
            plugins: [new Ext.ux.collapsedPanelTitlePlugin()],
            items:[appKerSuccessRateGrid]
      });
      
      var storeResources = new DashboardStore({
         url: 'controllers/arr_controller.php',
         autoLoad: true,
         root: 'response',
         baseParams: { 'operation' : 'get_resources' },
         fields: ['id', 'resource']

      });
      this.resourceSelectorComboBox = new Ext.form.ComboBox({

         name: 'Resources',
         editable: false,
         width: 165,
         listWidth: 310,
         fieldLabel: 'Resource',
         store: storeResources,
         displayField: 'resource',
         triggerAction: 'all',
         valueField: 'id',
         emptyText: 'No Resource Selected',
         listeners:
         {
            scope: this,
            'select': function(combo, record, index)
            {
               //this.selectedResource = record.get('id');
               this.selectedNode='NoNodeSelected';
               reloadAll.call(this); 
            }
         }
      });
      
       var optionsPanel = new Ext.Panel({
         //region: 'north',
         //height: 140,
         layout:'vbox',
         margins: '0 0 0 0',
         border: false,
         autoScroll: true,
         useArrows: true,
         layoutConfig: {
            align: 'stretch'
         },
        items: [this.resourceSelectorComboBox, {
               xtype: 'checkbox',
               boxLabel  : 'Show Details of Unsuccessful Tasks',
               id        : 'showUnsuccessfulTasksDetails',
               scope: this,
               checked:false,
               handler: reloadAll
            }, {
               xtype: 'checkbox',
               boxLabel  : 'Show Details of Successful Tasks',
               id        : 'showSuccessfulTasksDetails',
               scope: this,
               checked:false,
               handler: reloadAll
            }
         ],
         flex: 2
      });
      
      
     var leftPanel = new Ext.Panel({
         split: true,
         collapsible: true,
         title: 'Options',
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
         items: [optionsPanel]
      });
      
      this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
          id: 'duration_selector_' +  this.id,
          alignRight: false,
          showRefresh: true,
          showAggregationUnit: false,
          handler: function () {reloadAll.call(this);},
          //handler:  this.reloadAll,
          scope: this //also scope of handle
      });
       
      this.durationToolbar.dateSlider.region = 'south';
      
      function exportFunction(format, showTitle, scale, width,height)
      {
       var parameters = appKerStatsOverNodesGrid.store.baseParams;
    
       parameters['scale'] = scale || 1;
        parameters['show_title'] = showTitle;
        parameters['format'] = format;
       parameters['inline'] = 'n';
       //parameters['start'] = THIS.chartPagingToolbar.cursor;
       //parameters['limit'] = THIS.chartPagingToolbar.pageSize;
       parameters['width'] =  width || 757;
          parameters['height'] = height || 400;
       if(format == 'svg') parameters['font_size'] = 0;
       
          CCR.invokePost("controllers/arr_controller.php", parameters, {
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
             text: 'CSV - comma Separated Values', iconCls: 'csv',
             handler: function ()
             {
               exportFunction('csv', false);
             }
           }
         ]
       });
      this.durationToolbar.addItem('-');
      this.durationToolbar.addItem(exportButton);
      
      var getBaseParams = function ()
      {
        var baseParams = {};
        baseParams.start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
        baseParams.end_date =  this.durationToolbar.getEndDate().format('Y-m-d');
        baseParams.resource = this.resourceSelectorComboBox.getValue();
        baseParams.format='json';
        return baseParams;
      };
      //appKerStatsOverNodesGrid
      this.appKerStatsOverNodesGrid.store.on('beforeload', function() {
         if ( ! this.durationToolbar.validate() ) return;

         var baseParams = {};
         Ext.apply(baseParams, getBaseParams.call(this));
         
         baseParams.operation = 'get_ak_stats_over_nodes';
         
         this.appKerStatsOverNodesGrid.store.baseParams=baseParams;
         
      }, this);
      var appKerStatsOverNodesGrid=this.appKerStatsOverNodesGrid;
      this.appKerStatsOverNodesGrid.on('rowclick', function(grid, rowIndex, columnIndex, e) {
         if ( ! this.durationToolbar.validate() ) return;
         console.log(grid, rowIndex, columnIndex, e);
         //Ext.MessageBox.alert('Status', 'Changes saved successfully.');
         
         this.selectedNode=grid.getStore().getAt(rowIndex).get('node');
         appKerSuccessRateGrid.store.load();
         
      }, this);
      //appKerSuccessRateGrid
      var getBaseParamsAppKerSuccessRate = function ()
      {
        var baseParams = {};
        baseParams.start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
        baseParams.end_date =  this.durationToolbar.getEndDate().format('Y-m-d');
        var resource_id = this.resourceSelectorComboBox.getValue();
        if(resource_id=="")
           baseParams.resources = "ResourceIsNotSelected";
        else
           baseParams.resources = this.resourceSelectorComboBox.getRawValue();
        baseParams.problemSizes = this.problemSizeList.join(';');
        baseParams.appKers = this.appKerList.join(';');
        baseParams.node = this.selectedNode;
        baseParams.showAppKer='true';
        baseParams.showAppKerTotal='true';
        baseParams.showResourceTotal='true';
        baseParams.showUnsuccessfulTasksDetails=Ext.getCmp('showUnsuccessfulTasksDetails').getValue();
        baseParams.showSuccessfulTasksDetails=Ext.getCmp('showSuccessfulTasksDetails').getValue();
        baseParams.showInternalFailureTasks='false';
        baseParams.format='json';
        this.bottomPanel.setTitle('App Kernel Succcess Rate on Node: '+this.selectedNode);
        return baseParams;
      };
      
      this.appKerSuccessRateGrid.store.on('beforeload', function() {
         if ( ! this.durationToolbar.validate() ) return;

         var baseParams = {};
         Ext.apply(baseParams, getBaseParamsAppKerSuccessRate.call(this));
         
         baseParams.operation = 'get_ak_success_rates';
         
         this.appKerSuccessRateGrid.store.baseParams=baseParams;
         
      }, this);
      
      function reloadAll()
      {
         this.appKerStatsOverNodesGrid.store.load();
         this.appKerSuccessRateGrid.store.load();
      }
      
      
      Ext.apply(this, {
         layout: 'border',
         tbar:this.durationToolbar,
         items: [this.appKerStatsOverNodesGrid,leftPanel,this.bottomPanel]
         /*{items: [new Ext.Button({text: 'Refresh',
            handler: function() {
               CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);               
            }
         })]
         }*/
      });//Ext.apply
      
      XDMoD.Arr.AppKerStatsOverNodesPanel.superclass.initComponent.apply(this, arguments);
   }//initComponent
});//XDMoD.Arr.AppKerStatsOverNodesPanel
