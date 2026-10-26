/**
 * ARR active tasks grid.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.AppKerSuccessRateGrid = Ext.extend(Ext.grid.GridPanel, {
    loadMask: true,

    listeners: {
        viewready: function () {
            this.store.load();
        }
    },

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.Arr.AppKerSuccessRateStore();

        const expander = new Ext.ux.grid.RowExpander({
            tpl: new Ext.Template(
                '<div class="status-info-details"><pre>{unsuccessfull_tasks}</pre></div>'
            )
        });

        // Override the RowExpander getRowClass and add error or warning
        // classes in addition to the class returned from the original
        // getRowClass function.

        const getRowClass = expander.getRowClass;

        expander.getRowClass = function (record) {
            const rowClass = getRowClass.apply(this, arguments);

            if (record.get('unsuccessfull') > 0) {
               if (record.get('successfull') == 0) {
                  return rowClass + ' grid-row-error';
               } else {
                  return rowClass + ' grid-row-warning';
               }
            }

            return rowClass;
        };

        Ext.apply(config, {
            plugins: [expander],
            colModel: new Ext.grid.ColumnModel({
                defaults: {sortable: true},
                columns: [
                    expander,
                    {
                        header: 'Resource',
                        dataIndex: 'resource'
                    },
                    {
                        header: 'App Kernel',
                        dataIndex: 'appKer',
                        width: 250
                    },
                    {
                        header: 'Problem Size',
                        dataIndex: 'problemSize',align: 'right',
                        width: 90
                    },
                    {
                        header: 'Successfull Tasks',align: 'right',
                        dataIndex: 'successfull',
                        qtip: 'Successfull Tasks',
                        width: 120
                    },
                    {
                        header: 'Unsuccessfull Tasks',
                        dataIndex: 'unsuccessfull',align: 'right',
                        width: 130
                    },
                    {
                        header: 'Total Tasks',
                        dataIndex: 'total',align: 'right',
                        width: 90
                    },
                    {
                        header: 'Successfull Tasks, %',
                        dataIndex: 'successfull_percent',
                        renderer: Ext.util.Format.numberRenderer("0.00"),
                        align: 'right',
                        width: 130
                    }
                ]
            })
        });
        XDMoD.Arr.AppKerSuccessRateGrid.superclass.constructor.call(this, config);
    }
});
