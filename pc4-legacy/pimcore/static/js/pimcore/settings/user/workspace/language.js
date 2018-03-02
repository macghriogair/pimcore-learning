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

pimcore.registerNS("pimcore.settings.user.workspace.language");

pimcore.settings.user.workspace.language = Class.create({

    initialize: function (type, data) {
        this.type = type;
        this.data = data;
    },

    getLayout: function() {
        var data = [];
        var nrOfLanguages = pimcore.settings.websiteLanguages.length;
        for (var i = 0; i < nrOfLanguages; i++) {
            var language = pimcore.settings.websiteLanguages[i];
            data.push([language, pimcore.available_languages[language]]);
        }


        var options = {
            name: "languages",
            triggerAction: "all",
            editable: false,
            store: data,
            hideLabel: true,
            width: 350,
            height: 480,
            value: this.data

        };

        this.box = new Ext.ux.form.MultiSelect(options);

        this.window = new Ext.Panel({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            items: [this.box]
        });

        return this.window;
    },

    getValue: function() {
        var value = this.box.getValue();
        return value;
    },

    getType: function() {
        return this.type;
    }
});