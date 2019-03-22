/**
 * This class contains functionality for the App Kernels tab.
 *
 * @author Amin Ghadersohi
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 * @author Nikolay Simakov
 * @author Steven M. Gallo: Load content on render instead of creation
 */
XDMoD.Module.AppKernels = function (config) {
    XDMoD.Module.AppKernels.superclass.constructor.call(this, config);
};

/**
 * Add public static methods to the AppKernels class.
 */

Ext.apply(XDMoD.Module.AppKernels, {
});

/**
 * The application kernels module.
 */

Ext.extend(XDMoD.Module.AppKernels, XDMoD.PortalModule, {
    module_id: 'app_kernels',
    mainTabToken: 'main_tab_panel',
    usesToolbar: false,
    toolbarItems: {

        durationSelector: false,
        exportMenu: false,
        printButton: false,
        reportCheckbox: false

    },
    showDateChooser: true,

    // The activate event fires twice for some reason so ignode the 2nd event.
    first_time_activate: true,

    // Sub-tabs under the App Kernel tab. Content is based on this.permitted_modules
    groupTabPanel: null,

    // Modules that will be rendered in subtabs. These can be modified based on the user role.
    permitted_modules: [
        'app_kernel_viewer',
        'app_kernel_explorer'
    ],

    header: false,
    headerAsText: false,

    /* ==========================================================================================
     * Initialize this module. This is not done directly in initComponent() so we don't
     * automatically load all of the stores until necessary upon rendering the component. This cuts
     * down on extraneous rest calls.
     * ==========================================================================================
     */

    initialize: function(panel) {
        var delim = CCR.xdmod.ui.tokenDelimiter;

        for ( var i=0; i < panel.permitted_modules.length; i++ ) {
            switch (  panel.permitted_modules[i] ) {

            case 'app_kernel_viewer':
                this.appKernelViewer = new XDMoD.Module.AppKernels.AppKernelViewer({
                    title: 'App Kernel Viewer',
                    iconCls: 'line_chart',
                    tooltip: 'Displays data reflecting the reliability and performance of grid resources',
                    id: 'app_kernel_viewer',
                    layoutId: this.layoutId + delim + 'app_kernel_viewer'
                });
                panel.groupTabPanel.add(this.appKernelViewer);
                break;

            case 'app_kernel_explorer':
                this.appKernelExplorer = new XDMoD.Module.AppKernels.AppKernelExplorer({
                    title: 'App Kernel Explorer',
                    iconCls: 'line_chart',
                    tooltip: 'Displays data reflecting the reliability and performance of grid resources',
                    id: 'app_kernel_explorer'     
                });
                panel.groupTabPanel.add(this.appKernelExplorer);
                break;

            case 'app_kernel_notification':
                this.appKernelNotificationPanel = new XDMoD.Module.AppKernels.AppKernelNotificationPanel({
                    id: 'app_kernel_notification',
                    title:'Reports'
                });
                panel.groupTabPanel.add(this.appKernelNotificationPanel);
                break;
            case 'app_kernel_performance_map':
                this.appKernelPerformanceMapPanel = new XDMoD.Arr.AppKerPerformanceMapPanel({
                    id: 'app_kernel_performance_map',
                    title: 'Performance Map'
                });
                panel.groupTabPanel.add(this.appKernelPerformanceMapPanel);
                break;
            default:
                break;
            } // switch ( moduleName )
        }  // for ( moduleName in self.permitted_modules )

        panel.on('activate', panel.setSubtab, panel);

    },  // initialize()

    /* ==========================================================================================
     * When being directed to the App Kernel tab from a report, ensure that the user is placed on
     * the correct sub-tab based on the document hash in the url.
     * ==========================================================================================
     */

    setSubtab: function(panel) {

        // The activate event is fired multuiple times but I'm not sure where it's coming from so
        // ignore multiple events.

        if ( ! panel.first_time_activate ) return;
        
        panel.first_time_activate = false;
        
    },  // setSubtab()

    listeners: {
        beforerender: function(panel) {
            // Initialize the contents before the tab panel is rendered.
            this.initialize(panel);
        }
    }, 

    /**
     * Initialize app kernel module.
     */

    initComponent: function () {
        var delim = CCR.xdmod.ui.tokenDelimiter;
        this.layoutId = this.mainTabToken + delim + this.id;
        this.first_time_activate = true;

        this.groupTabPanel = new Ext.TabPanel({
            id: 'app_kernels_grouptab',
            activeTab: 0,
            region: 'center',
            listeners: {
                'tabchange': {
                    fn: function (tabpanel, tab) {
                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        var delim = CCR.xdmod.ui.tokenDelimiter;
                        var hist = this.mainTabToken + delim + this.module_id + delim + tab.id;

                        if( "current_hash" in tab && tab.current_hash !== '' ) {
                            hist = tab.current_hash;
                        }
                        if ( this.first_time_activate === false ) {
                            Ext.History.add(hist);
                        }
                    },
                    scope: this
                },  // tabchange
                activate: function(panel) {
                    console.log("groupTabPanel activate " + panel.id);
                }
            }
        });


        Ext.apply(this, {
            items: [this.groupTabPanel],
            listeners: {

                /**
                 * This event fires when the tab has been `activated`, a.k.a
                 * selected by the user clicking on the tab or by having
                 * `app_kernels` identifier included in the url hash. Specifically
                 * we're looking to see if we need to activate a `subTab`.
                 */
                activate: function () {
                    if (this.subTab !== undefined) {
                        var currentlyActive = this.groupTabPanel.getActiveTab();
                        if (currentlyActive && currentlyActive.id === this.subTab) {
                            // If it's the same activeTab then we need to force
                            // fire the 'activate' event.
                            currentlyActive.fireEvent('activate', currentlyActive);
                        } else {
                            this.groupTabPanel.suspendEvents(false);
                            this.groupTabPanel.setActiveTab(this.subTab);
                            this.groupTabPanel.resumeEvents();
                        }

                        this.subTab = undefined;
                    }
                }
            }
        });
        
        XDMoD.Module.AppKernels.superclass.initComponent.apply(this, arguments);
    }  // initComponent()
});
