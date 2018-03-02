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

pimcore.registerNS("pimcore.object.preview");
pimcore.object.preview = Class.create({

    initialize: function(object) {
        this.object = object;
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('object_"
                                        + this.object.data.general.o_id + "').preview.iFrameLoaded()";

            this.layout = new Ext.Panel({
                title: t('preview'),
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_preview",
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" width="100%" onload="' + iframeOnLoad
                    + '" frameborder="0" id="object_preview_iframe_' + this.object.data.general.o_id + '"></iframe>'
            });

            this.layout.on("resize", this.onLayoutResize.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
            this.layout.on("afterrender", function () {
                this.loadMask = new Ext.LoadMask(this.layout.getEl(), {msg: t("please_wait")});
                this.loadMask.enable();
            }.bind(this));
        }

        return this.layout;
    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("object_preview_iframe_" + this.object.data.general.o_id).setStyle({
            height: (height) + "px"
        });
    },

    iFrameLoaded: function () {
        this.loadMask.hide();
    },

    loadCurrentPreview: function () {
        var date = new Date();

        var path = "/admin/object/preview?id=" + this.object.data.general.o_id + "&time=" + date.getTime();
        
        try {
            Ext.get("object_preview_iframe_" + this.object.data.general.o_id).dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    refresh: function () {
        this.loadMask.show();
        this.object.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.object));
    }

});