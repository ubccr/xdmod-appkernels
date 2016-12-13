/**
 * App kernel error message panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.ErrorMessagePanel = Ext.extend(Ext.Panel, {
    autoScroll: true,

    listeners: {
        afterrender: function () {
            this.store.load({
                callback: function (records) {
                    this.update(this.getHtml(records[0]));
                },
                scope: this
            });
        }
    },

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.Arr.ErrorMessageStore({
            taskId: config.instanceId
        });

        XDMoD.Arr.ErrorMessagePanel.superclass.constructor.call(this, config);
    },

    getHtml: function (record) {
        return '<table border=1>' +
            '<tr><td>App STDOUT</td><td><pre>' + record.get('appstdout') + '</pre></td></tr>' +
            '<tr><td>STDERR</td><td><pre>' + record.get('stderr') + '</pre></td></tr>' +
            '<tr><td>STDOUT</td><td><pre>' + record.get('stdout') + '</pre></td></tr>' +
            '<tr><td>Task Exec Log</td><td><pre>' + record.get('taskexeclog') + '</pre></td></tr>' +
            '</table>';
    }
});

