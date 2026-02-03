/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */


Ext.namespace('XDMoD', 'XDMoD.Arr','CCR', 'CCR.xdmod', 'CCR.xdmod.ui');
Ext.QuickTips.init();  // enable tooltips


XDMoD.Arr.AppKerSuccessRatePanel = function (config)
{
   XDMoD.Arr.AppKerSuccessRatePanel.superclass.constructor.call(this, config);
}; 

Ext.apply(XDMoD.Arr.AppKerSuccessRatePanel,
{
    //static stuff
   
});


Ext.extend(XDMoD.Arr.AppKerSuccessRatePanel, Ext.Panel, {
   title: 'App Kernel Success Rates Table',
   resourcesList:[ "blacklight",
                   "edge",
                   "edge12core",
                   "lonestar4",
                   "kraken","trestles","gordon",
                   "stampede"
                   ],
   problemSizeList:[1,2,4,8,16,32,64,128],
   appKerList:["xdmod.app.chem.gamess.node",
               "xdmod.app.chem.nwchem.node",
               "xdmod.app.md.namd.node",
               "xdmod.benchmark.hpcc.node",
               "xdmod.benchmark.io.ior.node",
               "xdmod.benchmark.mpi.imb",
               "xdmod.benchmark.graph.graph500.node",
              "xdmod.bundle" 

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
   getSelectedProblemSizes: function()
   {
      var problemSize = [];
      var selNodes = this.problemSizesTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         problemSize.push(node.text);
      });
      return problemSize;
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
   initComponent: function(){
      var appKerSuccessRateGrid=new XDMoD.Arr.AppKerSuccessRateGrid({
         scope:this,
         region:"center"
      });
      
      this.appKerSuccessRateGrid=appKerSuccessRateGrid;
      
      this.showAppKerCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show App Kernel Details',
         checked:true,
         scope: this,
         handler: reloadAll
      });
      this.showAppKerTotalCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show App Kernel Totals',
         checked:true,
         scope: this,
         handler: reloadAll
      });
      this.showResourceTotalCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Resource Totals',
         scope: this,
         checked:true,
         handler: reloadAll
      });
      this.showUnsuccessfulTasksDetailsCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Details of Unsuccessful Tasks',
         scope: this,
         checked:false,
         handler: reloadAll
      });
      this.showSuccessfulTasksDetailsCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Details of Successful Tasks',
         scope: this,
         checked:false,
         handler: reloadAll
      });
      this.showInternalFailureTasksCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Tasks with Internal Failure',
         scope: this,
         checked:false,
         handler: reloadAll
      });

       var resourceChildren = [];
       for ( var i = 0; i < this.resourcesList.length; i++) {
           var resource = this.resourcesList[i];
           resourceChildren.push({
                       text:resource,
                       nick:resource,
                       type:"resource",
                       checked:true,
                       iconCls:"resource",
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
         //height: 200,
         root: new Ext.tree.AsyncTreeNode(
         {
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
           handler: function()
           {
             this.resourcesTree.un('checkchange',reloadAll,this);
             var lastNode = null;
             var selectAll = true;
             
             this.resourcesTree.getRootNode().cascade(function(n) {
                var ui = n.getUI();
                if(ui.isChecked()) selectAll=false;
                lastNode = n;
             });
             
             if(selectAll){
                this.resourcesTree.getRootNode().cascade(function(n) {
                  var ui = n.getUI();
                  if(!ui.isChecked()) ui.toggleCheck(true);
                  lastNode = n;
                });
             }
             else{
                this.resourcesTree.getRootNode().cascade(function(n) {
                   var ui = n.getUI();
                   if(ui.isChecked()) ui.toggleCheck(false);
                   lastNode = n;
                });
             }
             if(lastNode) reloadAll.call(this);
             this.resourcesTree.on('checkchange',reloadAll,this);
             }
          },{
             id: 'refresh',
             qtip: 'Refresh',
             hidden: true,
             scope: this,
             handler: reloadAll
          }],
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
       var problemSizeChildren = [];
       for (var i = 0; i< this.problemSizeList.length; i++) {
           var nodesSize = this.problemSizeList[i];
           problemSizeChildren.push({
               text:String(nodesSize),
               qtip:(nodesSize==1)?nodesSize+"node":nodesSize+"nodes",
               type:"node",
               checked:true,
               iconCls:"node",
               leaf: true
           });
       }
      this.problemSizesTree = new Ext.tree.TreePanel({
         flex: 0.5,
         title: "Problem Size (Cores or Nodes)",
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
             children: problemSizeChildren
          }),
          tools: [{
             id: 'unselect',
             qtip: 'De-select all selected resources.',
             scope: this,
             handler: function()
             {
               this.problemSizesTree.un('checkchange',reloadAll,this);
               var lastNode = null;
               var selectAll = true;
               
               this.problemSizesTree.getRootNode().cascade(function(n) {
                  var ui = n.getUI();
                  if(ui.isChecked()) selectAll=false;
                  lastNode = n;
               });
               
               if(selectAll){
                  this.problemSizesTree.getRootNode().cascade(function(n) {
                    var ui = n.getUI();
                    if(!ui.isChecked()) ui.toggleCheck(true);
                    lastNode = n;
                  });
               }
               else{
                  this.problemSizesTree.getRootNode().cascade(function(n) {
                     var ui = n.getUI();
                     if(ui.isChecked()) ui.toggleCheck(false);
                     lastNode = n;
                  });
               }
               if(lastNode) reloadAll.call(this);
               this.problemSizeTree.on('checkchange',reloadAll,this);
               }
            },{
               id: 'refresh',
               qtip: 'Refresh',
               hidden: true,
               scope: this,
               handler: reloadAll
            }],
          rootVisible: false,
          containerScroll: true,
          margins: '0 0 0 0',
          border: false,
          flex: 2
      });
       var appKerChildren = [];
       for (var i = 0; i < this.appKerList.length; i++) {
           var appker = this.appKerList[i];
           appKerChildren.push({
               text:appker,
               nick:appker,
               type:"app_kernel",
               checked:true,
               iconCls:"appkernel",
               leaf: true
           });
       }
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
            children: appKerChildren
         }),
         tools: [{
            id: 'unselect',
            qtip: 'De-select all selected resources.',
            scope: this,
            handler: function()
            {
              this.appKerTree.un('checkchange',reloadAll,this);
              var lastNode = null;
              var selectAll = true;
              
              this.appKerTree.getRootNode().cascade(function(n) {
                 var ui = n.getUI();
                 if(ui.isChecked()) selectAll=false;
                 lastNode = n;
              });
              
              if(selectAll){
                 this.appKerTree.getRootNode().cascade(function(n) {
                   var ui = n.getUI();
                   if(!ui.isChecked()) ui.toggleCheck(true);
                   lastNode = n;
                 });
              }
              else{
                 this.appKerTree.getRootNode().cascade(function(n) {
                    var ui = n.getUI();
                    if(ui.isChecked()) ui.toggleCheck(false);
                    lastNode = n;
                 });
              }
              if(lastNode) reloadAll.call(this);
              this.appKerTree.on('checkchange',reloadAll,this);
              }
           },{
              id: 'refresh',
              qtip: 'Refresh',
              hidden: true,
              scope: this,
              handler: reloadAll
         }],
         rootVisible: false,
         containerScroll: true,
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
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
         plugins: new Ext.ux.collapsedPanelTitlePlugin(),
         items: [
            {
                    xtype: 'form',
                    layout: 'fit',
                    region: 'north',
                    height: 150,
                    border: false,
                    
                    items: [{
                        xtype: 'fieldset',
                        header: false,
                        layout: 'form',
                        hideLabels: false,
                        border: false,
                        padding:0,
                        labelWidth:10,
                        defaults: {
                            anchor: '0'
                        },
                        items: [
                            this.showAppKerCheckbox,
                            this.showAppKerTotalCheckbox,
                            this.showResourceTotalCheckbox,
                            this.showUnsuccessfulTasksDetailsCheckbox,
                            this.showSuccessfulTasksDetailsCheckbox,
                            this.showInternalFailureTasksCheckbox
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
                    this.resourcesTree,
                    this.problemSizesTree,
                    this.appKerTree
                ]
            }
         ]
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
       var parameters = appKerSuccessRateGrid.store.baseParams;
    
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
              checkDashboardUser: true
          });                
      };
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
        var selectedResources = this.getSelectedResources();   
        var selectedProblemSizes = this.getSelectedProblemSizes();
        var selectedAppKers = this.getSelectedAppKers();
         
        var baseParams = {};
        baseParams.start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
        baseParams.end_date =  this.durationToolbar.getEndDate().format('Y-m-d');
        baseParams.resources = selectedResources.join(';');
        baseParams.problemSizes = selectedProblemSizes.join(';');
        baseParams.appKers = selectedAppKers.join(';');
        baseParams.showAppKer=this.showAppKerCheckbox.getValue();
        baseParams.showAppKerTotal=this.showAppKerTotalCheckbox.getValue();
        baseParams.showResourceTotal=this.showResourceTotalCheckbox.getValue();
        baseParams.showUnsuccessfulTasksDetails=this.showUnsuccessfulTasksDetailsCheckbox.getValue();
        baseParams.showSuccessfulTasksDetails=this.showSuccessfulTasksDetailsCheckbox.getValue();
        baseParams.showInternalFailureTasks=this.showInternalFailureTasksCheckbox.getValue();
        
        baseParams.format='json';
        return baseParams;
      };
      
      this.appKerSuccessRateGrid.store.on('beforeload', function() {
         if ( ! this.durationToolbar.validate() ) return;

         var baseParams = {};
         Ext.apply(baseParams, getBaseParams.call(this));
         
         baseParams.operation = 'get_ak_success_rates';
         
         this.appKerSuccessRateGrid.store.baseParams=baseParams;
         
      }, this);
      
      function reloadAll()
      {
         this.appKerSuccessRateGrid.store.load();
      }
      
      
      Ext.apply(this, {
         layout: 'border',
         tbar:this.durationToolbar,
         items: [this.appKerSuccessRateGrid,leftPanel]
         /*{items: [new Ext.Button({text: 'Refresh',
            handler: function() {
               CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);               
            }
         })]
         }*/
      });//Ext.apply
      
      XDMoD.Arr.AppKerSuccessRatePanel.superclass.initComponent.apply(this, arguments);
   }//initComponent
});//XDMoD.Arr.AppKerSuccessRatePanel
