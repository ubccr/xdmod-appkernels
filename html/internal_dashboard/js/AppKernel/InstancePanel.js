/**
 * App kernel instance panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.AppKernel');

XDMoD.AppKernel.InstancePanel = Ext.extend(Ext.Panel, {
    autoScroll: true,

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            listeners: {
                afterrender: function () {
                    XDMoD.REST.connection.request({
                        url: 'app_kernels/details',
                        method: 'GET',
                        params: {
                            debug: true,
                            instance_id: config.instanceId
                        },
                        success: function (response, options) {
                            var retval = Ext.decode(response.responseText);
                            this.update(retval.results[0]);
                        },
                        failure: function (response, options) {
                            var retval = Ext.decode(response.responseText);
                            console.log(retval);
                            // TODO
                        },
                        scope: this
                    });
                }
            }
        });

        XDMoD.AppKernel.InstancePanel.superclass.constructor.call(this, config);
    }
});

