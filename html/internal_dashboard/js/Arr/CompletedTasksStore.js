/**
 * ARR status completed tasks store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.CompletedTasksStore = Ext.extend(Ext.data.JsonStore, {
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
                operation: 'get_completed_tasks'
            },

            root: 'response',
            idProperty: 'task_id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'task_id',
                    type: 'int'
                },
                {
                    name: 'time_finished',
                    type: 'date',
                    dateFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'status',
                    type: 'string'
                },
                {
                    name: 'statusinfo',
                    type: 'string'
                },
                {
                    name: 'resource',
                    type: 'string'
                },
                {
                    name: 'app',
                    type: 'string'
                },
                {
                    name: 'resource_param',
                    type: 'string'
                },
                {
                    name: 'FatalErrorsCount',
                    type: 'int'
                },
                {
                    name: 'FailsToSubmitToTheQueue',
                    type: 'int'
                }
            ]
        });

        XDMoD.Arr.CompletedTasksStore.superclass.constructor.call(this, config);
    }
});

