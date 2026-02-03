/**
 * App kernel instance store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.AppKernel');

XDMoD.AppKernel.InstanceStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/app_kernel.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        this.logLevels = [];

        Ext.apply(config, {
            baseParams: {
                operation: 'get_instance'
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'id',
                    type: 'int'
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                },
                {
                    name: '',
                    type: ''
                }
            ]
        });

        XDMoD.AppKernel.InstanceStore.superclass.constructor.call(this, config);
    }
});

