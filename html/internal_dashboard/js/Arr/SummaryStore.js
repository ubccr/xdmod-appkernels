/**
 * ARR status summary store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.SummaryStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/arr.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            baseParams: {
                operation: 'get_summary'
            },

            root: 'response',
            idProperty: 'task_id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'active_count',
                    type: 'int'
                },
                {
                    name: 'queued_1_day_count',
                    type: 'int'
                },
                {
                    name: 'queued_2_days_count',
                    type: 'int'
                },
                {
                    name: 'queued_3_days_count',
                    type: 'int'
                },
                {
                    name: 'queued_4_days_count',
                    type: 'int'
                },
                {
                    name: 'queued_5_days_count',
                    type: 'int'
                }
            ]
        });

        XDMoD.Arr.SummaryStore.superclass.constructor.call(this, config);
    }
});

