/**
 * ARR status active tasks store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.AppKerStatsOverNodesStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/arr_controller.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        var nowEpoch = Date.now();

        Ext.apply(config, {
            baseParams: {
                operation: 'get_ak_stats_over_nodes'
            },

            root: 'response',
            //idProperty: 'resource',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
               {
                   name: 'node',
                   type: 'string'
               },
               {
                  name: 'unsuccessful',
                  type: 'int'
               },
               {
                  name: 'successful',
                  type: 'int'
               },
               {
                  name: 'total',
                  type: 'int'
               },
               {
                  name: 'successful_percent',
                  type: 'float'
               }
            ]
        });
        XDMoD.Arr.AppKerStatsOverNodesStore.superclass.constructor.call(this, config);
    }
});

