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

pimcore.registerNS("pimcore.object.classes.data.newsletterActive");
pimcore.object.classes.data.newsletterActive = Class.create(pimcore.object.classes.data.data, {

    type: "newsletterActive",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "newsletterActive";

        if(!initData["name"]) {
            initData = {
                title: t("newsletter_active")
            };
        }

        initData.fieldtype = "newsletterActive";
        initData.datatype = "data";
        initData.name = "newsletterActive";
        treeNode.setText("newsletterActive");

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("newsletter_active");
    },

    getGroup: function () {
        return "crm";
    },

    getIconClass: function () {
        return "pimcore_icon_newsletterActive";
    },

    getLayout: function ($super) {
        $super();

        var nameField = this.layout.getComponent("standardSettings").getComponent("name");
        nameField.disable();

        return this.layout;
    }

});
