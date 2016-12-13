/**
 * ARR Status panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 * 
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.AppKernelDashboardPanel = Ext.extend(Ext.ux.GroupTabPanel, {
    title: 'App Kernels',
    id:'appkernels',
    tabWidth: 140,

    constructor: function (config) {
        config = config || {};
        config.activeGroup=0;
        config.items = [
            {
                items: [new XDMoD.Arr.SchedulePanel()]
            },
            {
                items: [new XDMoD.Arr.ActiveTasksPanel()]
            },
            {
                items: [new XDMoD.Arr.ControlRegionsPanel()]
            },
            {
                items: [new XDMoD.Arr.WalltimePanel()]
            },
            {
                items: [new XDMoD.Arr.AppKerPerformanceMapPanel()]
            }
        ];
        var active_tab="appkernel_perfmap";
        var i_active_tab=0;
        //var tabpanel=this.getComponent("dashboard-tabpanel");
        if(document.location.hash!==""){
            var dashboard_viewport=Ext.getCmp("dashboard_viewport");
            var token = XDMoD.Dashboard.tokenize(document.location.hash);
            if (token !== undefined && "root" in token && "tab" in token) {
                active_tab=token.tab;
            }
        }
        var i;
        for(i=0;i<config.items.length;i++){
            if('items' in config.items[i] && config.items[i].items.length>0){
                if("id" in config.items[i].items[0] && config.items[i].items[0].id===active_tab){
                    i_active_tab=i;
                }
            }
        }
        config.activeGroup=i_active_tab;

        XDMoD.Arr.AppKernelDashboardPanel.superclass.constructor.call(this, config);

    },
    onRender: function (ct, position) {
        this.elements = 'body,header';
        XDMoD.Arr.AppKernelDashboardPanel.superclass.onRender.call(this, ct, position);


    },
    listeners: {
        'tabchange': {
            fn: function (tabpanel,tab) {
                var hist="appkernels:"+tab.id;
                if(document.location.hash!==""){
                    var token = XDMoD.Dashboard.tokenize(document.location.hash);
                    if (token !== undefined && "root" in token && "tab" in token && "params" in token) {
                        if(token.tab===tab.id){
                            if(token.params!==""){
                                hist+="?"+token.params;
                            }
                        }
                    }
                }
                Ext.History.add(hist);
            },
            scope: this
        }
    }
});
