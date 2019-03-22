Ext.namespace('XDMoD.Modules.SummaryPortlets');

/**
 * XDMoD.Modules.SummaryPortlets.CenterReportCardPortlet
 *
 * This portlet is responsible for displaying data that could be used to determine
 * the "health" of a center. Specifically, this implementation shows App Kernel
 * data for a given XDMoD Installation. Specifically it will display a horizontal
 * stacked barchart of the failed, in control, and under / over performing runs
 * of an app kernel by resource.
 *
 * The resources shown are restricted to those that the currently logged in user
 * is authorized to view. This is done via their associated organization,
 * `moddb.Users.organization_id`, which is in turn used as a filter via
 * `modw.resourcefact.organization_id`.
 *
 *
 * This portlet is driven by the REST Endpoint at
 * `<XDMoD Rest URL>/app_kernels/performance_map/raw`. Which in turn is served by
 * the `AppKernelControllerProvider::getRawPerformanceMap` function.
 *
 * It will display a message if no app kernel data is retrieved:
 *     - `this.grid.viewConfig.emptyText`
 *
 * It will mask itself while retrieving data:
 *     - `this.listeners.afterrender`
 *
 * It will unmask itself when it is done retrieving data or when an exception
 * is detected:
 *     - `this.gridstore.listeners.load`
 *     - `this.gridstore.listeners.exception`
 */
XDMoD.Modules.SummaryPortlets.CenterReportCardPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    titleBase: 'Center Report Card',

    tools: [
        {
            id: 'help',
            qtip: `
            <ul>
                <li style="padding-top:6px;margin-bottom:6px;">
                    <span style="width:20px;background:#ff0000;display:inline-block">&nbsp;</span>
                    <span><b>Failed Runs</b></span>
                    <ul>
                        <li style="margin-left:6px;">A run in which the app kernel failed to complete successfully.</li>
                    </ul>
                </li>
                <li style="margin-top:6px;margin-bottom:6px;">
                    <span style="width: 20px;background:#ffb336;display:inline-block">&nbsp;</span>
                    <span><b>Under Performing Runs</b></span>
                    <ul>
                        <li style="margin-left:6px;">A run in which the app kernel completed successfully but performed below the established control region.</li>
                    </ul>
                </li>
                <li style="margin-top:6px;margin-bottom:6px;">
                    <span style="width: 20px;background:#50b432;display:inline-block ">&nbsp;</span>
                    <span><b>In Control Runs</b></span>
                    <ul>
                        <li style="margin-left:6px;">A run in which the app kernel completed successfully and performed within the established control region.</li>
                    </ul>
                </li>
                <li style="margin-top:6px;padding-bottom:6px;">
                    <span style="width: 20px;background:#3c86ff;display:inline-block">&nbsp;</span>
                    <span><b>Over Performing Runs</b></span>
                    <ul>
                        <li style="margin-left:6px;">A run in which the app kernel completed successfully and performed better than the established control region.</li>
                    </ul>
                </li>
            </ul>
            `,
            qwidth: 60
        }
    ],

    /**
     * Constructor for the CenterReportCardPortlet.
     */
    initComponent: function () {
        var self = this;

        var aspectRatio = 0.8;
        this.height = this.width * aspectRatio;

        var dateRanges = CCR.xdmod.ui.DurationToolbar.getDateRanges();
        for ( var i = 0; i < dateRanges.length; i++ ) {
            var dateRange = dateRanges[i];
            if (dateRange.text === this.config.timeframe) {
                this.config.start_date = this.formatDate(dateRange.start);
                this.config.end_date = this.formatDate(dateRange.end);
            }
        }
        /**
         * The main datastore for this portlet. It retrieves app kernel run data
         * for the specified time period from `this.config.start_date` to
         * `this.config.end_date`. These values are populated w/ the value from
         * the XDMoD Duration Toolbar.
         */
        this.gridStore = new CCR.xdmod.CustomJsonStore({
            storeId: 'center-report-card-store',
            root: 'results',
            autoLoad: true,
            fields: [
                'resource',
                'app_kernel',
                'failedRuns',
                'inControlRuns',
                'overPerformingRuns',
                'underPerformingRuns'
            ],
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/app_kernels/performance_map/raw'
            }),
            baseParams: {
                'start_date': this.config.start_date,
                'end_date': this.config.end_date
            },
            listeners: {
                load: function() {
                    // Make sure that once we're loaded we remove this portlets
                    // mask. This was added during the `afterrender` event.
                    self.el.unmask();
                },
                exception: function() {
                    // refresh the grid view so that we can apply the empty text.
                    self.grid.getView().refresh();

                    // unmask so that the user can see what's going on.
                    self.el.unmask();
                }
            }
        }); // this.gridStore = new CCR.xdmod.CustomJsonStore({

        /**
         * A custom column renderer used to generate a stacked horizontal
         * barchart of each app kernels failed, in control, over and under
         * performing runs for a given time period.
         *
         * @param value    {Object}          *** UNUSED ***
         * @param metaData {Object}          *** UNUSED ***
         * @param record   {Ext.data.Record} record provided by the `gridStore`
         * that contains the app kernel run data to be rendered.
         *
         * @returns {string}
         */
        var valueRenderer = function (value, metaData, record) {
            var failed = record.get('failedRuns');
            var inControl = record.get('inControlRuns');
            var overPerforming = record.get('overPerformingRuns');
            var underPerforming = record.get('underPerformingRuns');

            var total = failed + inControl + overPerforming + underPerforming;

            /**
             * Constructs an svg `rect` element based on the provided attributes.
             * This will be used in a stacked horizontal bar chart.
             *
             * @param id     {String} The id to use for the rect element.
             * @param title  {String} The title to display for this elements tooltip
             * @param msg    {String} The msg to display w/ this elements tooltip
             * @param width  {Number} The width of this element ( will be interpreted as a percentage ).
             * @param x      {Number} The distance from the left that this element should reside ( will be interpreted as a percentage ).
             * @param height {Number} The height of this rect element.
             * @param red    {Number} The r of this elements rgb.
             * @param green  {Number} The g of this elements rgb.
             * @param blue   {Number} The b of this elements rgb.
             *
             * @returns {string} for an svg rect element
             */
            var rect = function (id, title, msg, width, x, height, red, green, blue) {
                var xValue = `${x}%`;
                if (Ext.isChrome) {
                    xValue = `calc(${x}% + 1px)`;
                }
                return `<rect id="${id}" width="${width}%" height="${height}" x="${xValue}" style="fill:rgb(${red}, ${green}, ${blue}); stroke-width:1; stroke:rgb(0,0,0)" ext:qtitle="${title}" ext:qtip="${msg}" ext:qwidth="120" />`;

            };

            var height = 20;

            // Make sure that we have at least some runs
            if (total > 0) {
                var contents = [
                    '<div style="width: 100%;">',
                    `<svg width="100%" height="${height}">`
                ];

                var input = {
                    'ak-failed': {
                        title: 'Failed Runs',
                        red: 255,
                        green: 0,
                        blue: 0,
                        runs: failed
                    },
                    'ak-underperforming': {
                        title: 'Under Performing Runs',
                        red: 255,
                        green: 179,
                        blue: 54,
                        runs: underPerforming
                    },
                    'ak-incontrol': {
                        title: 'In Control Runs',
                        red: 80,
                        green: 180,
                        blue: 50,
                        runs: inControl
                    },
                    'ak-overperforming': {
                        title: 'Over Performing Runs',
                        red: 66,
                        green: 134,
                        blue: 255,
                        runs: overPerforming
                    }
                };

                var sum = 0;
                for ( var id in input ) {
                    if (input.hasOwnProperty(id)) {
                        var runs = input[id].runs;
                        var percentage = total > 0 ? Math.round(((runs / total) * 100)) : 0;
                        var msg = `${percentage}% - ( ${runs} / ${total} )`;

                        contents.push(
                            rect(id, input[id].title, msg, percentage, sum, height, input[id].red, input[id].green, input[id].blue)
                        );

                        sum += percentage;
                    }
                }

                contents.push('</svg>');
                contents.push('</div>');
            } else {
                // If we don't have any runs then just output a simple message
                // to let the user know what's up.
                contents = [
                    `<div style="width:100%; height: ${height};">`,
                    '<span style="font-weight: bold">No Data Found!</span>',
                    '</div>'
                ];

            }

            return contents.join("\n");
        }; // var valueRenderer = function (value, metaData, record) {

        /**
         * The main visual element for this portlet. Displays App Kernels by
         * resource, name, and a stacked horizontal bar chart of the failed,
         * under performing, in control and over performing runs.
         */
        this.grid = new Ext.grid.GridPanel({
            width: 200,
            store: this.gridStore,
            autoExpandColumn: 'ak-status',
            colModel: new Ext.grid.ColumnModel({
                columns: [
                    {
                        id: 'ak-resource',
                        header: 'Resource',
                        dataIndex: 'resource',
                        width: 90
                    },
                    {
                        id: 'ak-name',
                        header: 'App Kernel',
                        dataIndex: 'app_kernel',
                        width: 190
                    },
                    {
                        id: 'ak-status',
                        header: 'Status',
                        dataIndex: 'inControlRuns',
                        renderer: valueRenderer
                    }
                ]
            }),
            viewConfig: new Ext.grid.GridView({
                emptyText: 'No App Kernel information available.'
            }),
            listeners: {

                /**
                 * Fires when the user clicks on a row. In this case we construct
                 * a new History token that will direct the UI to the App Kernel
                 * Performance Map tab w/ the currently selected start date and
                 * end date so that the tabs duration toolbar can be set correctly.
                 * The resource / app kernel is also included so that the correct
                 * first row can be selected.
                 *
                 * @param {Ext.grid.GridPanel} grid
                 * @param {number}             rowIndex
                 */
                rowclick: function (grid, rowIndex) {
                    var record = grid.getStore().getAt(rowIndex);

                    var info = {
                        start_date:self.config.start_date,
                        end_date: self.config.end_date,
                        resource: record.get('resource'),
                        app_kernel: record.get('app_kernel')
                    };

                    var token = 'main_tab_panel:app_kernels:app_kernel_performance_map?ak=' + window.btoa(JSON.stringify(info));

                    Ext.History.add(token);
                }
            }
        }); // this.grid

        Ext.apply(this, {
            items: [
                this.grid
            ]
        });

        this.updateTitle(this.config.start_date, this.config.end_date);

        XDMoD.Modules.SummaryPortlets.CenterReportCardPortlet.superclass.initComponent.apply(this, arguments);
    }, // initComponent

    listeners: {
        duration_change: function (timeframe) {
            // Mask the portlet as we're going to be loading new data.
            this.el.mask('Loading...');

            this.gridStore.load({
                params: {
                    start_date: timeframe.start_date,
                    end_date: timeframe.end_date
                }
            });

            // Make sure that the portlet title reflects the updated start / end
            // date
            this.updateTitle(timeframe.start_date, timeframe.end_date);

            // save the new timeframe data for later use.
            this.config.start_date = timeframe.start_date;
            this.config.end_date = timeframe.end_date;
        },

        afterrender: function () {
            this.el.mask('Loading...');
        }
    }, // listeners: {

    /**
     * A helper function that will update this portlet's title attribute w/
     * the start / end date used to generate the data it is displaying.
     *
     * @param {Date} startDate
     * @param {Date} endDate
     */
    updateTitle: function(startDate, endDate) {
        this.setTitle(`${this.titleBase} - ` +
            `${startDate}` +
            ` to ` +
            `${endDate}`);
    }, // updateTitle: function(startDate, endDate) {

    formatDate: function(date) {
        return `${date.getFullYear()}-${("" + (date.getMonth() + 1)).padStart(2, '0')}-${("" + date.getDate()).padStart(2, '0')}`
    } // formatDate: function(date) {

}); // XDMoD.Modules.SummaryPortlets.CenterReportCardPortlet = Ext.extend(Ext.ux.Portlet, {

Ext.reg('CenterReportCardPortlet', XDMoD.Modules.SummaryPortlets.CenterReportCardPortlet);
