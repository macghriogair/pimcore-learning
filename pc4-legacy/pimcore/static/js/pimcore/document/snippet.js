/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.document.snippet");
pimcore.document.snippet = Class.create(pimcore.document.page_snippet, {

    initialize: function(id) {

        this.id = intval(id);
        this.setType("snippet");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, "snippet");
        this.getData();
    },

    init: function () {

        this.edit = new pimcore.document.edit(this);

        if (this.isAllowed("settings")) {
            this.settings = new pimcore.document.snippets.settings(this);
            this.scheduler = new pimcore.element.scheduler(this, "document");
            this.notes = new pimcore.element.notes(this, "document");
        }
        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }
        if (this.isAllowed("versions")) {
            this.versions = new pimcore.document.versions(this);
        }

        this.dependencies = new pimcore.element.dependencies(this, "document");
        this.reports = new pimcore.report.panel("document_snippet", this);
    },


    getTabPanel: function () {

        var items = [];
        items.push(this.edit.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.settings.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        var reportLayout = this.reports.getLayout();
        if(reportLayout) {
            items.push(reportLayout);
        }

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            defaults: {autoScroll:true},
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getSaveData : function (only) {

        var parameters = {};
        parameters.id = this.id;

        // get only scheduled tasks
        if (only == "scheduler") {
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
                return parameters;
            }
            catch (e) {
                console.log("scheduler not available");
                return;
            }
        }


        // save all data allowed		
        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e2) {
                //console.log(e2);
            }
        }

        if (this.isAllowed("settings")) {
            // settings
            try {
                parameters.settings = Ext.encode(this.settings.getValues());
            }
            catch (e3) {
                //console.log(e3);
            }

            // scheduler
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
            }
            catch (e4) {
                //console.log(e4);
            }
        }

        // data
        try {
            parameters.data = Ext.encode(this.edit.getValues());
        }
        catch (e5) {
            //console.log(e5);
        }

        return parameters;
    }

});

