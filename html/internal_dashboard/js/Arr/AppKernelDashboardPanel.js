/* global Ext, XDMoD, document */
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
    id: 'appkernels',
    tabWidth: 140,

    constructor: function (config) {
        /* eslint-disable no-param-reassign */
        config = config || {};
        config.activeGroup = 0;
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
            }
        ];
        /* eslint-enable no-param-reassign */

        var active_tab = 'appkernel_perfmap';
        var i_active_tab = 0;

        if (document.location.hash !== '') {
            var token = XDMoD.Dashboard.tokenize(document.location.hash);
            if (token !== undefined && 'root' in token && 'tab' in token) {
                active_tab = token.tab;
            }
        }
        var i;
        for (i = 0; i < config.items.length; i++) {
            if ('items' in config.items[i] && config.items[i].items.length > 0) {
                if ('id' in config.items[i].items[0] && config.items[i].items[0].id === active_tab) {
                    i_active_tab = i;
                }
            }
        }
        // eslint-disable-next-line no-param-reassign
        config.activeGroup = i_active_tab;

        XDMoD.Arr.AppKernelDashboardPanel.superclass.constructor.call(this, config);
    }, // constructor: function (config) {

    onRender: function (ct, position) {
        this.elements = 'body,header';
        XDMoD.Arr.AppKernelDashboardPanel.superclass.onRender.call(this, ct, position);
    }, // onRender: function (ct, position) {

    adjustBodyWidth: function (w) {
        return w - this.tabWidth - 2;
    },

    listeners: {
        tabchange: {
            fn: function (tabpanel, tab) {
                var hist = 'appkernels:' + tab.id;
                if (document.location.hash !== '') {
                    var token = XDMoD.Dashboard.tokenize(document.location.hash);
                    if (token !== undefined && 'root' in token && 'tab' in token && 'params' in token) {
                        if (token.tab === tab.id) {
                            if (token.params !== '') {
                                hist += '?' + token.params;
                            }
                        }
                    }
                }
                Ext.History.add(hist);
            },
            scope: this
        } // tabchange
    } // listeners
}); // XDMoD.Arr.AppKernelDashboardPanel = Ext.extend(Ext.ux.GroupTabPanel, {
