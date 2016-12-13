/**
 * ARR status summary portlet.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Arr');

XDMoD.Arr.SummaryPortlet = Ext.extend(XDMoD.Summary.Portlet, {
    title: 'ARR Status',

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.Arr.SummaryStore();

        XDMoD.Arr.SummaryPortlet.superclass.constructor.call(this, config);
    },

    getHtml: function (record) {
        return 'Active Tasks: ' + record.get('active_count') + '<br/>' +
            'Tasks queued for 1 day or more: ' + record.get('queued_1_day_count') + '<br/>' +
            'Tasks queued for 2 days or more: ' + record.get('queued_2_days_count') + '<br/>' +
            'Tasks queued for 3 days or more: ' + record.get('queued_3_days_count') + '<br/>' +
            'Tasks queued for 4 days or more: ' + record.get('queued_4_days_count') + '<br/>' +
            'Tasks queued for 5 days or more: ' + record.get('queued_5_days_count');
    }
});

