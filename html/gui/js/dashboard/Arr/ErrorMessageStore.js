/**
 * App kernel error message store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.ErrorMessageStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/arr.php',

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
                operation: 'get_errmsg',
                task_id: config.taskId
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
                    name: 'appstdout',
                    type: 'string'
                },
                {
                    name: 'stderr',
                    type: 'string'
                },
                {
                    name: 'stdout',
                    type: 'string'
                },
                {
                    name: 'taskexeclog',
                    type: 'string'
                }
            ]
        });

        XDMoD.Arr.ErrorMessageStore.superclass.constructor.call(this, config);
    }
});

