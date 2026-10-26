/**
 * ARR active tasks grid.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.AppKerStatsOverNodesGrid = Ext.extend(Ext.grid.GridPanel, {
    loadMask: true,

    listeners: {
        viewready: function () {
            this.store.load();
        }
    },

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.Arr.AppKerStatsOverNodesStore();

        Ext.apply(config, {
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: [
                    {
                        header: 'Node',
                        dataIndex: 'node'
                    },
                    {
                        header: 'Unsuccessful Tasks',
                        dataIndex: 'unsuccessful',
                        align: 'right',
                        width: 130
                    },
                    {
                        header: 'Successful Tasks',
                        align: 'right',
                        dataIndex: 'successful',
                        width: 120
                    },
                    {
                        header: 'Total Tasks',
                        dataIndex: 'total',
                        align: 'right',
                        width: 90
                    },
                    {
                        header: 'Successful Tasks, %',
                        dataIndex: 'successful_percent',
                        renderer: Ext.util.Format.numberRenderer('0.00'),
                        align: 'right',
                        width: 130
                    }
                ]
            }
        )
    );

    XDMoD.Arr.AppKerStatsOverNodesGrid.superclass.constructor.call(this, config);
    }
});

