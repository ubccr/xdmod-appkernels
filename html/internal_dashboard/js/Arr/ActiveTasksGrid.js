/**
 * ARR active tasks grid.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.ActiveTasksGrid = Ext.extend(Ext.grid.GridPanel, {
    id: 'arr-active-tasks-grid',
    loadMask: true,

    listeners: {
        viewready: function () {
            this.store.load();
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.applyIf(config, {
            title: 'Active Tasks'
        });

        this.store = new XDMoD.Arr.ActiveTasksStore();

        var expander = new Ext.ux.grid.RowExpander({
            tpl: new Ext.Template(
                '<div class="status-info-details"><pre>{status_info}</pre></div>'
            )
        });

        // Override the RowExpander getRowClass and add error or warning
        // classes in addition to the class returned from the original
        // getRowClass function.

        var getRowClass = expander.getRowClass;

        expander.getRowClass = function (record) {
            var rowClass = getRowClass.apply(this, arguments);

            if (record.get('status').match(/ERROR/)) {
                return rowClass + ' grid-row-error';
            } else if (record.get('fatal_errors_count') > 0 || record.get('fails_to_submit_to_the_queue') > 0) {
                return rowClass + ' grid-row-warning';
            }

            return rowClass;
        };

        Ext.apply(config, {
            autoExpandColumn: 3,
            autoExpandMax: 10000,
            plugins: [expander],

            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },

                columns: [
                    expander,
                    {
                        header: 'Task ID',
                        dataIndex: 'task_id',
                        align: 'right'
                    },
                    {
                        header: 'Status',
                        dataIndex: 'status',
                        width: 200
                    },
                    {
                        header: 'Status Info',
                        dataIndex: 'status_info'
                    },
                    {
                        header: 'Time in Queue',
                        dataIndex: 'time_in_queue',
                        renderer: function (value) {
                            var minute = 1000 * 60,
                                hour = minute * 60,
                                day = hour * 24;

                            if (value === 0) {
                                return '0';
                            } else if (value < minute) {
                                return '< 1 minute';
                            } else if (value < hour) {
                                return Math.floor(value / minute) + ' minutes';
                            } else if (value < day) {
                                return Math.floor(value / hour) + ' hours';
                            } else {
                                return Math.floor(value / day) + ' days';
                            }
                        }
                    },
                    {
                        header: 'App',
                        dataIndex: 'app',
                        width: 200
                    },
                    {
                        header: 'Resource',
                        dataIndex: 'resource'
                    },
                    {
                        header: '# CPUs',
                        dataIndex: 'ncpus',
                        align: 'right'
                    },
                    {
                        header: 'Fatal Errors',
                        dataIndex: 'fatal_errors_count',
                        align: 'right'
                    },
                    {
                        header: 'Fails to Submit',
                        dataIndex: 'fails_to_submit_to_the_queue',
                        align: 'right'
                    }
                ]
            })
        });

        XDMoD.Arr.ActiveTasksGrid.superclass.constructor.call(this, config);
    }
});

