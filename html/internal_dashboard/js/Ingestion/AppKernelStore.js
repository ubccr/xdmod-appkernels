/**
 * App kernel ingestion report store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Ingestion');

XDMoD.Ingestion.AppKernelStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/app_kernel.php',

    listeners: {
        exception: function () {
            console.log(arguments);
        }
    },

    constructor: function (config) {
        config = config || {};

        this.logLevels = [];

        Ext.apply(config, {
            baseParams: {
                operation: 'get_ingestion_report'
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'resource',
                    type: 'string'
                },
                {
                    name: 'app_kernel',
                    type: 'string'
                },
                {
                    name: 'ncpus',
                    type: 'int'
                },
                {
                    name: 'examined',
                    type: 'int'
                },
                {
                    name: 'loaded',
                    type: 'int'
                },
                {
                    name: 'incomplete',
                    type: 'int'
                },
                {
                    name: 'parse_error',
                    type: 'int'
                },
                {
                    name: 'queued',
                    type: 'int'
                },
                {
                    name: 'error',
                    type: 'int'
                },
                {
                    name: 'sql_error',
                    type: 'int'
                },
                {
                    name: 'unknown',
                    type: 'int'
                },
                {
                    name: 'duplicate',
                    type: 'int'
                },
                {
                    name: 'exception',
                    type: 'int'
                }
            ]
        });

        XDMoD.Ingestion.AppKernelStore.superclass.constructor.call(this, config);
    },

    setStartDate: function (date) {
        this.setBaseParam('start_date', date.format('Y-m-d'));
    },

    setEndDate: function (date) {
        this.setBaseParam('end_date', date.format('Y-m-d'));
    },

    setOnlyMostRecent: function (only) {
        this.setBaseParam('only_most_recent', only ? '1' : '0');
    },

    setOnlyFailures: function (only) {
        this.setBaseParam('only_failures', only ? '1' : '0');
    }
});

