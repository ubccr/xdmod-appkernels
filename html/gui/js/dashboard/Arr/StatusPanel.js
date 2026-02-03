/**
 * ARR Status panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.StatusPanel = Ext.extend(Ext.TabPanel, {
    id: 'arr-tab-panel',
    title: 'ARR Status',
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    constructor: function (config) {
        config = config || {};

        config.items = [
            new XDMoD.Arr.ActiveTasksGrid(),
            /* new XDMoD.Arr.AppKerSuccessRatePlotPanel(), */
            new XDMoD.Arr.AppKerSuccessRatePanel()
            /*new XDMoD.Arr.AppKerPerformanceMapPanel(),*/
           /* new XDMoD.Arr.AppKerStatsOverNodesPanel()*/
        ];

        XDMoD.Arr.StatusPanel.superclass.constructor.call(this, config);
    }
});

