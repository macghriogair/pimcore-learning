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

pimcore.registerNS("pimcore.object.keyvalue.specialconfigwindow");
pimcore.object.keyvalue.specialconfigwindow = Class.create({

    initialize: function (data, keyid, parentPanel) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.parentPanel = parentPanel;
        this.keyid = keyid;
    },


    show: function() {

        this.searchfield = new Ext.form.TextField({
            width: 300,
            style: "float: left;",
            fieldLabel: t("search")
        });

        var editPanel = this.getEditPanel();

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            layout: "fit",
            resizable: false,
            title: t("keyvalue_define_select_values"),
            items: [editPanel],
            bbar: [
            "->",{
                xtype: "button",
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: "button",
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.applyData();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.show();
    },

    applyData: function() {
        var value = [];

        var totalCount = this.store.data.length;

        for (var i = 0; i < totalCount; i++) {

            var record = this.store.getAt(i);
            if (record.data.key == "" || record.data.value == "") {
                alert(t("keyvalue_keyvalue_empty"));
                return;
            }
            value.push(record.data);
        }

        this.parentPanel.applyDetailedConfig(this.keyid, value);
        this.searchWindow.close();
    },

    getEditPanel: function () {
        this.resultPanel = new Ext.Panel({
            layout: "fit",
            autoScroll: true,
            items: [this.getGridPanel()],
            tbar: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                }
            ]
        });

        return this.resultPanel;
    },

    onAdd: function () {
        var thePair = {"key" : "",
            "value" : ""};
        this.store.add(thePair);
    },

    getGridPanel: function() {
        var fields = ['key', 'value'];

        this.store = new Ext.data.ArrayStore({
            data: [],
            listeners: {
                add:function() {
                    this.dataChanged = true;
                }.bind(this),
                remove: function() {
                    this.dataChanged = true;
                }.bind(this),
                clear: function () {
                    this.dataChanged = true;
                }.bind(this),
                update: function(store) {
                    this.dataChanged = true;
                }.bind(this)
            },
            fields: fields
        });

        var pairs = [];
        for (var i = 0; i < this.data.length; i++) {
            var pair = this.data[i];

            this.store.add(pair);
        }

        var gridColumns = [];
        gridColumns.push({header: t("key"), width: 275, sortable: true, dataIndex: 'key',
                                                                                editor: new Ext.form.TextField({})});
        gridColumns.push({header: t("value"), width: 275, sortable: true, dataIndex: 'value',
                                                                                editor: new Ext.form.TextField({})});

        gridColumns.push({
            xtype: 'actioncolumn',
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/pimcore/static6/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        grid.getStore().removeAt(rowIndex);
                    }.bind(this)
                }
            ]
        });

        var pageSize = pimcore.helpers.grid.getDefaultPageSize(-1);
        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize});

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        this.gridPanel = Ext.create('Ext.grid.Panel', {
            store: this.store,
            columns: gridColumns,
            viewConfig: {
                markDirty: false
            },
            plugins: [this.cellEditing],
            width: 200,
            height: 200,
            stripeRows: true,
            tbar: {
                items: [
                    {
                        xtype: "tbspacer",
                        width: 20,
                        height: 16
                    },
                    {
                        xtype: "tbtext",
                        text: t('keyvalue_key_unique')
                    }

                ],
                ctCls: "pimcore_force_auto_width",
                cls: "pimcore_force_auto_width"
            },
            autoHeight: true,
            bodyCls: "pimcore_object_tag_objects"
        });

        return this.gridPanel;
    }
});