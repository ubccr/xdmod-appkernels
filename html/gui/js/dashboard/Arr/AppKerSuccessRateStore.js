/**
 * ARR status active tasks store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.AppKerSuccessRateStore = Ext.extend(Ext.data.JsonStore, {

    proxy: new Ext.data.HttpProxy({
        url: '/rest/v1.0/app_kernels/success_rate',
        method: 'GET'
    }),

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        var nowEpoch = Date.now();

        Ext.apply(config, {
            root: 'response',
            //idProperty: 'resource',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
               {
                   name: 'resource',
                   type: 'string'
               },
               {
                  name: 'appKer',
                  type: 'string'
               },
               {
                  name: 'problemSize',
                  type: 'string'
               },
               {
                  name: 'successfull',
                  type: 'int'
               },
               {
                  name: 'unsuccessfull',
                  type: 'int'
               },
               {
                  name: 'total',
                  type: 'int'
               },
               {
                  name: 'successfull_percent',
                  type: 'string'
               },
               {
                  name: 'unsuccessfull_tasks',
                  type: 'string'
               }
            ]
        });

        XDMoD.Arr.AppKerSuccessRateStore.superclass.constructor.call(this, config);
    }
});

