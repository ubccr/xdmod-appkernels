/**
 * Ingestion reports panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Ingestion');

XDMoD.Ingestion.ReportsPanel = Ext.extend(Ext.TabPanel, {
    id: 'ingestion-tab-panel',
    title: 'Ingestion Reports',
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    constructor: function (config) {
        config = config || {};

        config.items = [
            new XDMoD.Ingestion.AppKernelGrid()
        ];

        XDMoD.Ingestion.ReportsPanel.superclass.constructor.call(this, config);
    }
});

