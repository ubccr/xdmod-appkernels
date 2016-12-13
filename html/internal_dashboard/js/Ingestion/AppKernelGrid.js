/**
 * App kernel ingestion report grid.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Ingestion');

XDMoD.Ingestion.AppKernelGrid = Ext.extend(Ext.grid.GridPanel, {
    title: 'App Kernel',

    colModel: new Ext.grid.ColumnModel({
        defaults: {
            sortable: true
        },

        columns: [
            {
                header: 'Resource',
                dataIndex: 'resource'
            },
            {
                header: 'App Kernel',
                dataIndex: 'app_kernel',
                width: 200
            },
            {
                header: '# CPUs',
                dataIndex: 'ncpus',
                align: 'right'
            },
            {
                header: 'Examined',
                dataIndex: 'examined',
                align: 'right'
            },
            {
                header: 'Loaded',
                dataIndex: 'loaded',
                align: 'right'
            },
            {
                header: 'Incomplete',
                dataIndex: 'incomplete',
                align: 'right'
            },
            {
                header: 'Parse Error',
                dataIndex: 'parse_error',
                align: 'right'
            },
            {
                header: 'Queued',
                dataIndex: 'queued',
                align: 'right'
            },
            {
                header: 'Error',
                dataIndex: 'error',
                align: 'right'
            },
            {
                header: 'SQL Error',
                dataIndex: 'sql_error',
                align: 'right'
            },
            {
                header: 'Unknown',
                dataIndex: 'unknown',
                align: 'right'
            },
            {
                header: 'Duplicate',
                dataIndex: 'duplicate',
                align: 'right'
            },
            {
                header: 'Exception',
                dataIndex: 'exception',
                align: 'right'
            }
        ]
    }),

    loadMask: true,

    viewConfig: {
        autoFill: true,
        forceFit: true,

        getRowClass: function (record) {
            var errorKeys = [
                'parse_error',
                'queued',
                'error',
                'sql_error',
                'unknown',
                'duplicate',
                'exception'
            ];

            var error = false;

            Ext.each(errorKeys, function (item, index) {
                if (record.get(item) > 0) {
                    error = true;
                }
            }, this);

            if (error) {
                return 'grid-row-error';
            }

            if (record.get('incomplete') > 0) {
                return 'grid-row-warning';
            }

            return '';
        }
    },

    listeners: {
        viewready: function () {
            this.store.load();
        }
    },

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.Ingestion.AppKernelStore();

        var startDate = new Date(),
            endDate = new Date();

        // One week.
        startDate.setDate(endDate.getDate() - 6);

        this.store.setStartDate(startDate);
        this.store.setEndDate(endDate);

        this.tbar = new Ext.Toolbar({
            items: [
                {
                    xtype: 'checkbox',
                    boxLabel: 'Only Most Recent Run',
                    checked: false,
                    listeners: {
                        check: {
                            fn: function (checkbox, checked) {
                                var tb = this.getTopToolbar(),
                                    datefields = tb.findByType('datefield');

                                this.store.setOnlyMostRecent(checked);

                                Ext.each(datefields, function (item, index) {
                                    item.setDisabled(checked);
                                }, this);
                            },
                            scope: this
                        }
                    }
                },
                {
                    xtype: 'tbspacer',
                    width: 10
                },
                {
                    xtype: 'datefield',
                    value: startDate,
                    listeners: {
                        change: {
                            fn: function (field, date) {
                                this.store.setStartDate(date);
                            },
                            scope: this
                        }
                    }
                },
                {
                    xtype: 'tbspacer',
                    width: 10
                },
                {
                    xtype: 'datefield',
                    value: endDate,
                    listeners: {
                        change: {
                            fn: function (field, date) {
                                this.store.setEndDate(date);
                            },
                            scope: this
                        }
                    }
                },
                {
                    xtype: 'tbspacer',
                    width: 10
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Hide 100% Successful AKs',
                    checked: false,
                    listeners: {
                        check: {
                            fn: function (checkbox, checked) {
                                this.store.setOnlyFailures(checked);
                            },
                            scope: this
                        }
                    }
                },
                {
                    xtype: 'tbspacer',
                    width: 10
                },
                {
                    xtype: 'button',
                    text: 'Refresh',
                    iconCls: 'refresh',
                    listeners: {
                        click: {
                            fn: this.store.load,
                            scope: this.store
                        }
                    }
                }
            ]
        });


        XDMoD.Ingestion.AppKernelGrid.superclass.constructor.call(this, config);
    }
});

