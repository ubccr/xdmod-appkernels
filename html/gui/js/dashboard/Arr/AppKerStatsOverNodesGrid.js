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

        Ext.applyIf(config, {
            //title: 'AppKer Success Rate'
        });

        this.store = new XDMoD.Arr.AppKerStatsOverNodesStore();

       /*var expander = new Ext.ux.grid.RowExpander({
            tpl: new Ext.Template(
                '<div class="status-info-details"><pre>{unsuccessfull_tasks}</pre></div>'
            ),
        });

        // Override the RowExpander getRowClass and add error or warning
        // classes in addition to the class returned from the original
        // getRowClass function.

        var getRowClass = expander.getRowClass;

        expander.getRowClass = function (record) {
            var rowClass = getRowClass.apply(this, arguments);

            if (record.get('unsuccessfull')>0) {
               if(record.get('successfull')==0)
                  return rowClass + ' grid-row-error';
               else
                  return rowClass + ' grid-row-warning';
            }

            return rowClass;
        };*/

        Ext.apply(config, {
            //autoExpandColumn: 3,
            //autoExpandMax: 10000,
            //plugins: [expander],

            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },

                columns: [
                          //expander,
                          {
                              header: 'Node',
                              dataIndex: 'node'
                              //align: 'right',
                              //width: 100
                          },
                          {
                             header: 'Unsuccessful Tasks',
                             dataIndex: 'unsuccessful',align: 'right',
                             width: 130
                          },
                          {
                             header: 'Successful Tasks',align: 'right',
                             dataIndex: 'successful',
                             width: 120
                          },
                          
                          {
                             header: 'Total Tasks',
                             dataIndex: 'total',align: 'right',
                             width: 90
                          },
                          {
                             header: 'Successful Tasks, %',
                             dataIndex: 'successful_percent',
                             renderer: Ext.util.Format.numberRenderer("0.00"),
                             align: 'right',
                             width: 130
                          }
                      ]
            })
        });

        XDMoD.Arr.AppKerStatsOverNodesGrid.superclass.constructor.call(this, config);
    }
   
});

