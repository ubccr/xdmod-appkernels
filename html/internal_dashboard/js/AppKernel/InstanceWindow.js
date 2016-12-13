/**
 * App kernel instance window.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.AppKernel');

XDMoD.AppKernel.InstanceWindow = Ext.extend(Ext.Window, {
    modal: true,
    layout: 'fit',

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            title: 'App Kernel Instance #' + config.instanceId,
            width: Ext.min([Ext.getBody().getViewSize().width, 1280]),
            height: Ext.min([Ext.getBody().getViewSize().height, 800]),
            items: [
                {
                    xtype: 'tabpanel',
                    frame: false,
                    border: false,
                    activeTab: 0,
                    defaults: {
                        tabCls: 'tab-strip'
                    },
                    items: [
                        new XDMoD.AppKernel.InstancePanel({
                            title: 'Instance Data',
                            instanceId: config.instanceId
                        }),
                        new XDMoD.Arr.ErrorMessagePanel({
                            title: 'Output Data',
                            instanceId: config.instanceId
                        })
                    ]
                }
            ]
        });

        XDMoD.AppKernel.InstanceWindow.superclass.constructor.call(this, config);
    }
});
